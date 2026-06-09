<?php

namespace Khomanguon\TransactionManager\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsPage
{
    public function render()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền truy cập trang này.', 'khomanguon-transaction-manager'));
        }

        $template = KHOMANGUON_TRANSACTION_MANAGER_PATH . 'templates/admin/settings.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    public function save()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền truy cập trang này.', 'khomanguon-transaction-manager'));
        }

        check_admin_referer('aws-settings');

        $option_keys = array(
            'aws_access_key_id',
            'aws_secret_access_key',
            'aws_default_region',
            'aws_bucket',
            'recaptcha_site_key',
            'recaptcha_secret_key',
            'google_tag_manager_id',
            'google_analytics_id',
            'api_mailjet_key',
            'api_mailjet_secret',
            'api_mailjet_sender',
        );

        foreach ($option_keys as $option_key) {
            $value = isset($_POST[$option_key]) ? sanitize_text_field(wp_unslash($_POST[$option_key])) : '';
            update_option($option_key, $value);
        }

        wp_safe_redirect(admin_url('admin.php?page=cloud-key-management&status=success'));
        exit;
    }
}
