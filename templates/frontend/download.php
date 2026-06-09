<?php

if (!defined('ABSPATH')) {
    exit;
}

$key = get_post_meta($post_id, 'custom_key', true);
$cash = get_post_meta($post_id, 'custom_cash', true);

?>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 pl-0">
            <h4 style="color:red; font-weight: bold;">Bạn có thể tải về với một liên kết duy nhất bên dưới 🚀</h4>
            <h5 style="color:black; font-weight: bold;">➡ Liên kết tải trực tiếp, không quảng cáo.</h5>
            <h5 style="color:black; font-weight: bold;">➡ Liên kết tải đơn luồng, tốc độ tải không giới hạn.</h5>
        </div>
        <div class="col-sm-12 pl-0">
            <?php if (is_numeric($cash) && $cash !== '' && $key !== '') : ?>
                <?php $display_cash = number_format((float) $cash) . ' ' . COIN_PREFIX; ?>
                <button class="btn btn-success w-100 khomanguon-unlock-btn" data-post_id="<?php echo esc_attr($post_id); ?>">
                    🔐 MỞ KHOÁ LIÊN KẾT TẢI - [Bạn cần có <?php echo esc_html($display_cash); ?>]
                </button>
            <?php else : ?>
                <div style="background-color: #dc3545; color: #fff; padding: 10px 15px; border-radius: 5px; text-align: center; font-weight: bold; width: 100%;">
                    ⏳ LIÊN KẾT TẢI VỀ ĐANG ĐƯỢC CHUẨN BỊ
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
