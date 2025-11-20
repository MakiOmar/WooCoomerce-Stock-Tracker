<?php
/**
 * Database handler for stock logs.
 *
 * @package Anony_Stock_Log
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Anony_Stock_Log_Database
 */
class Anony_Stock_Log_Database {

	/**
	 * Instance of this class.
	 *
	 * @var Anony_Stock_Log_Database
	 */
	private static $instance = null;

	/**
	 * Get instance of this class.
	 *
	 * @return Anony_Stock_Log_Database
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'anony_stock_logs';
	}

	/**
	 * Create database tables.
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = self::get_table_name();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			product_id bigint(20) UNSIGNED NOT NULL,
			product_name varchar(255) NOT NULL,
			product_sku varchar(100) DEFAULT NULL,
			old_stock int(11) DEFAULT NULL,
			new_stock int(11) NOT NULL,
			stock_change int(11) NOT NULL,
			change_type varchar(50) NOT NULL,
			change_reason varchar(255) DEFAULT NULL,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			order_id bigint(20) UNSIGNED DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			hook_file varchar(500) DEFAULT NULL,
			hook_line int(11) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY product_id (product_id),
			KEY product_sku (product_sku),
			KEY created_at (created_at),
			KEY change_type (change_type)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Check if we need to migrate existing table.
		self::maybe_migrate_table();

		// Store version for future migrations.
		update_option( 'anony_stock_log_db_version', '1.0.1' );
	}

	/**
	 * Migrate existing table to add new columns.
	 */
	public static function maybe_migrate_table() {
		global $wpdb;

		$table_name   = self::get_table_name();
		$db_version   = get_option( 'anony_stock_log_db_version', '1.0.0' );

		// Only migrate if version is less than 1.0.1.
		if ( version_compare( $db_version, '1.0.1', '<' ) ) {
			// Check if columns exist using SHOW COLUMNS (safer than INFORMATION_SCHEMA).
			$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name}" );
			
			$has_hook_file = false;
			$has_hook_line = false;

			if ( ! empty( $columns ) ) {
				foreach ( $columns as $column ) {
					if ( 'hook_file' === $column->Field ) {
						$has_hook_file = true;
					}
					if ( 'hook_line' === $column->Field ) {
						$has_hook_line = true;
					}
				}
			}

			// Add hook_file column if it doesn't exist.
			if ( ! $has_hook_file ) {
				$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN hook_file varchar(500) DEFAULT NULL AFTER user_agent" );
			}

			// Add hook_line column if it doesn't exist.
			if ( ! $has_hook_line ) {
				$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN hook_line int(11) DEFAULT NULL AFTER hook_file" );
			}

			update_option( 'anony_stock_log_db_version', '1.0.1' );
		}
	}

	/**
	 * Insert stock log entry.
	 *
	 * @param array $data Log data.
	 * @return int|false Log ID on success, false on failure.
	 */
	public static function insert_log( $data ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$defaults = array(
			'product_id'    => 0,
			'product_name'  => '',
			'product_sku'   => null,
			'old_stock'     => null,
			'new_stock'     => 0,
			'stock_change' => 0,
			'change_type'   => 'manual',
			'change_reason' => null,
			'user_id'       => get_current_user_id(),
			'order_id'      => null,
			'ip_address'    => self::get_client_ip(),
			'user_agent'    => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : null,
			'hook_file'     => null,
			'hook_line'     => null,
			'created_at'    => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $data, $defaults );

		// Sanitize data.
		$insert_data = array(
			'product_id'    => absint( $data['product_id'] ),
			'product_name'  => sanitize_text_field( $data['product_name'] ),
			'product_sku'   => ! empty( $data['product_sku'] ) ? sanitize_text_field( $data['product_sku'] ) : null,
			'old_stock'     => null !== $data['old_stock'] ? intval( $data['old_stock'] ) : null,
			'new_stock'     => intval( $data['new_stock'] ),
			'stock_change'  => intval( $data['stock_change'] ),
			'change_type'   => sanitize_text_field( $data['change_type'] ),
			'change_reason' => ! empty( $data['change_reason'] ) ? sanitize_text_field( $data['change_reason'] ) : null,
			'user_id'       => ! empty( $data['user_id'] ) ? absint( $data['user_id'] ) : null,
			'order_id'      => ! empty( $data['order_id'] ) ? absint( $data['order_id'] ) : null,
			'ip_address'    => $data['ip_address'],
			'user_agent'    => $data['user_agent'],
			'hook_file'     => ! empty( $data['hook_file'] ) ? sanitize_text_field( $data['hook_file'] ) : null,
			'hook_line'     => ! empty( $data['hook_line'] ) ? absint( $data['hook_line'] ) : null,
			'created_at'    => $data['created_at'],
		);

		$result = $wpdb->insert( $table_name, $insert_data );

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get stock logs with filters.
	 *
	 * @param array $args Query arguments.
	 * @return array|object|null
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;

		$table_name = self::get_table_name();

		$defaults = array(
			'product_id'  => '',
			'product_sku' => '',
			'product_name' => '',
			'date_from'   => '',
			'date_to'     => '',
			'change_type' => '',
			'per_page'    => 20,
			'page'        => 1,
			'orderby'     => 'created_at',
			'order'       => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		// Filter by product ID.
		if ( ! empty( $args['product_id'] ) ) {
			$where[]        = 'product_id = %d';
			$where_values[] = absint( $args['product_id'] );
		}

		// Filter by SKU.
		if ( ! empty( $args['product_sku'] ) ) {
			$where[]        = 'product_sku LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['product_sku'] ) ) . '%';
		}

		// Filter by product name.
		if ( ! empty( $args['product_name'] ) ) {
			$where[]        = 'product_name LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['product_name'] ) ) . '%';
		}

		// Filter by date range.
		if ( ! empty( $args['date_from'] ) ) {
			$where[]        = 'DATE(created_at) >= %s';
			$where_values[] = sanitize_text_field( $args['date_from'] );
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[]        = 'DATE(created_at) <= %s';
			$where_values[] = sanitize_text_field( $args['date_to'] );
		}

		// Filter by change type.
		if ( ! empty( $args['change_type'] ) ) {
			$where[]        = 'change_type = %s';
			$where_values[] = sanitize_text_field( $args['change_type'] );
		}

		$where_clause = implode( ' AND ', $where );

		// Calculate offset.
		$offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

		// Validate orderby and order.
		$allowed_orderby = array( 'id', 'product_id', 'product_name', 'product_sku', 'old_stock', 'new_stock', 'stock_change', 'change_type', 'created_at' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		// Get total count.
		$count_query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
		if ( ! empty( $where_values ) ) {
			$count_query = $wpdb->prepare( $count_query, $where_values );
		}
		$total_items = (int) $wpdb->get_var( $count_query );

		// Get logs.
		$query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
		$query_values = array_merge( $where_values, array( absint( $args['per_page'] ), $offset ) );
		$query = $wpdb->prepare( $query, $query_values );

		$logs = $wpdb->get_results( $query, ARRAY_A );

		return array(
			'logs'        => $logs,
			'total_items' => $total_items,
			'total_pages' => ceil( $total_items / absint( $args['per_page'] ) ),
			'page'        => absint( $args['page'] ),
		);
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	private static function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) && ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated IPs.
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = explode( ',', $ip );
					$ip = trim( $ip[0] );
				}
				// Validate IP.
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}
}

