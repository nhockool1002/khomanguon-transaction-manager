<?php

namespace Khomanguon\TransactionManager;

use Khomanguon\TransactionManager\Admin\Menu;
use Khomanguon\TransactionManager\Ajax\R2Upload;
use Khomanguon\TransactionManager\Ajax\SignedS3Url;
use Khomanguon\TransactionManager\Ajax\UpdateOrderStatus;
use Khomanguon\TransactionManager\Mail\MailjetMailer;
use Khomanguon\TransactionManager\PostMeta\S3MetaBox;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin
{
    private static $instance;
    private $repository;
    private $mailer;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        Installer::maybe_upgrade();

        $this->repository = new PointsRepository();
        $this->mailer = new MailjetMailer();

        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'), 20);

        new Menu($this);
        new UpdateOrderStatus($this);
        new SignedS3Url($this);
        new R2Upload($this);
        new S3MetaBox();
    }

    public function repository()
    {
        return $this->repository;
    }

    public function mailer()
    {
        return $this->mailer;
    }

    public function register_shortcodes()
    {
        add_shortcode('user_points', array($this, 'shortcode_user_points'));
    }

    public function shortcode_user_points($atts)
    {
        $atts = shortcode_atts(
            array(
                'user_id' => get_current_user_id(),
            ),
            $atts
        );

        return $this->repository->get_user_points($atts['user_id']);
    }

    public function method_name($method_id, $with_markup = true)
    {
        $method_id = (int) $method_id;
        $names = array(
            1 => 'Momo',
            2 => 'Vietcombank',
            3 => 'TPBank',
            4 => 'Timo',
        );

        if (!isset($names[$method_id])) {
            return __('Khác', 'khomanguon-transaction-manager');
        }

        if (!$with_markup) {
            return $names[$method_id];
        }

        return sprintf(
            "<span class='method_payment mt_%d'>%s</span>",
            $method_id,
            esc_html($names[$method_id])
        );
    }

    public function status_name($status_id, $with_markup = true)
    {
        $status_id = (int) $status_id;

        if ($status_id === -1) {
            return $with_markup ? "<span class='text-danger'>Thất bại</span>" : 'Thất bại';
        }

        if ($status_id === 0) {
            return $with_markup ? "<span class='text-warning'>Đang xử lý</span>" : 'Đang xử lý';
        }

        if ($status_id === 1) {
            return $with_markup ? "<span class='text-success'>Thành công</span>" : 'Thành công';
        }

        return __('Khác', 'khomanguon-transaction-manager');
    }

    public function enqueue_frontend_assets()
    {
        wp_enqueue_style(
            'khomanguon-frontend-widgets',
            KHOMANGUON_TRANSACTION_MANAGER_URL . 'assets/css/frontend-widgets.css',
            array(),
            KHOMANGUON_TRANSACTION_MANAGER_VERSION
        );

        if (!is_singular('post')) {
            return;
        }

        wp_enqueue_style(
            'khomanguon-frontend-download',
            KHOMANGUON_TRANSACTION_MANAGER_URL . 'assets/css/frontend-download.css',
            array(),
            KHOMANGUON_TRANSACTION_MANAGER_VERSION
        );

        wp_enqueue_script(
            'khomanguon-frontend-download',
            KHOMANGUON_TRANSACTION_MANAGER_URL . 'assets/js/frontend-download.js',
            array('jquery'),
            KHOMANGUON_TRANSACTION_MANAGER_VERSION,
            true
        );

        wp_localize_script(
            'khomanguon-frontend-download',
            'khomanguonTransaction',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'unlockNonce' => wp_create_nonce('khomanguon_unlock_s3'),
            )
        );
    }

    public function render_download_box($post_id = 0)
    {
        $post_id = $post_id ? absint($post_id) : get_the_ID();

        if (!$post_id) {
            return;
        }

        $template = KHOMANGUON_TRANSACTION_MANAGER_PATH . 'templates/frontend/download.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    public function download_file_details($post_id)
    {
        $post_id = absint($post_id);
        $key = trim((string) get_post_meta($post_id, 'custom_key', true));
        $provider = get_post_meta($post_id, 'download_provider', true);
        $provider = in_array($provider, array('s3', 'r2'), true) ? $provider : 's3';
        $size = $this->download_file_size($provider, $key);

        return array(
            'name' => $this->download_file_name($key),
            'size' => $size,
            'size_label' => $this->format_file_size($size),
        );
    }

    private function download_file_name($key)
    {
        if ($key === '') {
            return '';
        }

        $normalized_key = str_replace('\\', '/', $key);
        $file_name = wp_basename($normalized_key);

        return $file_name !== '' ? rawurldecode($file_name) : $key;
    }

    private function download_file_size($provider, $key)
    {
        if ($key === '' || !class_exists('Aws\\S3\\S3Client')) {
            return 0;
        }

        $cache_key = 'khomanguon_download_file_size_' . md5($provider . '|' . $key);
        $cached_size = get_site_transient($cache_key);
        if ($cached_size !== false && is_numeric($cached_size)) {
            return (int) $cached_size;
        }

        set_error_handler(
            function ($severity, $message) {
                if (strpos($message, 'open_basedir restriction in effect') !== false) {
                    return true;
                }

                return false;
            }
        );

        try {
            if ($provider === 'r2') {
                $s3_client = R2ClientFactory::client();
                $bucket = R2ClientFactory::get_bucket();
            } else {
                $s3_client = S3ClientFactory::client();
                $bucket = S3ClientFactory::get_bucket();
            }

            if (is_wp_error($s3_client) || $bucket === '') {
                set_site_transient($cache_key, 0, 30 * MINUTE_IN_SECONDS);
                return 0;
            }

            $result = $s3_client->headObject(
                array(
                    'Bucket' => $bucket,
                    'Key' => $key,
                )
            );

            $size = isset($result['ContentLength']) ? absint($result['ContentLength']) : 0;
            set_site_transient($cache_key, $size, $size > 0 ? 12 * HOUR_IN_SECONDS : 30 * MINUTE_IN_SECONDS);

            return $size;
        } catch (\Exception $e) {
            set_site_transient($cache_key, 0, 30 * MINUTE_IN_SECONDS);
            return 0;
        } finally {
            restore_error_handler();
        }
    }

    private function format_file_size($bytes)
    {
        $bytes = absint($bytes);
        if ($bytes <= 0) {
            return __('Đang cập nhật', 'khomanguon-transaction-manager');
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $size = (float) $bytes;
        $unit_index = 0;

        while ($size >= 1024 && $unit_index < count($units) - 1) {
            $size /= 1024;
            $unit_index++;
        }

        return number_format_i18n($size, $unit_index === 0 ? 0 : 2) . ' ' . $units[$unit_index];
    }

    public function handle_payment_submission($user_id, $user_name)
    {
        if (!isset($_POST['khomanguon_payment_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['khomanguon_payment_nonce'])), 'khomanguon_payment_order')) {
            return new WP_Error('invalid_payment_nonce', __('Phiên giao dịch không hợp lệ, vui lòng thử lại.', 'khomanguon-transaction-manager'));
        }

        $amount = isset($_POST['amount']) ? absint($_POST['amount']) : 0;
        $method = isset($_POST['method']) ? absint($_POST['method']) : 0;
        $payment_code = isset($_POST['payment_code']) ? sanitize_text_field(wp_unslash($_POST['payment_code'])) : '';

        if ($amount < 50000 || $amount % 1000 !== 0) {
            return new WP_Error('invalid_payment_amount', __('Số tiền tối thiểu là 50.000 VNĐ và phải chia hết cho 1.000.', 'khomanguon-transaction-manager'));
        }

        if (!in_array($method, array(1, 2, 3, 4), true)) {
            return new WP_Error('invalid_payment_method', __('Phương thức thanh toán không hợp lệ.', 'khomanguon-transaction-manager'));
        }

        if ($payment_code === '') {
            return new WP_Error('invalid_payment_code', __('Vui lòng nhập mã giao dịch.', 'khomanguon-transaction-manager'));
        }

        $created = $this->repository->create_order($user_id, $amount, $method, $payment_code, 0);
        if (is_wp_error($created)) {
            return $created;
        }

        $result = $this->mailer->send_payment_created_notice(
            $user_name,
            $amount,
            $this->method_name($method, false),
            $payment_code
        );

        if (is_wp_error($result)) {
            error_log('Mailjet email failed: ' . $result->get_error_message());
        } else {
            error_log('Mailjet email sent successfully.');
        }

        return $created;
    }
}
