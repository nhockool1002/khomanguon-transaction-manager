<?php

namespace Khomanguon\TransactionManager\Admin;

use Khomanguon\TransactionManager\R2ClientFactory;

if (!defined('ABSPATH')) {
    exit;
}

class R2UploadPage
{
    public function render()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền truy cập trang này.', 'khomanguon-transaction-manager'));
        }

        $is_configured = R2ClientFactory::is_configured();
        $bucket = R2ClientFactory::get_bucket();
        $endpoint = R2ClientFactory::get_account_id() !== '' ? R2ClientFactory::get_endpoint() : '';
        $origin = $this->get_site_origin();

        $template = KHOMANGUON_TRANSACTION_MANAGER_PATH . 'templates/admin/r2-upload.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    private function get_site_origin()
    {
        $parts = wp_parse_url(admin_url());
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return home_url();
        }

        $origin = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }
}
