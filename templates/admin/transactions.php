<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap khomanguon-admin-page admin-point-wrapper">
    <div class="khomanguon-admin-card">
        <div class="khomanguon-admin-card__header">
            <div>
                <h1><?php echo esc_html__('Danh sách Giao Dịch', 'khomanguon-transaction-manager'); ?></h1>
                <p><?php echo esc_html__('Theo dõi và cập nhật trạng thái các yêu cầu nạp tiền.', 'khomanguon-transaction-manager'); ?></p>
            </div>
            <span class="khomanguon-admin-count">
                <?php echo esc_html(sprintf(_n('%s giao dịch', '%s giao dịch', count($results), 'khomanguon-transaction-manager'), number_format_i18n(count($results)))); ?>
            </span>
        </div>
        <div class="khomanguon-admin-card__body">
            <?php if (!empty($results)) : ?>
                <div class="khomanguon-table-wrap">
                    <table id="table_history_payment" class="table table-striped table-hover khomanguon-admin-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Mã giao dịch', 'khomanguon-transaction-manager'); ?></th>
                                <th><?php echo esc_html__('Số tiền', 'khomanguon-transaction-manager'); ?></th>
                                <th><?php echo esc_html__('Phương thức thanh toán', 'khomanguon-transaction-manager'); ?></th>
                                <th><?php echo esc_html__('Thời gian giao dịch', 'khomanguon-transaction-manager'); ?></th>
                                <th><?php echo esc_html__('Trạng thái', 'khomanguon-transaction-manager'); ?></th>
                                <th><?php echo esc_html__('Thao tác', 'khomanguon-transaction-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($result->payment_orders_code); ?></strong></td>
                                    <td>
                                        <span class="amount-charge">
                                            <?php echo esc_html(number_format((float) $result->amount) . ' VNĐ'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo wp_kses_post(generateMethodName($result->method)); ?></td>
                                    <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($result->timestamp))); ?></td>
                                    <td>
                                        <select class="form-control method khomanguon-status-select-<?php echo esc_attr($result->id); ?>" name="method" required>
                                            <option value="-1" <?php selected((int) $result->payment_status, -1); ?>>Thất bại</option>
                                            <option value="0" <?php selected((int) $result->payment_status, 0); ?>>Đang xử lý</option>
                                            <option value="1" <?php selected((int) $result->payment_status, 1); ?>>Thành công</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button
                                            class="btn btn-primary btn-sm khomanguon-update-payment"
                                            data-payment_id="<?php echo esc_attr($result->id); ?>"
                                            <?php disabled((int) $result->flg_completed, 1); ?>
                                        >
                                            <?php echo esc_html__('Cập nhật', 'khomanguon-transaction-manager'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="khomanguon-empty-state"><?php echo esc_html__('Không có giao dịch nào!', 'khomanguon-transaction-manager'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="khomanguon-admin-card">
        <div class="khomanguon-admin-card__header">
            <div>
                <h1><?php echo esc_html__('Lịch sử Giao Dịch', 'khomanguon-transaction-manager'); ?></h1>
                <p><?php echo esc_html__('Nhật ký cộng/trừ ví @Cash của người dùng.', 'khomanguon-transaction-manager'); ?></p>
            </div>
            <span class="khomanguon-admin-count">
                <?php echo esc_html(sprintf(_n('%s lịch sử', '%s lịch sử', count($rs_history), 'khomanguon-transaction-manager'), number_format_i18n(count($rs_history)))); ?>
            </span>
        </div>
        <div class="khomanguon-admin-card__body">
            <?php if (!empty($rs_history)) : ?>
                <div class="khomanguon-table-wrap">
                    <table id="table_history_payment_2" class="table table-striped table-hover khomanguon-admin-table khomanguon-history-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('ID', 'khomanguon-transaction-manager'); ?></th>
                                <th><?php echo esc_html__('User ID', 'khomanguon-transaction-manager'); ?></th>
                                <th><?php echo esc_html__('User Name', 'khomanguon-transaction-manager'); ?></th>
                                <th><?php echo esc_html__('Payment Info', 'khomanguon-transaction-manager'); ?></th>
                                <th><?php echo esc_html__('Timestamp', 'khomanguon-transaction-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rs_history as $item_hs) : ?>
                                <?php $user_profile_link = get_edit_user_link($item_hs->user_id); ?>
                                <tr>
                                    <td><?php echo esc_html($item_hs->id); ?></td>
                                    <td><?php echo esc_html($item_hs->user_id); ?></td>
                                    <td>
                                        <a class="khomanguon-user-link" href="<?php echo esc_url($user_profile_link); ?>" target="_blank"><?php echo esc_html($item_hs->display_name); ?></a>
                                    </td>
                                    <td class="khomanguon-payment-info">
                                        <span class="operator-<?php echo esc_attr($item_hs->operation === '+' ? 'green' : 'red'); ?>">
                                            <?php echo esc_html('[' . $item_hs->operation . $item_hs->amount . '] ' . $item_hs->reason); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($item_hs->timestamp))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="khomanguon-empty-state"><?php echo esc_html__('Không có lịch sử nào!', 'khomanguon-transaction-manager'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
