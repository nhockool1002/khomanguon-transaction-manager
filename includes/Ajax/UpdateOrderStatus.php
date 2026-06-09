<?php

namespace Khomanguon\TransactionManager\Ajax;

use Khomanguon\TransactionManager\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class UpdateOrderStatus
{
    private $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;

        add_action('wp_ajax_update_order_status', array($this, 'handle'));
    }

    public function handle()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json(
                array(
                    'message' => __('Bạn không có quyền cập nhật giao dịch.', 'khomanguon-transaction-manager'),
                    'status' => 403,
                ),
                403
            );
        }

        if (!check_ajax_referer('khomanguon_update_order_status', 'nonce', false)) {
            wp_send_json(
                array(
                    'message' => __('Phiên cập nhật không hợp lệ, vui lòng tải lại trang.', 'khomanguon-transaction-manager'),
                    'status' => 403,
                ),
                403
            );
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $status = isset($_POST['status']) ? (int) $_POST['status'] : 0;

        if ($status === 0) {
            wp_send_json(
                array(
                    'message' => __('Vui lòng chọn trạng thái cho giao dịch', 'khomanguon-transaction-manager'),
                    'status' => 400,
                ),
                400
            );
        }

        $order = $this->plugin->repository()->complete_order($order_id, $status);

        if (is_wp_error($order)) {
            wp_send_json(
                array(
                    'message' => $order->get_error_message(),
                    'status' => 400,
                ),
                400
            );
        }

        $current_user = wp_get_current_user();
        $method_name = $this->plugin->method_name($order->method, false);
        $mail_result = $this->plugin->mailer()->send_payment_status_notice(
            $order->user_email,
            $order->user_login,
            $order->user_login,
            $order->amount,
            $method_name,
            $order->payment_orders_code,
            $status,
            $current_user->user_login
        );

        if (is_wp_error($mail_result)) {
            error_log('Mailjet email failed: ' . $mail_result->get_error_message());
        } else {
            error_log('Mailjet email sent successfully.');
        }

        wp_send_json(
            array(
                'message' => __('Cập nhật thành công', 'khomanguon-transaction-manager'),
                'status' => 200,
            ),
            200
        );
    }
}
