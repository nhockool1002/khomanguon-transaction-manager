<?php

namespace Khomanguon\TransactionManager;

use Khomanguon\TransactionManager\Admin\Menu;
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
        $this->repository = new PointsRepository();
        $this->mailer = new MailjetMailer();

        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'), 20);

        new Menu($this);
        new UpdateOrderStatus($this);
        new SignedS3Url($this);
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
        if (!is_singular('post')) {
            return;
        }

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
