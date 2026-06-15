<?php

if (!defined('ABSPATH')) {
    exit;
}

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

    <?php CX_Tiered_Pricing_Admin::render_tier_form(); ?>
</div>