<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="container-fluid admin-point-wrapper m-2">
    <div class="row">
        <div class="col-sm-12 pt-3">
            <h2><?php echo esc_html__('Danh sách Giao Dịch', 'khomanguon-transaction-manager'); ?></h2>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 pl-3">
            <?php if (!empty($results)) : ?>
                <table id="table_history_payment" class="display">
                    <thead>
                        <tr>
                            <th width="210px"><?php echo esc_html__('Mã giao dịch', 'khomanguon-transaction-manager'); ?></th>
                            <th width="150px"><?php echo esc_html__('Số tiền', 'khomanguon-transaction-manager'); ?></th>
                            <th><?php echo esc_html__('Phương thức thanh toán', 'khomanguon-transaction-manager'); ?></th>
                            <th width="210px"><?php echo esc_html__('Thời gian giao dịch', 'khomanguon-transaction-manager'); ?></th>
                            <th width="210px"><?php echo esc_html__('Trạng thái', 'khomanguon-transaction-manager'); ?></th>
                            <th width="210px"><?php echo esc_html__('Actions', 'khomanguon-transaction-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result) : ?>
                            <tr>
                                <td><?php echo esc_html($result->payment_orders_code); ?></td>
                                <td>
                                    <span class="amount-charge">
                                        <?php echo esc_html(number_format((float) $result->amount) . 'VNĐ'); ?>
                                    </span>
                                </td>
                                <td><?php echo wp_kses_post(generateMethodName($result->method)); ?></td>
                                <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($result->timestamp))); ?></td>
                                <td style="padding-right: 15px;">
                                    <select class="form-control method khomanguon-status-select-<?php echo esc_attr($result->id); ?>" name="method" required style="border: none; outline: none;">
                                        <option value="-1" style="background-color: red; color: white; font-weight:bold;" <?php selected((int) $result->payment_status, -1); ?>>Thất bại</option>
                                        <option value="0" style="background-color: yellow; color: black; font-weight:bold;" <?php selected((int) $result->payment_status, 0); ?>>Đang xử lý</option>
                                        <option value="1" style="background-color: green; color: white; font-weight:bold;" <?php selected((int) $result->payment_status, 1); ?>>Thành công</option>
                                    </select>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-primary khomanguon-update-payment"
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
            <?php else : ?>
                <h4 style="color: red; font-weight: bolder;"><?php echo esc_html__('Không có lịch sử giao dịch nào!', 'khomanguon-transaction-manager'); ?></h4>
            <?php endif; ?>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-sm-12 pt-3">
            <h2><?php echo esc_html__('Lịch sử Giao Dịch', 'khomanguon-transaction-manager'); ?></h2>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 pl-3">
            <?php if (!empty($rs_history)) : ?>
                <table id="table_history_payment_2" class="display">
                    <thead>
                        <tr>
                            <th width="40px"><?php echo esc_html__('ID', 'khomanguon-transaction-manager'); ?></th>
                            <th width="100px"><?php echo esc_html__('User ID', 'khomanguon-transaction-manager'); ?></th>
                            <th><?php echo esc_html__('User Name', 'khomanguon-transaction-manager'); ?></th>
                            <th width="800px"><?php echo esc_html__('Payment Info', 'khomanguon-transaction-manager'); ?></th>
                            <th width="210px"><?php echo esc_html__('Timestamp', 'khomanguon-transaction-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rs_history as $item_hs) : ?>
                            <?php $user_profile_link = get_edit_user_link($item_hs->user_id); ?>
                            <tr>
                                <td><?php echo esc_html($item_hs->id); ?></td>
                                <td><?php echo esc_html($item_hs->user_id); ?></td>
                                <td>
                                    <b><a href="<?php echo esc_url($user_profile_link); ?>" target="_blank"><?php echo esc_html($item_hs->display_name); ?></a></b>
                                </td>
                                <td>
                                    <span class="operator-<?php echo esc_attr($item_hs->operation === '+' ? 'green' : 'red'); ?>">
                                        <?php echo esc_html('[' . $item_hs->operation . $item_hs->amount . '] ' . $item_hs->reason); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($item_hs->timestamp))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <h4 style="color: red; font-weight: bolder;"><?php echo esc_html__('Không có lịch sử nào!', 'khomanguon-transaction-manager'); ?></h4>
            <?php endif; ?>
        </div>
    </div>
</div>
