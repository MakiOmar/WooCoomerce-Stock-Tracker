# Anony Stock Log - WooCommerce Stock Tracking Plugin

**Author:** Mohammad Omar  
**Version:** 1.0.0  
**Requires:** WordPress 5.0+, WooCommerce 3.0+

## Description

Anony Stock Log is a comprehensive stock logging plugin for WooCommerce that provides accurate tracking of all product stock changes with advanced filtering capabilities. This plugin logs every stock modification, whether it's from manual edits, orders, stock restorations, or REST API calls.

## Features

### 📊 Comprehensive Stock Logging
- **Tracks all stock changes** automatically
- Captures manual edits, order processing, stock restorations, and REST API updates
- Records product details: ID, name, SKU
- Stores old stock, new stock, and stock change amounts
- Tracks user information and IP addresses

### 🔍 Advanced Filtering
Filter stock logs by:
- **Product ID** - Search by specific product ID
- **SKU** - Filter by product SKU (supports partial matches)
- **Product Name** - Search by product name (supports partial matches)
- **Date Range** - Filter by specific date ranges (from/to)
- **Change Type** - Filter by type of change (Manual, Order, Restore, REST API)

### 📝 Detailed Event Tracking
Each log entry includes:
- **Change Type**: Manual, Order, Restore, or REST API
- **Change Reason**: Detailed description of what triggered the change
- **User Information**: Which user made the change
- **Order ID**: When applicable (for order-related changes)
- **IP Address & User Agent**: Origin of the change
- **Timestamp**: Exact date and time of change

### 💾 Data Storage
- Dedicated database table for optimal performance
- Indexed for fast queries
- Maintains historical data

## Installation

1. Upload the `anony-stock-log` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **WooCommerce → Stock Log** to view logs

## Usage

### Viewing Stock Logs

1. Go to **WooCommerce → Stock Log** in your WordPress admin
2. Use the filter form to search for specific products or date ranges
3. Click on product names to edit the product
4. Use pagination to browse through large result sets

### Filtering Logs

The plugin provides a comprehensive filter form:
- Enter a **Product ID** to view logs for a specific product
- Enter a **SKU** (or partial SKU) to filter by SKU
- Enter a **Product Name** (or partial name) to search by name
- Select **Date From** and **Date To** to filter by date range
- Select **Change Type** to filter by event type

### Understanding Log Columns

- **ID**: Log entry ID
- **Date**: When the change occurred
- **Product**: Product name with link to edit page
- **SKU**: Product SKU
- **Old Stock**: Stock quantity before change
- **New Stock**: Stock quantity after change
- **Change**: Amount changed (green for increase, red for decrease)
- **Type**: Change type (Manual, Order, Restore, REST API)
- **Reason**: Detailed description of the change
- **User**: User who made the change

## Technical Details

### Database Schema

The plugin creates a custom table `wp_anony_stock_logs` with the following structure:
- `id` - Primary key
- `product_id` - Product ID
- `product_name` - Product name (stored for historical reference)
- `product_sku` - Product SKU
- `old_stock` - Stock quantity before change
- `new_stock` - Stock quantity after change
- `stock_change` - Calculated change amount
- `change_type` - Type of change (manual, order, restore, rest_api)
- `change_reason` - Description of change
- `user_id` - User who made the change
- `order_id` - Order ID (when applicable)
- `ip_address` - IP address
- `user_agent` - User agent string
- `created_at` - Timestamp

### Hooks Used

The plugin hooks into WooCommerce events:
- `woocommerce_product_set_stock`
- `woocommerce_variation_set_stock`
- `woocommerce_reduce_order_stock`
- `woocommerce_restore_order_stock`
- `woocommerce_before_product_object_save`
- `woocommerce_rest_insert_product_object`
- `save_post_product`

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.2 or higher

## Compliance

- ✅ Fully compliant with **WordPress PHP Coding Standards (WPCS)**
- ✅ Follows WordPress best practices
- ✅ Secure data handling and sanitization
- ✅ Proper nonce verification
- ✅ Translation ready

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/MakiOmar/WooCoomerce-Stock-Tracker).

## License

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- Comprehensive stock logging
- Advanced filtering capabilities
- Event tracking with detailed context
- WPCS compliant codebase

