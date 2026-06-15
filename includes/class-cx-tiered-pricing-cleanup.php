<?php

if (!defined('ABSPATH')) {
    exit;
}

class CX_Tiered_Pricing_Cleanup {

    public static function init() {
        add_action('before_delete_post', [__CLASS__, 'delete_product_tiers']);
    }

    public static function delete_product_tiers($post_id) {
        $post_type = get_post_type($post_id);

        if (!in_array($post_type, ['product', 'product_variation'], true)) {
            return;
        }

        global $wpdb;

        if ($post_type === 'product') {
            $wpdb->delete(CX_Tiered_Pricing_DB::table(), [
                'product_id' => (int) $post_id,
            ]);

            return;
        }

        $wpdb->delete(CX_Tiered_Pricing_DB::table(), [
            'variation_id' => (int) $post_id,
        ]);
    }
}