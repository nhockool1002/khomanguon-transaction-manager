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
            __('S3 Config', 'khomanguon-transaction-manager'),
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

        wp_nonce_field('khomanguon_s3_meta_box', 'khomanguon_s3_meta_box_nonce');
        ?>
        <div class="form-group">
            <label for="custom-key"><?php echo esc_html__('Key File:', 'khomanguon-transaction-manager'); ?></label>
            <input type="text" class="form-control" id="custom-key" name="custom_key" value="<?php echo esc_attr($key); ?>">
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

        if (isset($_POST['custom_cash'])) {
            update_post_meta($post_id, 'custom_cash', absint($_POST['custom_cash']));
        }
    }
}
