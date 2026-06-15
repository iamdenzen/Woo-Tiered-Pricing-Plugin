<?php

if (!defined('ABSPATH')) {
    exit;
}

class CX_Tiered_Pricing_DB {

    public static function table() {
        global $wpdb;

        return $wpdb->prefix . 'cx_tier_prices';
    }

    public static function install() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::table();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            variation_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            qty INT UNSIGNED NOT NULL,
            price DECIMAL(12,4) NOT NULL,

            PRIMARY KEY (id),
            UNIQUE KEY unique_tier (product_id, variation_id, qty),
            KEY product_idx (product_id),
            KEY variation_idx (variation_id)
        ) $charset_collate;";

        dbDelta($sql);
    }
}