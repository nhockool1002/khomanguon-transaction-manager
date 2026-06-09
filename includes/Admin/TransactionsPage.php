<?php

namespace Khomanguon\TransactionManager\Admin;

use Khomanguon\TransactionManager\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class TransactionsPage
{
    private $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function render()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền truy cập trang này.', 'khomanguon-transaction-manager'));
        }

        $results = $this->plugin->repository()->get_admin_orders();
        $rs_history = $this->plugin->repository()->get_admin_history();
        $results = is_array($results) ? $results : array();
        $rs_history = is_array($rs_history) ? $rs_history : array();
        $template = KHOMANGUON_TRANSACTION_MANAGER_PATH . 'templates/admin/transactions.php';

        if (file_exists($template)) {
            include $template;
        }
    }
}
