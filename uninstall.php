<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Anony_Stock_Log
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Option: Delete database table on uninstall.
// Uncomment the following lines if you want to delete the stock logs table when the plugin is uninstalled.
/*
global $wpdb;
$table_name = $wpdb->prefix . 'anony_stock_logs';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
delete_option( 'anony_stock_log_db_version' );
*/

