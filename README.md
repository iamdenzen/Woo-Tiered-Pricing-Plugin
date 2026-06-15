# CX Tiered Pricing for WooCommerce

Simple tiered pricing management for WooCommerce products and variations.

## Overview

CX Tiered Pricing allows store administrators to define quantity-based pricing tiers for WooCommerce products and product variations.

The plugin integrates with the CX Pricing Engine and automatically applies the correct unit price based on the selected quantity.

## Features

* Quantity-based pricing tiers
* Support for simple products
* Support for product variations
* Admin interface for managing tiers by SKU
* Automatic WooCommerce price synchronization
* Integration with CX Pricing Engine
* Product and variation cleanup on deletion
* Lightweight database structure
* No frontend templates required

---

## How It Works

Example:

| Quantity | Unit Price |
| -------- | ---------- |
| 1        | €10.00     |
| 10       | €9.50      |
| 25       | €8.75      |
| 50       | €7.99      |

If a customer orders:

* 5 units → €10.00 each
* 10 units → €9.50 each
* 30 units → €8.75 each
* 100 units → €7.99 each

The highest matching tier is used.

---

## Requirements

* WordPress 6.0+
* WooCommerce 7.0+
* PHP 8.0+

Optional:

* CX Pricing Engine

---

## Installation

1. Upload the plugin folder to:

   ```
   wp-content/plugins/cx-tiered-pricing
   ```

2. Activate the plugin from WordPress Admin.

3. Navigate to:

   ```
   Products → Tiered Pricing
   ```

4. Enter a product SKU and load its pricing tiers.

---

## Managing Tiers

### Simple Products

Search by SKU and define quantity / price pairs.

Example:

| Qty | Price |
| --- | ----- |
| 10  | 9.50  |
| 25  | 8.75  |
| 50  | 7.99  |

### Variable Products

When a parent variable product SKU is loaded, all variations are displayed.

Each variation can have its own pricing structure.

### Variation SKU

You may also search directly using a variation SKU.

Only that variation will be shown.

---

## Database

The plugin creates a single custom table:

```sql
wp_cx_tier_prices
```

Structure:

| Column       | Description              |
| ------------ | ------------------------ |
| id           | Record ID                |
| product_id   | WooCommerce Product ID   |
| variation_id | WooCommerce Variation ID |
| qty          | Minimum quantity         |
| price        | Unit price               |

Unique key:

```sql
(product_id, variation_id, qty)
```

---

## Price Resolution Logic

The plugin finds the highest tier that does not exceed the requested quantity.

Example:

Configured tiers:

| Qty | Price |
| --- | ----- |
| 10  | 9.50  |
| 25  | 8.75  |
| 50  | 7.99  |

Customer quantity:

```text
30
```

Selected tier:

```text
25 → 8.75
```

---

## WooCommerce Price Synchronization

Whenever tiers are saved:

* The lowest tier price becomes the WooCommerce product price.
* Variation prices are updated automatically.
* Variable product pricing is re-synced.

This ensures:

* Correct price display
* Correct catalog sorting
* Correct WooCommerce price indexing

---

## CX Pricing Engine Integration

If the CX Pricing Engine plugin is available, a pricing rule named:

```text
tier
```

is automatically registered.

The pricing engine can then request tier prices through:

```php
CX_Tiered_Pricing_Prices::get_tier_price(
    $product_id,
    $variation_id,
    $qty
);
```

---

## Public Methods

### Get tier price

```php
CX_Tiered_Pricing_Prices::get_tier_price(
    $product_id,
    $variation_id,
    $qty
);
```

### Get all tiers

```php
CX_Tiered_Pricing_Prices::get_all_tiers(
    $product_id,
    $variation_id
);
```

### Get available quantities

```php
CX_Tiered_Pricing_Prices::get_qty_tiers(
    $product_id,
    $variation_id
);
```

### Get lowest tier quantity

```php
CX_Tiered_Pricing_Prices::get_lowest_tier_qty(
    $product_id,
    $variation_id
);
```

### Replace all tiers

```php
CX_Tiered_Pricing_Prices::replace_tiers(
    $product_id,
    $variation_id,
    $tiers
);
```

---

## Folder Structure

```text
cx-tiered-pricing/
│
├── cx-tiered-pricing.php
│
├── includes/
│   ├── class-cx-tiered-pricing-db.php
│   ├── class-cx-tiered-pricing-admin.php
│   ├── class-cx-tiered-pricing-prices.php
│   └── class-cx-tiered-pricing-cleanup.php
│
└── assets/
    └── admin.js
```

---

## Notes

* Tier prices are stored independently from WooCommerce pricing tables.
* Product deletion automatically removes related tier pricing records.
* Variation deletion removes only variation-specific tiers.
* The plugin is intentionally lightweight and follows standard WordPress development practices.
