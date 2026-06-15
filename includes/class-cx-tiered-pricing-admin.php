<?php

if (!defined('ABSPATH')) {
    exit;
}

class CX_Tiered_Pricing_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_post_cx_save_tiers', [__CLASS__, 'save_tiers']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function add_menu() {
        add_submenu_page(
            'edit.php?post_type=product',
            'Tiered Pricing',
            'Tiered Pricing',
            'manage_woocommerce',
            'cx-tiered-pricing',
            [__CLASS__, 'render_page']
        );
    }

    public static function enqueue_assets($hook) {
        if ($hook !== 'product_page_cx-tiered-pricing') {
            return;
        }

        wp_enqueue_script(
            'cx-tiered-pricing-admin',
            CX_TIERED_PRICING_URL . 'assets/admin.js',
            ['jquery'],
            '1.0',
            true
        );
    }

    public static function render_page() {
        ?>
        <div class="wrap">
            <h1>Tiered Pricing</h1>

            <form method="get">
                <input type="hidden" name="post_type" value="product">
                <input type="hidden" name="page" value="cx-tiered-pricing">

                <input
                    type="text"
                    name="sku"
                    placeholder="Enter SKU"
                    value="<?php echo esc_attr($_GET['sku'] ?? ''); ?>"
                >

                <button class="button">Load</button>
            </form>

            <br>

            <?php self::render_tier_form(); ?>
        </div>
        <?php
    }

    private static function render_tier_form() {
        if (empty($_GET['sku'])) {
            return;
        }

        $sku = sanitize_text_field(wp_unslash($_GET['sku']));
        $found_id = wc_get_product_id_by_sku($sku);

        if (!$found_id) {
            echo '<p>Product not found.</p>';
            return;
        }

        $product = wc_get_product($found_id);

        if (!$product) {
            echo '<p>Product could not be loaded.</p>';
            return;
        }

        $targets = self::get_editable_products($product);

        if (empty($targets)) {
            echo '<p>No editable products found.</p>';
            return;
        }

        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="cx_save_tiers">

            <?php wp_nonce_field('cx_save_tiers'); ?>

            <?php foreach ($targets as $index => $target): ?>
                <?php self::render_product_tiers($target, $index); ?>
            <?php endforeach; ?>

            <p>
                <button class="button button-primary">Save All</button>
            </p>
        </form>

        <?php self::render_row_template(); ?>
        <?php
    }

    private static function get_editable_products($product) {
        $targets = [];

        if ($product->is_type('simple')) {
            $targets[] = [
                'product_id'   => $product->get_id(),
                'variation_id' => 0,
                'product'      => $product,
            ];
        }

        if ($product->is_type('variation')) {
            $targets[] = [
                'product_id'   => $product->get_parent_id(),
                'variation_id' => $product->get_id(),
                'product'      => $product,
            ];
        }

        if ($product->is_type('variable')) {
            foreach ($product->get_children() as $variation_id) {
                $variation = wc_get_product($variation_id);

                if (!$variation) {
                    continue;
                }

                $targets[] = [
                    'product_id'   => $product->get_id(),
                    'variation_id' => $variation_id,
                    'product'      => $variation,
                ];
            }
        }

        return $targets;
    }

    private static function render_product_tiers($target, $index) {
        $product_id = (int) $target['product_id'];
        $variation_id = (int) $target['variation_id'];
        $product = $target['product'];

        $rows = CX_Tiered_Pricing_Prices::get_all_tiers($product_id, $variation_id);
        ?>
        <div class="cx-tier-box" style="margin-bottom:40px; padding:20px; border:1px solid #ddd; background:#fff;">
            <h2 style="margin-top:0;">
                <?php echo esc_html($product->get_name()); ?>
            </h2>

            <?php if ($variation_id): ?>
                <p>
                    <strong>Variation:</strong>
                    <?php
                    echo wp_kses_post(wc_get_formatted_variation(
                        $product,
                        true,
                        false,
                        true
                    ));
                    ?>
                </p>
            <?php endif; ?>

            <input
                type="hidden"
                name="tiers[<?php echo esc_attr($index); ?>][product_id]"
                value="<?php echo esc_attr($product_id); ?>"
            >

            <input
                type="hidden"
                name="tiers[<?php echo esc_attr($index); ?>][variation_id]"
                value="<?php echo esc_attr($variation_id); ?>"
            >

            <table class="widefat cx-tiered-pricing-table">
                <thead>
                    <tr>
                        <th width="120">Qty</th>
                        <th width="120">Price</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td>
                                <input
                                    type="number"
                                    name="tiers[<?php echo esc_attr($index); ?>][qty][]"
                                    value="<?php echo esc_attr($row['qty']); ?>"
                                >
                            </td>

                            <td>
                                <input
                                    type="text"
                                    name="tiers[<?php echo esc_attr($index); ?>][price][]"
                                    value="<?php echo esc_attr($row['price']); ?>"
                                >
                            </td>

                            <td>
                                <button class="button cx-remove-tier">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <br>

            <button type="button" class="button cx-add-tier">
                Add Tier
            </button>
        </div>
        <?php
    }

    private static function render_row_template() {
        ?>
        <table style="display:none;">
            <tbody>
                <tr id="cx-tier-row-template">
                    <td>
                        <input type="number" data-name="qty">
                    </td>

                    <td>
                        <input type="text" data-name="price">
                    </td>

                    <td>
                        <button class="button cx-remove-tier">Remove</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public static function save_tiers() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('You are not allowed to edit tiered prices.');
        }

        check_admin_referer('cx_save_tiers');

        if (empty($_POST['tiers']) || !is_array($_POST['tiers'])) {
            wp_safe_redirect(wp_get_referer());
            exit;
        }

        $tier_groups = wp_unslash($_POST['tiers']);

        foreach ($tier_groups as $tier_group) {
            $product_id = isset($tier_group['product_id']) ? (int) $tier_group['product_id'] : 0;
            $variation_id = isset($tier_group['variation_id']) ? (int) $tier_group['variation_id'] : 0;

            if (!$product_id) {
                continue;
            }

            $tiers = self::prepare_tiers_from_request($tier_group);

            CX_Tiered_Pricing_Prices::replace_tiers(
                $product_id,
                $variation_id,
                $tiers
            );
        }

        wp_safe_redirect(wp_get_referer());
        exit;
    }

    private static function prepare_tiers_from_request($tier_group) {
        $tiers = [];

        if (empty($tier_group['qty']) || !is_array($tier_group['qty'])) {
            return $tiers;
        }

        foreach ($tier_group['qty'] as $index => $qty) {
            $price = $tier_group['price'][$index] ?? '';

            $qty = (int) $qty;
            $price = (float) $price;

            if ($qty <= 0 || $price <= 0) {
                continue;
            }

            $tiers[] = [
                'qty'   => $qty,
                'price' => $price,
            ];
        }

        return $tiers;
    }
}