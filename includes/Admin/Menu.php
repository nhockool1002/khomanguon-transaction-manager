<?php

namespace Khomanguon\TransactionManager\Admin;

use Khomanguon\TransactionManager\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class Menu
{
    private $plugin;
    private $transactions_page;
    private $settings_page;
    private $r2_upload_page;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        $this->transactions_page = new TransactionsPage($plugin);
        $this->settings_page = new SettingsPage();
        $this->r2_upload_page = new R2UploadPage();

        add_action('admin_menu', array($this, 'register'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_post_save_aws_settings', array($this->settings_page, 'save'));
    }

    public function register()
    {
        add_menu_page(
            __('Quản lý giao dịch', 'khomanguon-transaction-manager'),
            __('Quản lý giao dịch', 'khomanguon-transaction-manager'),
            'manage_options',
            'payment-management',
            array($this->transactions_page, 'render'),
            'dashicons-tickets',
            9999
        );

        add_submenu_page(
            'payment-management',
            __('Cài đặt Cloud Settings', 'khomanguon-transaction-manager'),
            __('Cài đặt Cloud Settings', 'khomanguon-transaction-manager'),
            'manage_options',
            'cloud-key-management',
            array($this->settings_page, 'render')
        );

        add_submenu_page(
            'payment-management',
            __('R2 Upload', 'khomanguon-transaction-manager'),
            __('R2 Upload', 'khomanguon-transaction-manager'),
            'manage_options',
            'r2-upload-management',
            array($this->r2_upload_page, 'render')
        );
    }

    public function enqueue_assets($hook_suffix)
    {
        if (strpos($hook_suffix, 'payment-management') === false && strpos($hook_suffix, 'cloud-key-management') === false && strpos($hook_suffix, 'r2-upload-management') === false) {
            return;
        }

        wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css', array(), '4.5.2');
        wp_enqueue_style('datatables', 'https://cdn.datatables.net/2.0.3/css/dataTables.bootstrap4.min.css', array('bootstrap-css'), '2.0.3');
        wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11.0.20');

        wp_enqueue_script('bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), '4.5.2', true);
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/2.0.3/js/dataTables.min.js', array('jquery'), '2.0.3', true);
        wp_enqueue_script('datatables-bootstrap', 'https://cdn.datatables.net/2.0.3/js/dataTables.bootstrap4.min.js', array('jquery', 'datatables', 'bootstrap-js'), '2.0.3', true);
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.0.20', true);

        if (strpos($hook_suffix, 'payment-management') !== false) {
            wp_enqueue_style(
                'khomanguon-admin-transactions',
                KHOMANGUON_TRANSACTION_MANAGER_URL . 'assets/css/admin-transactions.css',
                array('bootstrap-css', 'datatables'),
                KHOMANGUON_TRANSACTION_MANAGER_VERSION
            );

            wp_enqueue_script(
                'khomanguon-admin-transactions',
                KHOMANGUON_TRANSACTION_MANAGER_URL . 'assets/js/admin-transactions.js',
                array('jquery', 'datatables-bootstrap', 'sweetalert2'),
                KHOMANGUON_TRANSACTION_MANAGER_VERSION,
                true
            );

            wp_localize_script(
                'khomanguon-admin-transactions',
                'khomanguonAdminTransaction',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('khomanguon_update_order_status'),
                )
            );
        }

        if (strpos($hook_suffix, 'r2-upload-management') !== false) {
            wp_enqueue_style(
                'khomanguon-admin-transactions',
                KHOMANGUON_TRANSACTION_MANAGER_URL . 'assets/css/admin-transactions.css',
                array('bootstrap-css'),
                KHOMANGUON_TRANSACTION_MANAGER_VERSION
            );

            wp_enqueue_style(
                'khomanguon-admin-r2-upload',
                KHOMANGUON_TRANSACTION_MANAGER_URL . 'assets/css/admin-r2-upload.css',
                array('bootstrap-css', 'khomanguon-admin-transactions'),
                KHOMANGUON_TRANSACTION_MANAGER_VERSION
            );

            wp_enqueue_script(
                'khomanguon-admin-r2-upload',
                KHOMANGUON_TRANSACTION_MANAGER_URL . 'assets/js/admin-r2-upload.js',
                array('jquery', 'sweetalert2'),
                KHOMANGUON_TRANSACTION_MANAGER_VERSION,
                true
            );

            wp_localize_script(
                'khomanguon-admin-r2-upload',
                'khomanguonR2Upload',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('khomanguon_r2_upload'),
                    'partSize' => 25 * 1024 * 1024,
                    'bucket' => get_option('r2_bucket'),
                )
            );
        }
    }
}
