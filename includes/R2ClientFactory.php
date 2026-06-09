<?php

namespace Khomanguon\TransactionManager;

use Aws\S3\S3Client;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class R2ClientFactory
{
    public static function is_configured()
    {
        return self::get_account_id() !== ''
            && self::get_bucket() !== ''
            && trim((string) get_option('r2_api_token')) !== ''
            && trim((string) get_option('r2_access_key_id')) !== ''
            && trim((string) get_option('r2_secret_access_key')) !== '';
    }

    public static function client()
    {
        if (!class_exists(S3Client::class)) {
            return new WP_Error('r2_sdk_missing', __('AWS SDK chưa được cấu hình để kết nối R2.', 'khomanguon-transaction-manager'));
        }

        if (!self::is_configured()) {
            return new WP_Error('r2_missing_config', __('Thiếu cấu hình Cloudflare R2.', 'khomanguon-transaction-manager'));
        }

        return new S3Client(
            array(
                'version' => 'latest',
                'region' => 'auto',
                'endpoint' => self::get_endpoint(),
                'use_path_style_endpoint' => true,
                'signature_version' => 'v4',
                'credentials' => array(
                    'key' => trim((string) get_option('r2_access_key_id')),
                    'secret' => trim((string) get_option('r2_secret_access_key')),
                ),
            )
        );
    }

    public static function get_account_id()
    {
        return trim((string) get_option('r2_account_id'));
    }

    public static function get_bucket()
    {
        return trim((string) get_option('r2_bucket'));
    }

    public static function get_endpoint()
    {
        return 'https://' . self::get_account_id() . '.r2.cloudflarestorage.com';
    }
}
