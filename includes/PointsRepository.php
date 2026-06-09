<?php

namespace Khomanguon\TransactionManager;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class PointsRepository
{
    private $wpdb;
    private $point_table;
    private $history_table;
    private $orders_table;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
        $this->point_table = $wpdb->prefix . 'point';
        $this->history_table = $wpdb->prefix . 'point_history';
        $this->orders_table = $wpdb->prefix . 'point_orders';
    }

    public function point_table()
    {
        return $this->point_table;
    }

    public function history_table()
    {
        return $this->history_table;
    }

    public function orders_table()
    {
        return $this->orders_table;
    }

    public function get_user_points($user_id)
    {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT point_amount FROM {$this->point_table} WHERE user_id = %d", absint($user_id))
        );
    }

    public function get_user_orders($user_id)
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT id, payment_orders_code, amount, timestamp, payment_status, method
                FROM {$this->orders_table}
                WHERE user_id = %d
                ORDER BY id DESC",
                absint($user_id)
            )
        );
    }

    public function get_user_history($user_id)
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT id, operation, amount, reason, timestamp
                FROM {$this->history_table}
                WHERE user_id = %d
                ORDER BY id DESC",
                absint($user_id)
            )
        );
    }

    public function get_admin_orders()
    {
        return $this->wpdb->get_results(
            "SELECT o.id,
                    o.payment_orders_code,
                    o.amount,
                    o.timestamp,
                    o.payment_status,
                    o.method,
                    o.flg_completed,
                    u.user_login,
                    u.user_email
            FROM {$this->orders_table} o
            INNER JOIN {$this->wpdb->users} u ON o.user_id = u.ID
            ORDER BY o.timestamp DESC"
        );
    }

    public function get_admin_history()
    {
        return $this->wpdb->get_results(
            "SELECT ph.*, u.display_name
            FROM {$this->history_table} AS ph
            JOIN {$this->wpdb->users} AS u ON ph.user_id = u.ID
            WHERE ph.user_id != 1
            ORDER BY ph.timestamp DESC"
        );
    }

    public function get_order($order_id)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT o.*, u.user_login, u.user_email
                FROM {$this->orders_table} o
                INNER JOIN {$this->wpdb->users} u ON o.user_id = u.ID
                WHERE o.id = %d",
                absint($order_id)
            )
        );
    }

    public function create_order($user_id, $amount, $method, $payment_orders_code, $payment_status = 0)
    {
        $user_id = absint($user_id);
        $amount = absint($amount);
        $method = absint($method);
        $payment_orders_code = sanitize_text_field($payment_orders_code);
        $payment_status = (int) $payment_status;

        if ($user_id <= 0 || $amount <= 0 || !in_array($method, array(1, 2, 3, 4), true) || $payment_orders_code === '') {
            return new WP_Error('invalid_order_data', __('Thông tin giao dịch không hợp lệ.', 'khomanguon-transaction-manager'));
        }

        $duplicate = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(1) FROM {$this->orders_table}
                WHERE user_id = %d AND method = %s AND payment_orders_code = %s",
                $user_id,
                (string) $method,
                $payment_orders_code
            )
        );

        if ($duplicate > 0) {
            return new WP_Error('duplicate_payment_code', __('Mã giao dịch này đã được gửi trước đó.', 'khomanguon-transaction-manager'));
        }

        $inserted = $this->wpdb->insert(
            $this->orders_table,
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'method' => (string) $method,
                'payment_orders_code' => $payment_orders_code,
                'payment_status' => $payment_status,
            ),
            array('%d', '%d', '%s', '%s', '%d')
        );

        if (!$inserted) {
            return new WP_Error('create_order_failed', __('Không thể tạo giao dịch.', 'khomanguon-transaction-manager'));
        }

        return (int) $this->wpdb->insert_id;
    }

    public function adjust_points($user_id, $amount, $operation, $reason, $require_sufficient_balance = false)
    {
        $user_id = absint($user_id);
        $amount = absint($amount);
        $operation = sanitize_text_field($operation);
        $reason = sanitize_text_field($reason);

        if ($user_id <= 0 || $amount <= 0 || !in_array($operation, array('+', '-'), true)) {
            return new WP_Error('invalid_point_data', __('Thông tin @Cash không hợp lệ.', 'khomanguon-transaction-manager'));
        }

        $this->wpdb->query('START TRANSACTION');

        $current_points = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT point_amount FROM {$this->point_table} WHERE user_id = %d FOR UPDATE",
                $user_id
            )
        );

        $has_balance_row = $current_points !== null;
        $current_points = (int) $current_points;

        if ($operation === '-' && $require_sufficient_balance && $current_points < $amount) {
            $this->wpdb->query('ROLLBACK');
            return new WP_Error('insufficient_points', __('Không đủ @Cash để thực hiện giao dịch.', 'khomanguon-transaction-manager'));
        }

        $new_points = $operation === '+' ? $current_points + $amount : $current_points - $amount;

        if ($has_balance_row) {
            $saved = $this->wpdb->update(
                $this->point_table,
                array('point_amount' => $new_points),
                array('user_id' => $user_id),
                array('%d'),
                array('%d')
            );
        } else {
            $saved = $this->wpdb->insert(
                $this->point_table,
                array(
                    'user_id' => $user_id,
                    'point_amount' => $new_points,
                ),
                array('%d', '%d')
            );
        }

        if ($saved === false) {
            $this->wpdb->query('ROLLBACK');
            return new WP_Error('point_update_failed', __('Không thể cập nhật @Cash.', 'khomanguon-transaction-manager'));
        }

        $history_saved = $this->wpdb->insert(
            $this->history_table,
            array(
                'user_id' => $user_id,
                'operation' => $operation,
                'amount' => $amount,
                'reason' => $reason,
            ),
            array('%d', '%s', '%d', '%s')
        );

        if (!$history_saved) {
            $this->wpdb->query('ROLLBACK');
            return new WP_Error('point_history_failed', __('Không thể ghi lịch sử @Cash.', 'khomanguon-transaction-manager'));
        }

        $this->wpdb->query('COMMIT');

        return $new_points;
    }

    public function complete_order($order_id, $status)
    {
        $order_id = absint($order_id);
        $status = (int) $status;

        if ($order_id <= 0 || !in_array($status, array(-1, 1), true)) {
            return new WP_Error('invalid_order_status', __('Trạng thái giao dịch không hợp lệ.', 'khomanguon-transaction-manager'));
        }

        $this->wpdb->query('START TRANSACTION');

        $order = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->orders_table} WHERE id = %d FOR UPDATE",
                $order_id
            )
        );

        if (!$order) {
            $this->wpdb->query('ROLLBACK');
            return new WP_Error('order_not_found', __('Không tìm thấy giao dịch.', 'khomanguon-transaction-manager'));
        }

        if ((int) $order->flg_completed === 1) {
            $this->wpdb->query('ROLLBACK');
            return new WP_Error('order_completed', __('Giao dịch này đã được cập nhật.', 'khomanguon-transaction-manager'));
        }

        $updated = $this->wpdb->update(
            $this->orders_table,
            array(
                'payment_status' => $status,
                'flg_completed' => 1,
            ),
            array(
                'id' => $order_id,
                'flg_completed' => 0,
            ),
            array('%d', '%d'),
            array('%d', '%d')
        );

        if (!$updated) {
            $this->wpdb->query('ROLLBACK');
            return new WP_Error('order_update_failed', __('Không thể cập nhật giao dịch.', 'khomanguon-transaction-manager'));
        }

        if ($status === 1) {
            $points = (int) ($order->amount / 1000);
            $current_points = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT point_amount FROM {$this->point_table} WHERE user_id = %d FOR UPDATE",
                    (int) $order->user_id
                )
            );

            $has_balance_row = $current_points !== null;
            $new_points = (int) $current_points + $points;

            if ($has_balance_row) {
                $point_saved = $this->wpdb->update(
                    $this->point_table,
                    array('point_amount' => $new_points),
                    array('user_id' => (int) $order->user_id),
                    array('%d'),
                    array('%d')
                );
            } else {
                $point_saved = $this->wpdb->insert(
                    $this->point_table,
                    array(
                        'user_id' => (int) $order->user_id,
                        'point_amount' => $points,
                    ),
                    array('%d', '%d')
                );
            }

            if ($point_saved === false) {
                $this->wpdb->query('ROLLBACK');
                return new WP_Error('point_update_failed', __('Không thể cộng @Cash cho người dùng.', 'khomanguon-transaction-manager'));
            }

            $history_saved = $this->wpdb->insert(
                $this->history_table,
                array(
                    'user_id' => (int) $order->user_id,
                    'operation' => '+',
                    'amount' => $points,
                    'reason' => __('Nhận tiền thanh toán', 'khomanguon-transaction-manager'),
                ),
                array('%d', '%s', '%d', '%s')
            );

            if (!$history_saved) {
                $this->wpdb->query('ROLLBACK');
                return new WP_Error('point_history_failed', __('Không thể ghi lịch sử @Cash.', 'khomanguon-transaction-manager'));
            }
        }

        $this->wpdb->query('COMMIT');

        return $this->get_order($order_id);
    }
}
