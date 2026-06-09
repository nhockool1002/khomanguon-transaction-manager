<?php

namespace Khomanguon\TransactionManager\Mail;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class MailjetMailer
{
    public function send($to_email, $to_name, $subject, $text_part, $html_part, $from_email = 'admin@khomanguon.org', $from_name = '[KHOMANGUON] Notification')
    {
        $api_key = get_option('api_mailjet_key');
        $api_secret = get_option('api_mailjet_secret');
        $api_sender = get_option('api_mailjet_sender');

        if (empty($api_key) || empty($api_secret)) {
            return new WP_Error('mailjet_config_error', __('Mailjet API credentials are not configured.', 'khomanguon-transaction-manager'));
        }

        if (empty($from_email)) {
            $from_email = !empty($api_sender) ? $api_sender : 'admin@khomanguon.org';
        }

        $payload = array(
            'Messages' => array(
                array(
                    'From' => array(
                        'Email' => $from_email,
                        'Name' => $from_name,
                    ),
                    'To' => array(
                        array(
                            'Email' => $to_email,
                            'Name' => $to_name,
                        ),
                    ),
                    'Subject' => $subject,
                    'TextPart' => $text_part,
                    'HTMLPart' => $html_part,
                ),
            ),
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailjet.com/v3.1/send');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, wp_json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_USERPWD, "$api_key:$api_secret");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return new WP_Error('mailjet_curl_error', __('cURL error: ', 'khomanguon-transaction-manager') . $error);
        }

        $response_data = json_decode($response, true);

        if ($http_code >= 200 && $http_code < 300 && isset($response_data['Messages'][0]['Status']) && $response_data['Messages'][0]['Status'] === 'success') {
            return true;
        }

        $error_message = isset($response_data['Messages'][0]['Errors'][0]['ErrorMessage'])
            ? $response_data['Messages'][0]['Errors'][0]['ErrorMessage']
            : __('Unknown error occurred.', 'khomanguon-transaction-manager');

        return new WP_Error('mailjet_api_error', __('Mailjet API error: ', 'khomanguon-transaction-manager') . $error_message);
    }

    public function send_payment_created_notice($user_name, $amount, $method_name, $payment_code)
    {
        $user_name = esc_html($user_name);
        $method_name = esc_html($method_name);
        $payment_code = esc_html($payment_code);
        $subject = "User [$user_name] đã thực hiện một khoản thanh toán tại KHOMANGUON.ORG [" . date('d/m/Y H:i:s') . ']';
        $text_part = "Xin chào NhutNguyen, $user_name đã thực hiện một khoản thanh toán tại KHOMANGUON.ORG.";
        $html_part = "
            <h3>Xin chào <span style='color:red;'>NhutNguyen</span>,</h3>
            <p>Người dùng <span style='color:blue; font-weight: bolder;'>[$user_name]</span> đã thực hiện một khoản thanh toán tại <a href=\"https://khomanguon.org/\">KHOMANGUON.ORG</a> với thông tin như sau:</p>
            <table style=\"border-collapse: collapse; width: 100%; max-width: 600px; font-family: Arial, sans-serif; font-size: 14px; border: 1px solid #ddd;\">
                <thead>
                    <tr style=\"background-color: #0073aa; color: #ffffff; text-align: center;\">
                        <th style=\"padding: 10px; border: 1px solid #ddd; font-weight: bold;\">User</th>
                        <th style=\"padding: 10px; border: 1px solid #ddd; font-weight: bold;\">Số tiền nạp</th>
                        <th style=\"padding: 10px; border: 1px solid #ddd; font-weight: bold;\">Phương thức thanh toán</th>
                        <th style=\"padding: 10px; border: 1px solid #ddd; font-weight: bold;\">Mã giao dịch</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style=\"background-color: #f9f9f9; text-align: center;\">
                        <td style=\"padding: 10px; border: 1px solid #ddd;\">$user_name</td>
                        <td style=\"padding: 10px; border: 1px solid #ddd;\">" . number_format((float) $amount) . " VNĐ</td>
                        <td style=\"padding: 10px; border: 1px solid #ddd;\">$method_name</td>
                        <td style=\"padding: 10px; border: 1px solid #ddd;\">$payment_code</td>
                    </tr>
                </tbody>
            </table>
            <br />
            <p>Vui lòng truy cập trang quản trị để đánh giá.</p>
        ";

        return $this->send('nhut.nguyenminh.it@gmail.com', 'NhutNguyen', $subject, $text_part, $html_part);
    }

    public function send_payment_status_notice($to_email, $to_name, $user_name, $amount, $method_name, $payment_code, $status, $approver)
    {
        $to_name = esc_html($to_name);
        $user_name = esc_html($user_name);
        $method_name = esc_html($method_name);
        $payment_code = esc_html($payment_code);
        $approver = esc_html($approver);
        $subject = "Cập nhật khoản thanh toán của $to_name tại KHOMANGUON.ORG [" . date('d/m/Y H:i:s') . ']';
        $text_part = '';
        $status_text = '';

        if ((int) $status === -1) {
            $status_text = "<span style='color:red;font-weight: bolder'>Rejected</span>";
        }

        if ((int) $status === 1) {
            $status_text = "<span style='color:green;font-weight: bolder'>Approved</span>";
        }

        $html_part = "
            <h3>Xin chào <span style='color:red;'>$user_name</span>,</h3>
            <p>Khoản thanh toán của bạn tại <a href=\"https://khomanguon.org/\">KHOMANGUON.ORG</a> được cập nhật trạng thái kết quả như sau:</p>
            <table style=\"border-collapse: collapse; width: 100%; max-width: 600px; font-family: Arial, sans-serif; font-size: 14px; border: 1px solid #ddd;\">
                <thead>
                    <tr style=\"background-color:rgb(236, 87, 0); color: #ffffff; text-align: center;\">
                        <th style=\"padding: 10px; border: 1px solid #ddd; font-weight: bold;\">User</th>
                        <th style=\"padding: 10px; border: 1px solid #ddd; font-weight: bold;\">Số tiền nạp</th>
                        <th style=\"padding: 10px; border: 1px solid #ddd; font-weight: bold;\">Phương thức thanh toán</th>
                        <th style=\"padding: 10px; border: 1px solid #ddd; font-weight: bold;\">Mã giao dịch</th>
                        <th style=\"padding: 10px; border: 1px solid #ddd; font-weight: bold;\">Trạng thái</th>
                        <th style=\"padding: 10px; border: 1px solid #ddd; font-weight: bold;\">Người xét duyệt</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style=\"background-color: #f9f9f9; text-align: center;\">
                        <td style=\"padding: 10px; border: 1px solid #ddd;\">$user_name</td>
                        <td style=\"padding: 10px; border: 1px solid #ddd;\">" . number_format((float) $amount) . " VNĐ</td>
                        <td style=\"padding: 10px; border: 1px solid #ddd;\">$method_name</td>
                        <td style=\"padding: 10px; border: 1px solid #ddd;\">$payment_code</td>
                        <td style=\"padding: 10px; border: 1px solid #ddd;\">$status_text</td>
                        <td style=\"padding: 10px; border: 1px solid #ddd;\">$approver</td>
                    </tr>
                </tbody>
            </table>
            <br />
            <p>~</p>
        ";

        return $this->send($to_email, $to_name, $subject, $text_part, $html_part);
    }
}
