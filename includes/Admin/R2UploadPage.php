<?php

namespace Khomanguon\TransactionManager\Admin;

use Khomanguon\TransactionManager\R2ClientFactory;
use Khomanguon\TransactionManager\S3ClientFactory;

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

        $providers = array(
            's3' => array(
                'label' => 'AWS S3',
                'configured' => S3ClientFactory::is_configured(),
                'bucket' => S3ClientFactory::get_bucket(),
                'prefix' => S3ClientFactory::get_upload_prefix(),
                'endpoint' => '',
            ),
            'r2' => array(
                'label' => 'Cloudflare R2',
                'configured' => R2ClientFactory::is_configured(),
                'bucket' => R2ClientFactory::get_bucket(),
                'prefix' => R2ClientFactory::get_upload_prefix(),
                'endpoint' => R2ClientFactory::get_account_id() !== '' ? R2ClientFactory::get_endpoint() : '',
            ),
        );
        $has_configured_provider = $providers['s3']['configured'] || $providers['r2']['configured'];
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
