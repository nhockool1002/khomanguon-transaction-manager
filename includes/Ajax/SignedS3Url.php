<?php

namespace Khomanguon\TransactionManager\Ajax;

use Aws\S3\S3Client;
use Khomanguon\TransactionManager\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class SignedS3Url
{
    private $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;

        add_action('wp_ajax_get_signed_s3_url', array($this, 'handle'));
    }

    public function handle()
    {
        if (!is_user_logged_in()) {
            wp_send_json(
                array(
                    'message' => __('Vui lòng đăng nhập để mở khoá liên kết.', 'khomanguon-transaction-manager'),
                    'status' => 401,
                ),
                401
            );
        }

        if (!check_ajax_referer('khomanguon_unlock_s3', 'nonce', false)) {
            wp_send_json(
                array(
                    'message' => __('Phiên mở khoá không hợp lệ, vui lòng tải lại trang.', 'khomanguon-transaction-manager'),
                    'status' => 403,
                ),
                403
            );
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json(
                array(
                    'message' => __('Bài viết không hợp lệ.', 'khomanguon-transaction-manager'),
                    'status' => 400,
                ),
                400
            );
        }

        $key = get_post_meta($post_id, 'custom_key', true);
        $cash = get_post_meta($post_id, 'custom_cash', true);

        if ($key === '' || !is_numeric($cash)) {
            wp_send_json(
                array(
                    'message' => __('Liên kết tải về chưa sẵn sàng.', 'khomanguon-transaction-manager'),
                    'status' => 400,
                ),
                400
            );
        }

        $cash = absint($cash);
        $user_id = get_current_user_id();

        if ($this->plugin->repository()->get_user_points($user_id) < $cash) {
            wp_send_json(
                array(
                    'message' => __('Không đủ @Cash để mở khoá liên kết, vui lòng nạp thêm.', 'khomanguon-transaction-manager'),
                    'status' => 400,
                ),
                400
            );
        }

        if (!class_exists(S3Client::class)) {
            wp_send_json(
                array(
                    'message' => __('AWS SDK chưa được cấu hình.', 'khomanguon-transaction-manager'),
                    'status' => 500,
                ),
                500
            );
        }

        $aws_access_key_id = get_option('aws_access_key_id');
        $aws_secret_access_key = get_option('aws_secret_access_key');
        $aws_default_region = get_option('aws_default_region');
        $aws_bucket = get_option('aws_bucket');

        if (empty($aws_access_key_id) || empty($aws_secret_access_key) || empty($aws_default_region) || empty($aws_bucket)) {
            wp_send_json(
                array(
                    'message' => __('Thiếu cấu hình AWS S3.', 'khomanguon-transaction-manager'),
                    'status' => 500,
                ),
                500
            );
        }

        try {
            $s3_client = new S3Client(
                array(
                    'version' => 'latest',
                    'region' => $aws_default_region,
                    'credentials' => array(
                        'key' => $aws_access_key_id,
                        'secret' => $aws_secret_access_key,
                    ),
                )
            );

            $command = $s3_client->getCommand(
                'GetObject',
                array(
                    'Bucket' => $aws_bucket,
                    'Key' => $key,
                )
            );

            $presigned_url = $s3_client->createPresignedRequest($command, '+1 minutes');
            $signed_url = (string) $presigned_url->getUri();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            wp_send_json(
                array(
                    'message' => __('Không thể tạo liên kết tải S3.', 'khomanguon-transaction-manager'),
                    'status' => 500,
                ),
                500
            );
        }

        $post_title = get_the_title($post_id);
        $deducted = $this->plugin->repository()->adjust_points(
            $user_id,
            $cash,
            '-',
            'Tạo liên kết tải cho bài viết: ' . $post_title,
            true
        );

        if (is_wp_error($deducted)) {
            wp_send_json(
                array(
                    'message' => $deducted->get_error_message(),
                    'status' => 400,
                ),
                400
            );
        }

        wp_send_json(
            array(
                'message' => $signed_url,
                'status' => 200,
            ),
            200
        );
    }
}
