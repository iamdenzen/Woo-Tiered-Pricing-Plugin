<?php

if (!defined('ABSPATH')) {
    exit;
}

class CX_Tiered_Pricing_Prices {

    public static function init() {
        add_action('plugins_loaded', [__CLASS__, 'register_pricing_engine']);
    }

    public static function register_pricing_engine() {
        if (!class_exists('CX_Pricing_Engine')) {
            return;
        }

        CX_Pricing_Engine::register('tier', function ($price, $ctx) {
            $tier_price = self::get_tier_price(
                $ctx['product_id'],
                $ctx['variation_id'],
                $ctx['qty']
            );

            return $tier_price !== null && $tier_price !== false
                ? (float) $tier_price
                : $price;
        }, 10);
    }

    public static function get_tier_price($product_id, $variation_id = 0, $qty = 1) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT price
             FROM " . CX_Tiered_Pricing_DB::table() . "
             WHERE product_id = %d
             AND variation_id = %d
             AND qty <= %d
             ORDER BY qty DESC
             LIMIT 1",
            (int) $product_id,
            (int) $variation_id,
            (int) $qty
        ));
    }

    public static function get_qty_tiers($product_id, $variation_id = null) {
        global $wpdb;

        if ($variation_id) {
            return $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT qty
                 FROM " . CX_Tiered_Pricing_DB::table() . "
                 WHERE product_id = %d
                 AND variation_id = %d
                 ORDER BY qty ASC",
                (int) $product_id,
                (int) $variation_id
            ));
        }

        return $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT qty
             FROM " . CX_Tiered_Pricing_DB::table() . "
             WHERE product_id = %d
             ORDER BY qty ASC",
            (int) $product_id
        ));
    }

    public static function get_lowest_tier_qty($product_id, $variation_id = null) {
        $tiers = self::get_qty_tiers($product_id, $variation_id);

        foreach ($tiers as $qty) {
            if ((int) $qty > 1) {
                return (int) $qty;
            }
        }

        return null;
    }

    public static function get_all_tiers($product_id, $variation_id = 0) {
        global $wpdb;

        if ($variation_id) {
            $variation_tiers = $wpdb->get_results($wpdb->prepare(
                "SELECT qty, price
                 FROM " . CX_Tiered_Pricing_DB::table() . "
                 WHERE product_id = %d
                 AND variation_id = %d
                 ORDER BY qty ASC",
                (int) $product_id,
                (int) $variation_id
            ), ARRAY_A);

            if (!empty($variation_tiers)) {
                return $variation_tiers;
            }
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT qty, price
             FROM " . CX_Tiered_Pricing_DB::table() . "
             WHERE product_id = %d
             AND variation_id = 0
             ORDER BY qty ASC",
            (int) $product_id
        ), ARRAY_A);
    }

    public static function replace_tiers($product_id, $variation_id, array $tiers) {
        global $wpdb;

        $product_id = (int) $product_id;
        $variation_id = (int) $variation_id;

        $clean_tiers = [];

        foreach ($tiers as $tier) {
            if (
                empty($tier['qty']) ||
                empty($tier['price']) ||
                (int) $tier['qty'] <= 0 ||
                (float) $tier['price'] <= 0
            ) {
                continue;
            }

            $clean_tiers[] = [
                'qty'   => (int) $tier['qty'],
                'price' => (float) $tier['price'],
            ];
        }

        $wpdb->delete(CX_Tiered_Pricing_DB::table(), [
            'product_id'   => $product_id,
            'variation_id' => $variation_id,
        ]);

        foreach ($clean_tiers as $tier) {
            $wpdb->insert(CX_Tiered_Pricing_DB::table(), [
                'product_id'   => $product_id,
                'variation_id' => $variation_id,
                'qty'          => $tier['qty'],
                'price'        => $tier['price'],
            ]);
        }

        self::sync_woocommerce_price($product_id, $variation_id, $clean_tiers);
    }

    private static function sync_woocommerce_price($product_id, $variation_id, array $tiers) {
        if (empty($tiers)) {
            return;
        }

        $lowest_price = min(array_column($tiers, 'price'));

        if ($variation_id) {
            $product = wc_get_product($variation_id);

            if ($product) {
                $product->set_regular_price($lowest_price);
                $product->set_price($lowest_price);
                $product->save();
            }

            WC_Product_Variable::sync($product_id);

            return;
        }

        $product = wc_get_product($product_id);

        if ($product) {
            $product->set_regular_price($lowest_price);
            $product->set_price($lowest_price);
            $product->save();
        }
    }
}