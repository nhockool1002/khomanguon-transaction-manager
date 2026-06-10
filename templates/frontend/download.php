<?php

if (!defined('ABSPATH')) {
    exit;
}

$key = get_post_meta($post_id, 'custom_key', true);
$cash = get_post_meta($post_id, 'custom_cash', true);
$file_details = khomanguon_transactions()->download_file_details($post_id);

?>
<div class="container-fluid khomanguon-download-box">
    <div class="row">
        <div class="col-sm-12 pl-0">
            <h4 class="khomanguon-download-title">Bạn có thể tải về với một liên kết duy nhất bên dưới 🚀</h4>
            <h5 class="khomanguon-download-note">➡ Liên kết tải trực tiếp, không quảng cáo.</h5>
            <h5 class="khomanguon-download-note">➡ Liên kết tải đơn luồng, tốc độ tải không giới hạn.</h5>
        </div>
        <?php if (!empty($file_details['name'])) : ?>
            <div class="col-sm-12 pl-0">
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
            </div>
        <?php endif; ?>
        <div class="col-sm-12 pl-0">
            <?php if (is_numeric($cash) && $cash !== '' && $key !== '') : ?>
                <?php $display_cash = number_format((float) $cash) . ' ' . COIN_PREFIX; ?>
                <button class="btn btn-success w-100 khomanguon-unlock-btn khomanguon-rainbow-btn" data-post_id="<?php echo esc_attr($post_id); ?>">
                    🔐 MỞ KHOÁ LIÊN KẾT TẢI - [Bạn cần có <?php echo esc_html($display_cash); ?>]
                </button>
            <?php else : ?>
                <div class="khomanguon-download-pending">
                    ⏳ LIÊN KẾT TẢI VỀ ĐANG ĐƯỢC CHUẨN BỊ
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
