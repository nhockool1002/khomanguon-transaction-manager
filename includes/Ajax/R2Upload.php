<?php

namespace Khomanguon\TransactionManager\Ajax;

use Khomanguon\TransactionManager\R2ClientFactory;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class R2Upload
{
    public function __construct()
    {
        add_action('wp_ajax_khomanguon_r2_list_files', array($this, 'list_files'));
        add_action('wp_ajax_khomanguon_r2_delete_file', array($this, 'delete_file'));
        add_action('wp_ajax_khomanguon_r2_create_multipart_upload', array($this, 'create_multipart_upload'));
        add_action('wp_ajax_khomanguon_r2_sign_multipart_part', array($this, 'sign_multipart_part'));
        add_action('wp_ajax_khomanguon_r2_complete_multipart_upload', array($this, 'complete_multipart_upload'));
        add_action('wp_ajax_khomanguon_r2_abort_multipart_upload', array($this, 'abort_multipart_upload'));
        add_action('wp_ajax_khomanguon_r2_apply_cors', array($this, 'apply_cors'));
    }

    public function list_files()
    {
        $client = $this->require_client();
        if (is_wp_error($client)) {
            $this->send_error($client);
        }

        $prefix = $this->sanitize_object_key($this->post_value('prefix'), true);
        $continuation_token = sanitize_text_field($this->post_value('continuation_token'));

        try {
            $args = array(
                'Bucket' => R2ClientFactory::get_bucket(),
                'MaxKeys' => 100,
            );

            if ($prefix !== '') {
                $args['Prefix'] = $prefix;
            }

            if ($continuation_token !== '') {
                $args['ContinuationToken'] = $continuation_token;
            }

            $result = $client->listObjectsV2($args);
            $files = array();

            foreach ((array) $result->get('Contents') as $object) {
                $last_modified = isset($object['LastModified']) && $object['LastModified'] ? $object['LastModified']->format('Y-m-d H:i:s') : '';
                $files[] = array(
                    'key' => (string) $object['Key'],
                    'size' => (int) $object['Size'],
                    'lastModified' => $last_modified,
                );
            }

            wp_send_json(
                array(
                    'files' => $files,
                    'isTruncated' => (bool) $result->get('IsTruncated'),
                    'nextContinuationToken' => (string) $result->get('NextContinuationToken'),
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('R2 list files failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('r2_list_failed', __('Không thể tải danh sách tệp R2.', 'khomanguon-transaction-manager')), 500);
        }
    }

    public function delete_file()
    {
        $client = $this->require_client();
        if (is_wp_error($client)) {
            $this->send_error($client);
        }

        $key = $this->sanitize_object_key($this->post_value('key'));
        if ($key === '') {
            $this->send_error(new WP_Error('r2_invalid_key', __('Key tệp không hợp lệ.', 'khomanguon-transaction-manager')));
        }

        try {
            $client->deleteObject(
                array(
                    'Bucket' => R2ClientFactory::get_bucket(),
                    'Key' => $key,
                )
            );

            wp_send_json(
                array(
                    'message' => __('Đã xoá tệp trên R2.', 'khomanguon-transaction-manager'),
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('R2 delete file failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('r2_delete_failed', __('Không thể xoá tệp R2.', 'khomanguon-transaction-manager')), 500);
        }
    }

    public function create_multipart_upload()
    {
        $client = $this->require_client();
        if (is_wp_error($client)) {
            $this->send_error($client);
        }

        $key = $this->sanitize_object_key($this->post_value('key'));
        $content_type = sanitize_mime_type($this->post_value('content_type'));

        if ($key === '') {
            $this->send_error(new WP_Error('r2_invalid_key', __('Key tệp không hợp lệ.', 'khomanguon-transaction-manager')));
        }

        try {
            $args = array(
                'Bucket' => R2ClientFactory::get_bucket(),
                'Key' => $key,
            );

            if ($content_type !== '') {
                $args['ContentType'] = $content_type;
            }

            $result = $client->createMultipartUpload($args);

            wp_send_json(
                array(
                    'key' => $key,
                    'uploadId' => (string) $result->get('UploadId'),
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('R2 create multipart upload failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('r2_create_upload_failed', __('Không thể khởi tạo upload R2.', 'khomanguon-transaction-manager')), 500);
        }
    }

    public function sign_multipart_part()
    {
        $client = $this->require_client();
        if (is_wp_error($client)) {
            $this->send_error($client);
        }

        $key = $this->sanitize_object_key($this->post_value('key'));
        $upload_id = sanitize_text_field($this->post_value('upload_id'));
        $part_number = absint($this->post_value('part_number'));

        if ($key === '' || $upload_id === '' || $part_number <= 0 || $part_number > 10000) {
            $this->send_error(new WP_Error('r2_invalid_part', __('Thông tin part upload không hợp lệ.', 'khomanguon-transaction-manager')));
        }

        try {
            $command = $client->getCommand(
                'UploadPart',
                array(
                    'Bucket' => R2ClientFactory::get_bucket(),
                    'Key' => $key,
                    'UploadId' => $upload_id,
                    'PartNumber' => $part_number,
                )
            );

            $request = $client->createPresignedRequest($command, '+30 minutes');

            wp_send_json(
                array(
                    'url' => (string) $request->getUri(),
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('R2 sign multipart part failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('r2_sign_part_failed', __('Không thể tạo link upload part R2.', 'khomanguon-transaction-manager')), 500);
        }
    }

    public function complete_multipart_upload()
    {
        $client = $this->require_client();
        if (is_wp_error($client)) {
            $this->send_error($client);
        }

        $key = $this->sanitize_object_key($this->post_value('key'));
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
            $client->completeMultipartUpload(
                array(
                    'Bucket' => R2ClientFactory::get_bucket(),
                    'Key' => $key,
                    'UploadId' => $upload_id,
                    'MultipartUpload' => array(
                        'Parts' => $normalized_parts,
                    ),
                )
            );

            wp_send_json(
                array(
                    'message' => __('Upload R2 hoàn tất.', 'khomanguon-transaction-manager'),
                    'key' => $key,
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('R2 complete multipart upload failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('r2_complete_failed', __('Không thể hoàn tất upload R2.', 'khomanguon-transaction-manager')), 500);
        }
    }

    public function abort_multipart_upload()
    {
        $client = $this->require_client();
        if (is_wp_error($client)) {
            $this->send_error($client);
        }

        $key = $this->sanitize_object_key($this->post_value('key'));
        $upload_id = sanitize_text_field($this->post_value('upload_id'));

        if ($key === '' || $upload_id === '') {
            $this->send_error(new WP_Error('r2_invalid_abort', __('Thông tin huỷ upload không hợp lệ.', 'khomanguon-transaction-manager')));
        }

        try {
            $client->abortMultipartUpload(
                array(
                    'Bucket' => R2ClientFactory::get_bucket(),
                    'Key' => $key,
                    'UploadId' => $upload_id,
                )
            );

            wp_send_json(
                array(
                    'message' => __('Đã huỷ upload R2.', 'khomanguon-transaction-manager'),
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('R2 abort multipart upload failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('r2_abort_failed', __('Không thể huỷ upload R2.', 'khomanguon-transaction-manager')), 500);
        }
    }

    public function apply_cors()
    {
        $client = $this->require_client();
        if (is_wp_error($client)) {
            $this->send_error($client);
        }

        $origin = $this->get_site_origin();

        try {
            $client->putBucketCors(
                array(
                    'Bucket' => R2ClientFactory::get_bucket(),
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
                    'message' => sprintf(__('Đã cài CORS cho origin %s.', 'khomanguon-transaction-manager'), $origin),
                    'status' => 200,
                ),
                200
            );
        } catch (\Exception $e) {
            error_log('R2 apply CORS failed: ' . $e->getMessage());
            $this->send_error(new WP_Error('r2_cors_failed', __('Không thể cài CORS cho bucket R2.', 'khomanguon-transaction-manager')), 500);
        }
    }

    private function require_client()
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error('r2_forbidden', __('Bạn không có quyền quản lý R2.', 'khomanguon-transaction-manager'));
        }

        if (!check_ajax_referer('khomanguon_r2_upload', 'nonce', false)) {
            return new WP_Error('r2_invalid_nonce', __('Phiên quản lý R2 không hợp lệ, vui lòng tải lại trang.', 'khomanguon-transaction-manager'));
        }

        return R2ClientFactory::client();
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
