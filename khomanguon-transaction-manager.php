<?php
/**
 * Plugin Name: Khomanguon Transaction Manager
 * Description: Quản lý giao dịch, ví @Cash, mở khóa S3 và cấu hình cloud cho KHOMANGUON.ORG.
 * Version: 1.0.0.b
 * Author: KHOMANGUON.ORG
 * Text Domain: khomanguon-transaction-manager
 * Update URI: https://github.com/nhockool1002/khomanguon-transaction-manager.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KHOMANGUON_TRANSACTION_MANAGER_VERSION', '1.0.0.b');
define('KHOMANGUON_TRANSACTION_MANAGER_FILE', __FILE__);
define('KHOMANGUON_TRANSACTION_MANAGER_PATH', plugin_dir_path(__FILE__));
define('KHOMANGUON_TRANSACTION_MANAGER_URL', plugin_dir_url(__FILE__));

if (!defined('COIN_PREFIX')) {
    define('COIN_PREFIX', '@Cash');
}

if (!defined('MOMO_QR_CODE')) {
    define('MOMO_QR_CODE', trailingslashit(get_template_directory_uri()) . 'assets/images/momo_qr.jpg');
}

if (!defined('VCB_QR_CODE')) {
    define('VCB_QR_CODE', trailingslashit(get_template_directory_uri()) . 'assets/images/vcb_qr.jpg');
}

if (!defined('TPB_QR_CODE')) {
    define('TPB_QR_CODE', trailingslashit(get_template_directory_uri()) . 'assets/images/tpb_qr.jpg');
}

if (!defined('TIMO_QR_CODE')) {
    define('TIMO_QR_CODE', trailingslashit(get_template_directory_uri()) . 'assets/images/timo_qr.jpg');
}

$khomanguon_autoload = ABSPATH . 'vendor/autoload.php';
if (file_exists($khomanguon_autoload)) {
    require_once $khomanguon_autoload;
}

require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/Installer.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/PointsRepository.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/R2ClientFactory.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/Mail/MailjetMailer.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/GitHubUpdater.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/Plugin.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/Admin/Menu.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/Admin/TransactionsPage.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/Admin/SettingsPage.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/Admin/R2UploadPage.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/Ajax/UpdateOrderStatus.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/Ajax/SignedS3Url.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/Ajax/R2Upload.php';
require_once KHOMANGUON_TRANSACTION_MANAGER_PATH . 'includes/PostMeta/S3MetaBox.php';

new Khomanguon\TransactionManager\GitHubUpdater(KHOMANGUON_TRANSACTION_MANAGER_FILE, KHOMANGUON_TRANSACTION_MANAGER_VERSION);

register_activation_hook(__FILE__, array('Khomanguon\\TransactionManager\\Installer', 'activate'));

function khomanguon_transactions()
{
    return Khomanguon\TransactionManager\Plugin::instance();
}

add_action('plugins_loaded', 'khomanguon_transactions');

if (!function_exists('addPointToUser')) {
    function addPointToUser($user_id, $amount, $operation, $reason)
    {
        return khomanguon_transactions()->repository()->adjust_points($user_id, $amount, $operation, $reason);
    }
}

if (!function_exists('addPointOrders')) {
    function addPointOrders($user_id, $amount, $method, $payment_orders_code, $payment_status)
    {
        return khomanguon_transactions()->repository()->create_order($user_id, $amount, $method, $payment_orders_code, $payment_status);
    }
}

if (!function_exists('getUserPoints')) {
    function getUserPoints($user_id)
    {
        return khomanguon_transactions()->repository()->get_user_points($user_id);
    }
}

if (!function_exists('generateMethodName')) {
    function generateMethodName($methodId, $blank = true)
    {
        return khomanguon_transactions()->method_name($methodId, $blank);
    }
}

if (!function_exists('generateStatusName')) {
    function generateStatusName($statusId, $blank = true)
    {
        return khomanguon_transactions()->status_name($statusId, $blank);
    }
}

if (!function_exists('khomanguon_render_download_box')) {
    function khomanguon_render_download_box($post_id = 0)
    {
        khomanguon_transactions()->render_download_box($post_id);
    }
}

if (!function_exists('khomanguon_transactions_handle_payment_submission')) {
    function khomanguon_transactions_handle_payment_submission($user_id, $user_name)
    {
        return khomanguon_transactions()->handle_payment_submission($user_id, $user_name);
    }
}

if (!function_exists('khomanguon_transactions_get_user_orders')) {
    function khomanguon_transactions_get_user_orders($user_id)
    {
        return khomanguon_transactions()->repository()->get_user_orders($user_id);
    }
}

if (!function_exists('khomanguon_transactions_get_user_history')) {
    function khomanguon_transactions_get_user_history($user_id)
    {
        return khomanguon_transactions()->repository()->get_user_history($user_id);
    }
}
