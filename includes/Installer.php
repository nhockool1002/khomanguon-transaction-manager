<?php

namespace Khomanguon\TransactionManager;

if (!defined('ABSPATH')) {
    exit;
}

class Installer
{
    const DB_VERSION = '1.2.0';

    public static function activate()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $point_table = $wpdb->prefix . 'point';
        $history_table = $wpdb->prefix . 'point_history';
        $orders_table = $wpdb->prefix . 'point_orders';
        $downloads_table = $wpdb->prefix . 'khomanguon_file_downloads';
        $file_meta_table = $wpdb->prefix . 'khomanguon_file_meta';

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

        dbDelta("CREATE TABLE $downloads_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            provider varchar(10) NOT NULL,
            object_key varchar(1024) NOT NULL,
            file_path varchar(1200) NOT NULL,
            cash_amount bigint(20) unsigned NOT NULL DEFAULT 0,
            post_title text NOT NULL,
            downloaded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY provider_object_key (provider, object_key(191)),
            KEY user_id (user_id),
            KEY post_id (post_id),
            KEY downloaded_at (downloaded_at)
        ) $charset_collate;");

        dbDelta("CREATE TABLE $file_meta_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            provider varchar(10) NOT NULL,
            object_key varchar(1024) NOT NULL,
            display_name varchar(1024) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY provider_object_key (provider, object_key(191)),
            KEY provider (provider)
        ) $charset_collate;");

        update_option('khomanguon_transaction_manager_db_version', self::DB_VERSION);
    }

    public static function maybe_upgrade()
    {
        if (get_option('khomanguon_transaction_manager_db_version') !== self::DB_VERSION) {
            self::activate();
        }
    }
}
