<?php

if (!defined('ABSPATH')) {
    exit;
}

$key = trim((string) get_post_meta($post_id, 'custom_key', true));
$cash = get_post_meta($post_id, 'custom_cash', true);
$has_download_key = $key !== '';
$has_price = $cash !== '' && is_numeric($cash);
$is_download_ready = $has_download_key && $has_price;
$display_cash = $has_price ? number_format_i18n((float) $cash) . ' ' . COIN_PREFIX : '';
$file_details = $has_download_key
    ? khomanguon_transactions()->download_file_details($post_id)
    : array(
        'name' => '',
        'size' => 0,
        'size_label' => '',
    );

if (!$has_download_key && !$has_price) {
    $pending_message = __('Liên kết tải và số @Cash đang được chuẩn bị.', 'khomanguon-transaction-manager');
} elseif (!$has_download_key) {
    $pending_message = __('Liên kết tải đang được chuẩn bị.', 'khomanguon-transaction-manager');
} else {
    $pending_message = __('Số @Cash mở khóa đang được cập nhật.', 'khomanguon-transaction-manager');
}

?>
<div class="container-fluid khomanguon-download-box">
    <div class="row">
        <div class="col-sm-12 pl-0">
            <div class="khomanguon-download-hero">
                <div>
                    <span class="khomanguon-download-eyebrow"><?php echo esc_html__('Tải về siêu tốc', 'khomanguon-transaction-manager'); ?></span>
                    <h4 class="khomanguon-download-title">Bạn có thể tải về với một liên kết duy nhất bên dưới 🚀</h4>
                    <div class="khomanguon-download-benefits">
                        <span>➡ <?php echo esc_html__('Liên kết tải trực tiếp, không quảng cáo.', 'khomanguon-transaction-manager'); ?></span>
                        <span>➡ <?php echo esc_html__('Liên kết tải đơn luồng, tốc độ tải không giới hạn.', 'khomanguon-transaction-manager'); ?></span>
                    </div>
                </div>
                <div class="khomanguon-price-card <?php echo $has_price ? '' : 'is-pending'; ?>">
                    <span><?php echo esc_html__('Chi phí mở khóa', 'khomanguon-transaction-manager'); ?></span>
                    <strong><?php echo $has_price ? esc_html($display_cash) : esc_html__('Đang cập nhật', 'khomanguon-transaction-manager'); ?></strong>
                </div>
            </div>
        </div>
        <div class="col-sm-12 pl-0">
            <?php if (!empty($file_details['name'])) : ?>
                <div class="khomanguon-file-info" aria-label="<?php echo esc_attr__('Thông tin file tải về', 'khomanguon-transaction-manager'); ?>">
                    <div class="khomanguon-file-info__icon" aria-hidden="true">📦</div>
                    <div class="khomanguon-file-info__item khomanguon-file-info__item--name">
                        <span><?php echo esc_html__('Tên file', 'khomanguon-transaction-manager'); ?></span>
                        <strong><?php echo esc_html($file_details['name']); ?></strong>
                    </div>
                    <div class="khomanguon-file-info__item">
                        <span><?php echo esc_html__('Dung lượng', 'khomanguon-transaction-manager'); ?></span>
                        <strong><?php echo esc_html($file_details['size_label']); ?></strong>
                    </div>
                </div>
            <?php else : ?>
                <div class="khomanguon-file-info khomanguon-file-info--pending">
                    <div class="khomanguon-file-info__icon" aria-hidden="true">⏳</div>
                    <div class="khomanguon-file-info__item khomanguon-file-info__item--name">
                        <span><?php echo esc_html__('Thông tin file', 'khomanguon-transaction-manager'); ?></span>
                        <strong><?php echo esc_html__('Đang được chuẩn bị', 'khomanguon-transaction-manager'); ?></strong>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-sm-12 pl-0">
            <?php if ($is_download_ready) : ?>
                <button class="btn btn-success w-100 khomanguon-unlock-btn khomanguon-rainbow-btn" data-post_id="<?php echo esc_attr($post_id); ?>">
                    🔐 MỞ KHOÁ LIÊN KẾT TẢI NGAY
                </button>
            <?php else : ?>
                <div class="khomanguon-download-pending">
                    ⏳ <?php echo esc_html($pending_message); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
