<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<?php $default_provider = $providers['s3']['configured'] ? 's3' : 'r2'; ?>
<?php $default_tab_provider = $providers['r2']['configured'] ? 'r2' : ($providers['s3']['configured'] ? 's3' : 'r2'); ?>
<div class="wrap khomanguon-admin-page khomanguon-r2-page">
    <div class="khomanguon-admin-card">
        <div class="khomanguon-admin-card__header">
            <div>
                <h1><?php echo esc_html__('Cloud File Manager', 'khomanguon-transaction-manager'); ?></h1>
                <p><?php echo esc_html__('Upload file lớn, quản lý object key và xem analytics tải xuống cho AWS S3 hoặc Cloudflare R2.', 'khomanguon-transaction-manager'); ?></p>
            </div>
            <?php if ($has_configured_provider) : ?>
                <span class="khomanguon-admin-count"><?php echo esc_html__('Cloud Ready', 'khomanguon-transaction-manager'); ?></span>
            <?php endif; ?>
        </div>
        <div class="khomanguon-admin-card__body">
            <?php if (!$has_configured_provider) : ?>
                <div class="notice notice-warning inline">
                    <p>
                        <?php echo esc_html__('Bạn cần cấu hình AWS S3 hoặc Cloudflare R2 trước khi dùng màn hình quản lý file.', 'khomanguon-transaction-manager'); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=cloud-key-management')); ?>"><?php echo esc_html__('Mở trang cài đặt', 'khomanguon-transaction-manager'); ?></a>
                    </p>
                </div>
            <?php endif; ?>

            <div class="khomanguon-r2-provider-grid">
                <?php foreach ($providers as $provider_key => $provider) : ?>
                    <div class="khomanguon-r2-provider-card <?php echo $provider['configured'] ? 'is-configured' : 'is-disabled'; ?>">
                        <strong><?php echo esc_html($provider['label']); ?></strong>
                        <span><?php echo esc_html($provider['configured'] ? __('Đã cấu hình', 'khomanguon-transaction-manager') : __('Chưa cấu hình', 'khomanguon-transaction-manager')); ?></span>
                        <div><?php echo esc_html__('Bucket:', 'khomanguon-transaction-manager'); ?> <code><?php echo esc_html($provider['bucket'] !== '' ? $provider['bucket'] : '-'); ?></code></div>
                        <div><?php echo esc_html__('Upload prefix:', 'khomanguon-transaction-manager'); ?> <code><?php echo esc_html($provider['prefix'] !== '' ? $provider['prefix'] : '/'); ?></code></div>
                        <?php if ($provider_key === 'r2' && $provider['endpoint'] !== '') : ?>
                            <div><?php echo esc_html__('Endpoint:', 'khomanguon-transaction-manager'); ?> <code><?php echo esc_html($provider['endpoint']); ?></code></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="khomanguon-r2-cors">
                <p><?php echo esc_html__('Upload trực tiếp từ trình duyệt cần CORS cho bucket đã chọn. Nếu upload bị lỗi CORS hoặc không đọc được ETag, hãy chọn provider và bấm nút bên dưới một lần.', 'khomanguon-transaction-manager'); ?></p>
                <button type="button" class="button button-secondary" id="khomanguon-r2-apply-cors" <?php disabled(!$has_configured_provider); ?>>
                    <?php echo esc_html__('Cài CORS cho provider đang chọn', 'khomanguon-transaction-manager'); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="khomanguon-admin-card <?php echo $has_configured_provider ? '' : 'khomanguon-r2-disabled-card'; ?>">
        <div class="khomanguon-admin-card__header">
            <div>
                <h1><?php echo esc_html__('Quản lý tệp & Analytics', 'khomanguon-transaction-manager'); ?></h1>
                <p><?php echo esc_html__('Danh sách object theo prefix, số lượt tải, member đã tải và doanh thu @Cash được ghi nhận theo giá tại từng thời điểm tải.', 'khomanguon-transaction-manager'); ?></p>
            </div>
        </div>
        <div class="khomanguon-admin-card__body">
            <div class="khomanguon-cloud-tabs" role="tablist" aria-label="<?php echo esc_attr__('Chọn cloud provider', 'khomanguon-transaction-manager'); ?>">
                <button
                    type="button"
                    class="button khomanguon-cloud-tab <?php echo $default_tab_provider === 'r2' ? 'is-active' : ''; ?>"
                    data-provider="r2"
                    role="tab"
                    aria-selected="<?php echo $default_tab_provider === 'r2' ? 'true' : 'false'; ?>"
                    <?php disabled(!$providers['r2']['configured']); ?>
                >
                    <?php echo esc_html__('R2 Cloud', 'khomanguon-transaction-manager'); ?>
                </button>
                <button
                    type="button"
                    class="button khomanguon-cloud-tab <?php echo $default_tab_provider === 's3' ? 'is-active' : ''; ?>"
                    data-provider="s3"
                    role="tab"
                    aria-selected="<?php echo $default_tab_provider === 's3' ? 'true' : 'false'; ?>"
                    <?php disabled(!$providers['s3']['configured']); ?>
                >
                    <?php echo esc_html__('S3 Cloud', 'khomanguon-transaction-manager'); ?>
                </button>
            </div>

            <div class="khomanguon-r2-analytics-summary">
                <div><strong id="khomanguon-r2-total-files">0</strong><span><?php echo esc_html__('Tệp có lượt tải', 'khomanguon-transaction-manager'); ?></span></div>
                <div><strong id="khomanguon-r2-total-downloads">0</strong><span><?php echo esc_html__('Tổng lượt tải', 'khomanguon-transaction-manager'); ?></span></div>
                <div><strong id="khomanguon-r2-total-revenue">0 @Cash</strong><span><?php echo esc_html__('Tổng doanh thu', 'khomanguon-transaction-manager'); ?></span></div>
            </div>

            <div class="khomanguon-r2-filter">
                <input type="text" id="khomanguon-r2-list-prefix" class="form-control" placeholder="<?php echo esc_attr__('Lọc theo thư mục con trong upload prefix', 'khomanguon-transaction-manager'); ?>" <?php disabled(!$has_configured_provider); ?>>
                <button type="button" class="button button-secondary" id="khomanguon-r2-refresh" <?php disabled(!$has_configured_provider); ?>>
                    <?php echo esc_html__('Tải danh sách', 'khomanguon-transaction-manager'); ?>
                </button>
                <label class="khomanguon-r2-toggle">
                    <input type="checkbox" id="khomanguon-r2-show-file-path" checked <?php disabled(!$has_configured_provider); ?>>
                    <?php echo esc_html__('Hiển thị file path', 'khomanguon-transaction-manager'); ?>
                </label>
                <label class="khomanguon-r2-toggle">
                    <input type="checkbox" id="khomanguon-r2-include-admin" <?php disabled(!$has_configured_provider); ?>>
                    <?php echo esc_html__('Tính cả admin', 'khomanguon-transaction-manager'); ?>
                </label>
            </div>

            <div class="khomanguon-table-wrap">
                <table class="table table-striped table-hover khomanguon-admin-table khomanguon-r2-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Tên file', 'khomanguon-transaction-manager'); ?></th>
                            <th><?php echo esc_html__('Object key', 'khomanguon-transaction-manager'); ?></th>
                            <th class="khomanguon-r2-path-column"><?php echo esc_html__('File path', 'khomanguon-transaction-manager'); ?></th>
                            <th><?php echo esc_html__('Dung lượng', 'khomanguon-transaction-manager'); ?></th>
                            <th><?php echo esc_html__('Cập nhật', 'khomanguon-transaction-manager'); ?></th>
                            <th><?php echo esc_html__('Lượt tải', 'khomanguon-transaction-manager'); ?></th>
                            <th><?php echo esc_html__('Member tải', 'khomanguon-transaction-manager'); ?></th>
                            <th><?php echo esc_html__('Doanh thu', 'khomanguon-transaction-manager'); ?></th>
                            <th><?php echo esc_html__('Thao tác', 'khomanguon-transaction-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="khomanguon-r2-files-body">
                        <tr>
                            <td colspan="9"><?php echo esc_html($has_configured_provider ? __('Đang tải danh sách tệp...', 'khomanguon-transaction-manager') : __('Màn hình quản lý file đang bị vô hiệu hoá vì thiếu cấu hình S3/R2.', 'khomanguon-transaction-manager')); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="khomanguon-admin-card <?php echo $has_configured_provider ? '' : 'khomanguon-r2-disabled-card'; ?>">
        <div class="khomanguon-admin-card__header">
            <div>
                <h1><?php echo esc_html__('Upload file lớn', 'khomanguon-transaction-manager'); ?></h1>
                <p><?php echo esc_html__('File được chia part và upload trực tiếp lên provider đã chọn bằng presigned URL, không đi qua PHP upload limit.', 'khomanguon-transaction-manager'); ?></p>
            </div>
        </div>
        <div class="khomanguon-admin-card__body">
            <div class="khomanguon-r2-upload-grid">
                <div class="form-group">
                    <label for="khomanguon-cloud-provider"><?php echo esc_html__('Cloud provider', 'khomanguon-transaction-manager'); ?></label>
                    <select id="khomanguon-cloud-provider" class="form-control" <?php disabled(!$has_configured_provider); ?>>
                        <?php foreach ($providers as $provider_key => $provider) : ?>
                            <option value="<?php echo esc_attr($provider_key); ?>" <?php selected($default_provider, $provider_key); ?> <?php disabled(!$provider['configured']); ?>>
                                <?php echo esc_html($provider['label'] . ($provider['configured'] ? '' : ' - chưa cấu hình')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="khomanguon-r2-file"><?php echo esc_html__('Chọn file', 'khomanguon-transaction-manager'); ?></label>
                    <input type="file" id="khomanguon-r2-file" class="form-control" <?php disabled(!$has_configured_provider); ?>>
                </div>
                <div class="form-group">
                    <label for="khomanguon-r2-prefix"><?php echo esc_html__('Thư mục con trong upload prefix', 'khomanguon-transaction-manager'); ?></label>
                    <input type="text" id="khomanguon-r2-prefix" class="form-control" placeholder="source-code/" <?php disabled(!$has_configured_provider); ?>>
                </div>
                <div class="form-group khomanguon-r2-key-field">
                    <label for="khomanguon-r2-key"><?php echo esc_html__('Object key sẽ upload', 'khomanguon-transaction-manager'); ?></label>
                    <input type="text" id="khomanguon-r2-key" class="form-control" placeholder="source-code/example.zip" <?php disabled(!$has_configured_provider); ?>>
                    <small id="khomanguon-r2-key-help"><?php echo esc_html__('Object key này đã được giới hạn trong upload prefix của provider đang chọn.', 'khomanguon-transaction-manager'); ?></small>
                </div>
            </div>

            <div class="khomanguon-r2-actions">
                <button type="button" class="button button-primary" id="khomanguon-r2-start-upload" <?php disabled(!$has_configured_provider); ?>>
                    <?php echo esc_html__('Bắt đầu upload', 'khomanguon-transaction-manager'); ?>
                </button>
                <span id="khomanguon-r2-upload-status"></span>
            </div>

            <div class="khomanguon-r2-progress" aria-hidden="true">
                <div id="khomanguon-r2-progress-bar"></div>
            </div>
        </div>
    </div>
</div>
