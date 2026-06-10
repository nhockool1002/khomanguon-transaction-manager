<?php

namespace Khomanguon\TransactionManager;

use Aws\S3\S3Client;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class S3ClientFactory
{
    public static function is_configured()
    {
        return trim((string) get_option('aws_access_key_id')) !== ''
            && trim((string) get_option('aws_secret_access_key')) !== ''
            && trim((string) get_option('aws_default_region')) !== ''
            && self::get_bucket() !== '';
    }

    public static function client()
    {
        if (!class_exists(S3Client::class)) {
            return new WP_Error('s3_sdk_missing', __('AWS SDK chưa được cấu hình để kết nối S3.', 'khomanguon-transaction-manager'));
        }

        if (!self::is_configured()) {
            return new WP_Error('s3_missing_config', __('Thiếu cấu hình AWS S3.', 'khomanguon-transaction-manager'));
        }

        return new S3Client(
            array(
                'version' => 'latest',
                'region' => trim((string) get_option('aws_default_region')),
                'credentials' => array(
                    'key' => trim((string) get_option('aws_access_key_id')),
                    'secret' => trim((string) get_option('aws_secret_access_key')),
                ),
            )
        );
    }

    public static function get_bucket()
    {
        return trim((string) get_option('aws_bucket'));
    }

    public static function get_upload_prefix()
    {
        return self::normalize_prefix((string) get_option('aws_upload_prefix'));
    }

    public static function normalize_prefix($prefix)
    {
        $prefix = str_replace('\\', '/', sanitize_text_field((string) $prefix));
        $prefix = trim(preg_replace('#/+#', '/', $prefix), '/');

        return $prefix === '' ? '' : $prefix . '/';
    }
}
