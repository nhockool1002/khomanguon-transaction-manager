<?php

namespace Khomanguon\TransactionManager;

if (!defined('ABSPATH')) {
    exit;
}

class Installer
{
    public static function activate()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $point_table = $wpdb->prefix . 'point';
        $history_table = $wpdb->prefix . 'point_history';
        $orders_table = $wpdb->prefix . 'point_orders';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $point_table)) !== $point_table) {
            dbDelta("CREATE TABLE $point_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id mediumint(9) NOT NULL,
                point_amount int NOT NULL,
                UNIQUE (user_id),
                PRIMARY KEY  (id)
            ) $charset_collate;");
        }

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $history_table)) !== $history_table) {
            dbDelta("CREATE TABLE $history_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id mediumint(9) NOT NULL,
                operation varchar(1) NOT NULL,
                amount bigint NOT NULL,
                reason text NOT NULL,
                timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;");
        }

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $orders_table)) !== $orders_table) {
            dbDelta("CREATE TABLE $orders_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id mediumint(9) NOT NULL,
                amount bigint NOT NULL,
                method text NOT NULL,
                payment_orders_code text NOT NULL,
                payment_status int NOT NULL,
                flg_completed int NOT NULL DEFAULT 0,
                timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;");
        }
    }
}
