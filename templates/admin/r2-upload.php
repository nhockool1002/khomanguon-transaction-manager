<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap khomanguon-admin-page khomanguon-r2-page">
    <div class="khomanguon-admin-card">
        <div class="khomanguon-admin-card__header">
            <div>
                <h1><?php echo esc_html__('R2 Upload & File Manager', 'khomanguon-transaction-manager'); ?></h1>
                <p><?php echo esc_html__('Upload file lớn trực tiếp lên Cloudflare R2 và quản lý object key dùng cho bài viết.', 'khomanguon-transaction-manager'); ?></p>
            </div>
            <?php if ($is_configured) : ?>
                <span class="khomanguon-admin-count"><?php echo esc_html($bucket); ?></span>
            <?php endif; ?>
        </div>
        <div class="khomanguon-admin-card__body">
            <?php if (!$is_configured) : ?>
                <div class="notice notice-warning inline">
                    <p>
                        <?php echo esc_html__('Bạn cần cấu hình Cloudflare R2 trước khi upload.', 'khomanguon-transaction-manager'); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=cloud-key-management')); ?>"><?php echo esc_html__('Mở trang cài đặt', 'khomanguon-transaction-manager'); ?></a>
                    </p>
                </div>
            <?php else : ?>
                <div class="khomanguon-r2-summary">
                    <div>
                        <strong><?php echo esc_html__('Endpoint:', 'khomanguon-transaction-manager'); ?></strong>
                        <code><?php echo esc_html($endpoint); ?></code>
                    </div>
                    <div>
                        <strong><?php echo esc_html__('Bucket:', 'khomanguon-transaction-manager'); ?></strong>
                        <code><?php echo esc_html($bucket); ?></code>
                    </div>
                    <div>
                        <strong><?php echo esc_html__('Admin origin:', 'khomanguon-transaction-manager'); ?></strong>
                        <code><?php echo esc_html($origin); ?></code>
                    </div>
                </div>

                <div class="khomanguon-r2-cors">
                    <p><?php echo esc_html__('Upload trực tiếp từ trình duyệt cần CORS cho bucket. Nếu upload bị lỗi CORS hoặc không đọc được ETag, hãy bấm nút bên dưới một lần.', 'khomanguon-transaction-manager'); ?></p>
                    <button type="button" class="button button-secondary" id="khomanguon-r2-apply-cors">
                        <?php echo esc_html__('Cài CORS cho bucket', 'khomanguon-transaction-manager'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$is_configured) : ?>
        <div class="khomanguon-admin-card khomanguon-r2-disabled-card">
            <div class="khomanguon-admin-card__header">
                <div>
                    <h1><?php echo esc_html__('Upload file lớn', 'khomanguon-transaction-manager'); ?></h1>
                    <p><?php echo esc_html__('Tính năng này sẽ được bật sau khi cấu hình đủ thông tin R2.', 'khomanguon-transaction-manager'); ?></p>
                </div>
            </div>
            <div class="khomanguon-admin-card__body">
                <div class="khomanguon-r2-upload-grid">
                    <div class="form-group">
                        <label><?php echo esc_html__('Chọn file', 'khomanguon-transaction-manager'); ?></label>
                        <input type="file" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label><?php echo esc_html__('Thư mục/prefix tuỳ chọn', 'khomanguon-transaction-manager'); ?></label>
                        <input type="text" class="form-control" placeholder="source-code/" disabled>
                    </div>
                    <div class="form-group khomanguon-r2-key-field">
                        <label><?php echo esc_html__('Object key trên R2', 'khomanguon-transaction-manager'); ?></label>
                        <input type="text" class="form-control" placeholder="source-code/example.zip" disabled>
                    </div>
                </div>
                <button type="button" class="button button-primary" disabled><?php echo esc_html__('Bắt đầu upload', 'khomanguon-transaction-manager'); ?></button>
            </div>
        </div>

        <div class="khomanguon-admin-card khomanguon-r2-disabled-card">
            <div class="khomanguon-admin-card__header">
                <div>
                    <h1><?php echo esc_html__('Quản lý tệp R2', 'khomanguon-transaction-manager'); ?></h1>
                    <p><?php echo esc_html__('Danh sách tệp sẽ khả dụng khi R2 được cấu hình đầy đủ.', 'khomanguon-transaction-manager'); ?></p>
                </div>
            </div>
            <div class="khomanguon-admin-card__body">
                <div class="khomanguon-r2-filter">
                    <input type="text" class="form-control" placeholder="<?php echo esc_attr__('Lọc theo prefix, ví dụ source-code/', 'khomanguon-transaction-manager'); ?>" disabled>
                    <button type="button" class="button button-secondary" disabled><?php echo esc_html__('Tải danh sách', 'khomanguon-transaction-manager'); ?></button>
                </div>
                <div class="khomanguon-empty-state"><?php echo esc_html__('Màn hình R2 đang bị vô hiệu hoá vì thiếu cấu hình.', 'khomanguon-transaction-manager'); ?></div>
            </div>
        </div>
    <?php else : ?>
        <div class="khomanguon-admin-card">
            <div class="khomanguon-admin-card__header">
                <div>
                    <h1><?php echo esc_html__('Upload file lớn', 'khomanguon-transaction-manager'); ?></h1>
                    <p><?php echo esc_html__('File được chia part và upload trực tiếp lên R2 bằng presigned URL, không đi qua PHP upload limit.', 'khomanguon-transaction-manager'); ?></p>
                </div>
            </div>
            <div class="khomanguon-admin-card__body">
                <div class="khomanguon-r2-upload-grid">
                    <div class="form-group">
                        <label for="khomanguon-r2-file"><?php echo esc_html__('Chọn file', 'khomanguon-transaction-manager'); ?></label>
                        <input type="file" id="khomanguon-r2-file" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="khomanguon-r2-prefix"><?php echo esc_html__('Thư mục/prefix tuỳ chọn', 'khomanguon-transaction-manager'); ?></label>
                        <input type="text" id="khomanguon-r2-prefix" class="form-control" placeholder="source-code/">
                    </div>
                    <div class="form-group khomanguon-r2-key-field">
                        <label for="khomanguon-r2-key"><?php echo esc_html__('Object key trên R2', 'khomanguon-transaction-manager'); ?></label>
                        <input type="text" id="khomanguon-r2-key" class="form-control" placeholder="source-code/example.zip">
                        <small><?php echo esc_html__('Copy key này vào trường Key File của bài viết để tạo link tải.', 'khomanguon-transaction-manager'); ?></small>
                    </div>
                </div>

                <div class="khomanguon-r2-actions">
                    <button type="button" class="button button-primary" id="khomanguon-r2-start-upload">
                        <?php echo esc_html__('Bắt đầu upload', 'khomanguon-transaction-manager'); ?>
                    </button>
                    <span id="khomanguon-r2-upload-status"></span>
                </div>

                <div class="khomanguon-r2-progress" aria-hidden="true">
                    <div id="khomanguon-r2-progress-bar"></div>
                </div>
            </div>
        </div>

        <div class="khomanguon-admin-card">
            <div class="khomanguon-admin-card__header">
                <div>
                    <h1><?php echo esc_html__('Quản lý tệp R2', 'khomanguon-transaction-manager'); ?></h1>
                    <p><?php echo esc_html__('Danh sách 100 object mới nhất theo prefix. Bạn có thể hiển thị/copy object key hoặc file path của từng tệp.', 'khomanguon-transaction-manager'); ?></p>
                </div>
            </div>
            <div class="khomanguon-admin-card__body">
                <div class="khomanguon-r2-filter">
                    <input type="text" id="khomanguon-r2-list-prefix" class="form-control" placeholder="<?php echo esc_attr__('Lọc theo prefix, ví dụ source-code/', 'khomanguon-transaction-manager'); ?>">
                    <button type="button" class="button button-secondary" id="khomanguon-r2-refresh">
                        <?php echo esc_html__('Tải danh sách', 'khomanguon-transaction-manager'); ?>
                    </button>
                    <label class="khomanguon-r2-toggle">
                        <input type="checkbox" id="khomanguon-r2-show-file-path" checked>
                        <?php echo esc_html__('Hiển thị file path', 'khomanguon-transaction-manager'); ?>
                    </label>
                </div>

                <div class="khomanguon-table-wrap">
                    <table class="table table-striped table-hover khomanguon-admin-table khomanguon-r2-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Object key', 'khomanguon-transaction-manager'); ?></th>
                                <th class="khomanguon-r2-path-column"><?php echo esc_html__('File path', 'khomanguon-transaction-manager'); ?></th>
                                <th><?php echo esc_html__('Dung lượng', 'khomanguon-transaction-manager'); ?></th>
                                <th><?php echo esc_html__('Cập nhật', 'khomanguon-transaction-manager'); ?></th>
                                <th><?php echo esc_html__('Thao tác', 'khomanguon-transaction-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="khomanguon-r2-files-body">
                            <tr>
                                <td colspan="5"><?php echo esc_html__('Đang tải danh sách tệp...', 'khomanguon-transaction-manager'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
