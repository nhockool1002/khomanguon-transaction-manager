<?php

namespace Khomanguon\TransactionManager\PostMeta;

if (!defined('ABSPATH')) {
    exit;
}

class S3MetaBox
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'register'));
        add_action('save_post', array($this, 'save'));
    }

    public function register()
    {
        add_meta_box(
            'custom-post-meta-box',
            __('Download Config', 'khomanguon-transaction-manager'),
            array($this, 'render'),
            'post',
            'normal',
            'high'
        );
    }

    public function render($post)
    {
        $key = get_post_meta($post->ID, 'custom_key', true);
        $price = get_post_meta($post->ID, 'custom_cash', true);
        $provider = get_post_meta($post->ID, 'download_provider', true);
        $provider = in_array($provider, array('s3', 'r2'), true) ? $provider : 's3';

        wp_nonce_field('khomanguon_s3_meta_box', 'khomanguon_s3_meta_box_nonce');
        ?>
        <div class="form-group">
            <label for="download-provider"><?php echo esc_html__('Cloud Storage:', 'khomanguon-transaction-manager'); ?></label>
            <select class="form-control" id="download-provider" name="download_provider">
                <option value="s3" <?php selected($provider, 's3'); ?>><?php echo esc_html__('AWS S3', 'khomanguon-transaction-manager'); ?></option>
                <option value="r2" <?php selected($provider, 'r2'); ?>><?php echo esc_html__('Cloudflare R2', 'khomanguon-transaction-manager'); ?></option>
            </select>
        </div>
        <div class="form-group">
            <label for="custom-key"><?php echo esc_html__('Key File:', 'khomanguon-transaction-manager'); ?></label>
            <input type="text" class="form-control" id="custom-key" name="custom_key" value="<?php echo esc_attr($key); ?>">
            <small><?php echo esc_html__('Nhập object key/path tương ứng với provider đã chọn. Ví dụ: source-code/example.zip', 'khomanguon-transaction-manager'); ?></small>
        </div>
        <div class="form-group">
            <label for="custom-price"><?php echo esc_html__('@Cash:', 'khomanguon-transaction-manager'); ?></label>
            <input type="number" class="form-control" id="custom-price" name="custom_cash" min="0" value="<?php echo esc_attr($price); ?>">
        </div>
        <?php
    }

    public function save($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (!isset($_POST['khomanguon_s3_meta_box_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['khomanguon_s3_meta_box_nonce'])), 'khomanguon_s3_meta_box')) {
            return;
        }

        if (isset($_POST['custom_key'])) {
            update_post_meta($post_id, 'custom_key', sanitize_text_field(wp_unslash($_POST['custom_key'])));
        }

        if (isset($_POST['download_provider'])) {
            $provider = sanitize_text_field(wp_unslash($_POST['download_provider']));
            update_post_meta($post_id, 'download_provider', in_array($provider, array('s3', 'r2'), true) ? $provider : 's3');
        }

        if (isset($_POST['custom_cash'])) {
            update_post_meta($post_id, 'custom_cash', absint($_POST['custom_cash']));
        }
    }
}
