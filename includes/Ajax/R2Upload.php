<?php

namespace Khomanguon\TransactionManager\Ajax;

use Khomanguon\TransactionManager\Plugin;
use Khomanguon\TransactionManager\R2ClientFactory;
use Khomanguon\TransactionManager\S3ClientFactory;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class R2Upload
{
    private $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;

        add_action('wp_ajax_khomanguon_r2_list_files', array($this, 'list_files'));
        add_action('wp_ajax_khomanguon_r2_delete_file', array($this, 'delete_file'));
        add_action('wp_ajax_khomanguon_r2_create_multipart_upload', array($this, 'create_multipart_upload'));
        add_action('wp_ajax_khomanguon_r2_sign_multipart_part', array($this, 'sign_multipart_part'));
        add_action('wp_ajax_khomanguon_r2_complete_multipart_upload', array($this, 'complete_multipart_upload'));
        add_action('wp_ajax_khomanguon_r2_abort_multipart_upload', array($this, 'abort_multipart_upload'));
        add_action('wp_ajax_khomanguon_r2_apply_cors', array($this, 'apply_cors'));
        add_action('wp_ajax_khomanguon_r2_update_file_name', array($this, 'update_file_name'));
    }

    public function list_files()
    {
        $storage = $this->require_storage();
        if (is_wp_error($storage)) {
            $this->send_error($storage);
        }

        $prefix = $this->sanitize_object_key($this->post_value('prefix'), true);
        $prefix = $this->apply_base_prefix($storage['prefix'], $prefix, true);
        $continuation_token = sanitize_text_field($this->post_value('continuation_token'));

        try {
            $args = array(
                'Bucket' => $storage['bucket'],
                'MaxKeys' => 100,
            );

            if ($prefix !== '') {
                $args['Prefix'] = $prefix;
            }

            if ($continuation_token !== '') {
                $args['ContinuationToken'] = $continuation_token;
            }

            $result = $storage['client']->listObjectsV2($args);
            $files = array();
            $keys = array();

            foreach ((array) $result->get('Contents') as $object) {
                $object_key = (string) $object['Key'];
                $last_modified = isset($object['LastModified']) && $object['LastModified'] ? $object['LastModified']->format('Y-m-d H:i:s') : '';
                $keys[] = $object_key;
                $files[] = array(
                    'key' => $object_key,
                    'size' => (int) $object['Size'],
                    'lastModified' => $last_modified,
                );
            }

            $analytics = $this->plugin->repository()->get_download_stats_for_keys($storage['provider'], $keys);
            $display_names = $this->plugin->repository()->get_file_display_names($storage['provider'], $keys);
            $totals = $this->plugin->repository()->get_download_totals($storage['provider'], $prefix);

            foreach ($files as &$file) {
                $stats = isset($analytics[$file['key']]) ? $analytics[$file['key']] : array(
                    'download_count' => 0,
                    'revenue' => 0,
                    'members' => array(),
                );

                $file['downloadCount'] = (int) $stats['download_count'];
                $file['revenue'] = (int) $stats['revenue'];
                $file['members'] = $stats['members'];
                $file['displayName'] = isset($display_names[$file['key']]) && $display_names[$file['key']] !== '' ? $display_names[$file['key']] : $file['key'];
            }
            unset($file);

            wp_send_json(
                array(
                    'files' => $files,
                    'provider' => $storage['provider'],
                    'bucket' => $storage['bucket'],
                    'basePrefix' => $storage['prefix'],
                    'totalTrackedFiles' => $totals['file_count'],
                    'totalDownloads' => $totals['download_count'],
                    'totalRevenue' => $totals['revenue'],
                    'isTruncated' => (bool) $result->get('IsTruncated'),
                    'nextContinuationToken' => (string) $result->get('NextContinuationToken'),
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('Cloud list files failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('cloud_list_failed', __('Không thể tải danh sách tệp cloud.', 'khomanguon-transaction-manager')), 500);
        }
    }

    public function update_file_name()
    {
        $storage = $this->require_storage();
        if (is_wp_error($storage)) {
            $this->send_error($storage);
        }

        $key = $this->sanitize_object_key($this->post_value('key'));
        $key = $this->apply_base_prefix($storage['prefix'], $key);
        $display_name = sanitize_text_field($this->post_value('display_name'));

        if ($key === '' || $display_name === '') {
            $this->send_error(new WP_Error('cloud_invalid_file_name', __('Tên file không hợp lệ.', 'khomanguon-transaction-manager')));
        }

        $saved = $this->plugin->repository()->update_file_display_name($storage['provider'], $key, $display_name);
        if (is_wp_error($saved)) {
            $this->send_error($saved, 500);
        }

        wp_send_json(
            array(
                'message' => __('Đã lưu tên file.', 'khomanguon-transaction-manager'),
                'displayName' => $display_name,
                'status' => 200,
            ),
            200
        );
    }

    public function delete_file()
    {
        $storage = $this->require_storage();
        if (is_wp_error($storage)) {
            $this->send_error($storage);
        }

        $key = $this->sanitize_object_key($this->post_value('key'));
        $key = $this->apply_base_prefix($storage['prefix'], $key);
        if ($key === '') {
            $this->send_error(new WP_Error('r2_invalid_key', __('Key tệp không hợp lệ.', 'khomanguon-transaction-manager')));
        }

        try {
            $storage['client']->deleteObject(
                array(
                    'Bucket' => $storage['bucket'],
                    'Key' => $key,
                )
            );

            wp_send_json(
                array(
                    'message' => __('Đã xoá tệp trên cloud.', 'khomanguon-transaction-manager'),
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('Cloud delete file failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('cloud_delete_failed', __('Không thể xoá tệp cloud.', 'khomanguon-transaction-manager')), 500);
        }
    }

    public function create_multipart_upload()
    {
        $storage = $this->require_storage();
        if (is_wp_error($storage)) {
            $this->send_error($storage);
        }

        $key = $this->sanitize_object_key($this->post_value('key'));
        $key = $this->apply_base_prefix($storage['prefix'], $key);
        $content_type = sanitize_mime_type($this->post_value('content_type'));

        if ($key === '') {
            $this->send_error(new WP_Error('r2_invalid_key', __('Key tệp không hợp lệ.', 'khomanguon-transaction-manager')));
        }

        try {
            $args = array(
                'Bucket' => $storage['bucket'],
                'Key' => $key,
            );

            if ($content_type !== '') {
                $args['ContentType'] = $content_type;
            }

            $result = $storage['client']->createMultipartUpload($args);

            wp_send_json(
                array(
                    'key' => $key,
                    'provider' => $storage['provider'],
                    'uploadId' => (string) $result->get('UploadId'),
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('Cloud create multipart upload failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('cloud_create_upload_failed', __('Không thể khởi tạo upload cloud.', 'khomanguon-transaction-manager')), 500);
        }
    }

    public function sign_multipart_part()
    {
        $storage = $this->require_storage();
        if (is_wp_error($storage)) {
            $this->send_error($storage);
        }

        $key = $this->sanitize_object_key($this->post_value('key'));
        $key = $this->apply_base_prefix($storage['prefix'], $key);
        $upload_id = sanitize_text_field($this->post_value('upload_id'));
        $part_number = absint($this->post_value('part_number'));

        if ($key === '' || $upload_id === '' || $part_number <= 0 || $part_number > 10000) {
            $this->send_error(new WP_Error('r2_invalid_part', __('Thông tin part upload không hợp lệ.', 'khomanguon-transaction-manager')));
        }

        try {
            $command = $storage['client']->getCommand(
                'UploadPart',
                array(
                    'Bucket' => $storage['bucket'],
                    'Key' => $key,
                    'UploadId' => $upload_id,
                    'PartNumber' => $part_number,
                )
            );

            $request = $storage['client']->createPresignedRequest($command, '+30 minutes');

            wp_send_json(
                array(
                    'url' => (string) $request->getUri(),
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('Cloud sign multipart part failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('cloud_sign_part_failed', __('Không thể tạo link upload part cloud.', 'khomanguon-transaction-manager')), 500);
        }
    }

    public function complete_multipart_upload()
    {
        $storage = $this->require_storage();
        if (is_wp_error($storage)) {
            $this->send_error($storage);
        }

        $key = $this->sanitize_object_key($this->post_value('key'));
        $key = $this->apply_base_prefix($storage['prefix'], $key);
        $upload_id = sanitize_text_field($this->post_value('upload_id'));
        $parts = json_decode($this->post_value('parts'), true);

        if ($key === '' || $upload_id === '' || !is_array($parts) || empty($parts)) {
            $this->send_error(new WP_Error('r2_invalid_complete', __('Thông tin hoàn tất upload không hợp lệ.', 'khomanguon-transaction-manager')));
        }

        $normalized_parts = array();
        foreach ($parts as $part) {
            if (!isset($part['PartNumber'], $part['ETag'])) {
                continue;
            }

            $part_number = absint($part['PartNumber']);
            $etag = sanitize_text_field($part['ETag']);

            if ($part_number > 0 && $etag !== '') {
                $normalized_parts[] = array(
                    'PartNumber' => $part_number,
                    'ETag' => $etag,
                );
            }
        }

        if (empty($normalized_parts)) {
            $this->send_error(new WP_Error('r2_invalid_parts', __('Danh sách part upload không hợp lệ.', 'khomanguon-transaction-manager')));
        }

        usort(
            $normalized_parts,
            function ($a, $b) {
                return $a['PartNumber'] <=> $b['PartNumber'];
            }
        );

        try {
            $storage['client']->completeMultipartUpload(
                array(
                    'Bucket' => $storage['bucket'],
                    'Key' => $key,
                    'UploadId' => $upload_id,
                    'MultipartUpload' => array(
                        'Parts' => $normalized_parts,
                    ),
                )
            );

            wp_send_json(
                array(
                    'message' => __('Upload cloud hoàn tất.', 'khomanguon-transaction-manager'),
                    'key' => $key,
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('Cloud complete multipart upload failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('cloud_complete_failed', __('Không thể hoàn tất upload cloud.', 'khomanguon-transaction-manager')), 500);
        }
    }

    public function abort_multipart_upload()
    {
        $storage = $this->require_storage();
        if (is_wp_error($storage)) {
            $this->send_error($storage);
        }

        $key = $this->sanitize_object_key($this->post_value('key'));
        $key = $this->apply_base_prefix($storage['prefix'], $key);
        $upload_id = sanitize_text_field($this->post_value('upload_id'));

        if ($key === '' || $upload_id === '') {
            $this->send_error(new WP_Error('r2_invalid_abort', __('Thông tin huỷ upload không hợp lệ.', 'khomanguon-transaction-manager')));
        }

        try {
            $storage['client']->abortMultipartUpload(
                array(
                    'Bucket' => $storage['bucket'],
                    'Key' => $key,
                    'UploadId' => $upload_id,
                )
            );

            wp_send_json(
                array(
                    'message' => __('Đã huỷ upload cloud.', 'khomanguon-transaction-manager'),
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('Cloud abort multipart upload failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('cloud_abort_failed', __('Không thể huỷ upload cloud.', 'khomanguon-transaction-manager')), 500);
        }
    }

    public function apply_cors()
    {
        $storage = $this->require_storage();
        if (is_wp_error($storage)) {
            $this->send_error($storage);
        }

        $origin = $this->get_site_origin();

        try {
            $storage['client']->putBucketCors(
                array(
                    'Bucket' => $storage['bucket'],
                    'CORSConfiguration' => array(
                        'CORSRules' => array(
                            array(
                                'AllowedHeaders' => array('*'),
                                'AllowedMethods' => array('GET', 'HEAD', 'PUT', 'POST', 'DELETE'),
                                'AllowedOrigins' => array($origin),
                                'ExposeHeaders' => array('ETag'),
                                'MaxAgeSeconds' => 3600,
                            ),
                        ),
                    ),
                )
            );

            wp_send_json(
                array(
                    'message' => sprintf(__('Đã cài CORS cho %1$s với origin %2$s.', 'khomanguon-transaction-manager'), strtoupper($storage['provider']), $origin),
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('Cloud apply CORS failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('cloud_cors_failed', __('Không thể cài CORS cho bucket cloud.', 'khomanguon-transaction-manager')), 500);
        }
    }

    private function require_storage()
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error('cloud_forbidden', __('Bạn không có quyền quản lý file cloud.', 'khomanguon-transaction-manager'));
        }

        if (!check_ajax_referer('khomanguon_r2_upload', 'nonce', false)) {
            return new WP_Error('cloud_invalid_nonce', __('Phiên quản lý file cloud không hợp lệ, vui lòng tải lại trang.', 'khomanguon-transaction-manager'));
        }

        $provider = $this->get_provider();
        if ($provider === 's3') {
            $client = S3ClientFactory::client();
            if (is_wp_error($client)) {
                return $client;
            }

            return array(
                'provider' => 's3',
                'client' => $client,
                'bucket' => S3ClientFactory::get_bucket(),
                'prefix' => S3ClientFactory::get_upload_prefix(),
            );
        }

        $client = R2ClientFactory::client();
        if (is_wp_error($client)) {
            return $client;
        }

        return array(
            'provider' => 'r2',
            'client' => $client,
            'bucket' => R2ClientFactory::get_bucket(),
            'prefix' => R2ClientFactory::get_upload_prefix(),
        );
    }

    private function get_provider()
    {
        $provider = sanitize_text_field($this->post_value('provider'));

        return in_array($provider, array('s3', 'r2'), true) ? $provider : 'r2';
    }

    private function post_value($key)
    {
        $value = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';

        return is_scalar($value) ? (string) $value : '';
    }

    private function sanitize_object_key($key, $allow_empty = false)
    {
        $key = sanitize_text_field((string) $key);
        $key = str_replace('\\', '/', $key);
        $key = preg_replace('#/+#', '/', $key);
        $key = ltrim(trim($key), '/');

        if ($key === '' && $allow_empty) {
            return '';
        }

        if ($key === '' || strpos($key, '..') !== false) {
            return '';
        }

        return $key;
    }

    private function apply_base_prefix($base_prefix, $key, $allow_base_only = false)
    {
        $base_prefix = $this->sanitize_object_key($base_prefix, true);
        $base_prefix = $base_prefix === '' ? '' : rtrim($base_prefix, '/') . '/';
        $key = $this->sanitize_object_key($key, true);

        if ($base_prefix === '') {
            return $key;
        }

        if ($key === '' && $allow_base_only) {
            return $base_prefix;
        }

        if ($key === '') {
            return '';
        }

        if (strpos($key, $base_prefix) === 0) {
            return $key;
        }

        return $base_prefix . ltrim($key, '/');
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

    private function send_error(WP_Error $error, $status = 400)
    {
        wp_send_json(
            array(
                'message' => $error->get_error_message(),
                'status' => $status,
            ),
            $status
        );
    }
}
