<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="container-fluid admin-point-wrapper m-2">
    <div class="row">
        <div class="col-sm-12 pt-3">
            <h2 style="color: red;"><?php echo esc_html__('Settings Cloud Configuration', 'khomanguon-transaction-manager'); ?></h2>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 pl-3">
            <div class="wrap">
                <?php if (isset($_GET['status']) && $_GET['status'] === 'success') : ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php echo esc_html__('Đã lưu cài đặt.', 'khomanguon-transaction-manager'); ?></p>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="needs-validation" novalidate>
                    <h3>AWS Settings</h3>
                    <?php wp_nonce_field('aws-settings'); ?>
                    <input type="hidden" name="action" value="save_aws_settings">

                    <div class="form-group row">
                        <label for="aws_access_key_id" class="col-sm-2 col-form-label">AWS Access Key ID</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="aws_access_key_id" name="aws_access_key_id" value="<?php echo esc_attr(get_option('aws_access_key_id')); ?>" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="aws_secret_access_key" class="col-sm-2 col-form-label">AWS Secret Access Key</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" id="aws_secret_access_key" name="aws_secret_access_key" value="<?php echo esc_attr(get_option('aws_secret_access_key')); ?>" required autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="aws_default_region" class="col-sm-2 col-form-label">AWS Default Region</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="aws_default_region" name="aws_default_region" value="<?php echo esc_attr(get_option('aws_default_region')); ?>" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="aws_bucket" class="col-sm-2 col-form-label">AWS Bucket</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="aws_bucket" name="aws_bucket" value="<?php echo esc_attr(get_option('aws_bucket')); ?>" required>
                        </div>
                    </div>

                    <hr />
                    <h3>Cloudflare R2 Settings</h3>
                    <p class="description"><?php echo esc_html__('R2 tương thích S3. Khi cấu hình R2 đầy đủ, plugin sẽ ưu tiên tạo link tải từ R2 và bật trang upload/quản lý tệp R2.', 'khomanguon-transaction-manager'); ?></p>
                    <div class="form-group row">
                        <label for="r2_account_id" class="col-sm-2 col-form-label">Account ID</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="r2_account_id" name="r2_account_id" value="<?php echo esc_attr(get_option('r2_account_id')); ?>">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="r2_api_token" class="col-sm-2 col-form-label">Your API Token</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" id="r2_api_token" name="r2_api_token" value="<?php echo esc_attr(get_option('r2_api_token')); ?>" autocomplete="off">
                            <small class="form-text text-muted"><?php echo esc_html__('Token Cloudflare API được lưu để dùng cho các thao tác Cloudflare mở rộng. Upload/list/delete hiện dùng Access Key ID và Secret Access Key của R2.', 'khomanguon-transaction-manager'); ?></small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="r2_access_key_id" class="col-sm-2 col-form-label">Access Key ID</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="r2_access_key_id" name="r2_access_key_id" value="<?php echo esc_attr(get_option('r2_access_key_id')); ?>">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="r2_secret_access_key" class="col-sm-2 col-form-label">Secret Access Key</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" id="r2_secret_access_key" name="r2_secret_access_key" value="<?php echo esc_attr(get_option('r2_secret_access_key')); ?>" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="r2_bucket" class="col-sm-2 col-form-label">Bucket Name</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="r2_bucket" name="r2_bucket" value="<?php echo esc_attr(get_option('r2_bucket')); ?>">
                        </div>
                    </div>

                    <hr />
                    <h3>Google Settings</h3>
                    <div class="form-group row">
                        <label for="recaptcha_site_key" class="col-sm-2 col-form-label">Google reCaptcha v3 Site Key</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="recaptcha_site_key" name="recaptcha_site_key" value="<?php echo esc_attr(get_option('recaptcha_site_key')); ?>" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="recaptcha_secret_key" class="col-sm-2 col-form-label">Google reCaptcha v3 Secret Key</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" id="recaptcha_secret_key" name="recaptcha_secret_key" value="<?php echo esc_attr(get_option('recaptcha_secret_key')); ?>" required autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="google_tag_manager_id" class="col-sm-2 col-form-label">Google Tag Manager ID</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="google_tag_manager_id" name="google_tag_manager_id" value="<?php echo esc_attr(get_option('google_tag_manager_id')); ?>" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="google_analytics_id" class="col-sm-2 col-form-label">Google Analytics ID</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="google_analytics_id" name="google_analytics_id" value="<?php echo esc_attr(get_option('google_analytics_id')); ?>" required>
                        </div>
                    </div>

                    <hr />
                    <h3>MailJet Settings</h3>
                    <div class="form-group row">
                        <label for="api_mailjet_key" class="col-sm-2 col-form-label">MailJet API Key</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="api_mailjet_key" name="api_mailjet_key" value="<?php echo esc_attr(get_option('api_mailjet_key')); ?>" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="api_mailjet_secret" class="col-sm-2 col-form-label">MailJet API Secret</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" id="api_mailjet_secret" name="api_mailjet_secret" value="<?php echo esc_attr(get_option('api_mailjet_secret')); ?>" required autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="api_mailjet_sender" class="col-sm-2 col-form-label">MailJet Sender</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="api_mailjet_sender" name="api_mailjet_sender" value="<?php echo esc_attr(get_option('api_mailjet_sender')); ?>" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-10 offset-sm-2">
                            <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
