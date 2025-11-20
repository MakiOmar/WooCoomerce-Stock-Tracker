<?php
/**
 * Stock logger to capture WooCommerce stock changes.
 *
 * @package Anony_Stock_Log
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Anony_Stock_Logger
 */
class Anony_Stock_Logger {

	/**
	 * Instance of this class.
	 *
	 * @var Anony_Stock_Logger
	 */
	private static $instance = null;

	/**
	 * Track product stock before change.
	 *
	 * @var array
	 */
	private $product_stock_before = array();

	/**
	 * Track REST API context for stock changes.
	 *
	 * @var array
	 */
	private $rest_api_context = array();

	/**
	 * Get instance of this class.
	 *
	 * @return Anony_Stock_Logger
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Track stock before update.
		add_action( 'woocommerce_before_product_object_save', array( $this, 'track_stock_before' ), 10, 1 );
		add_filter( 'woocommerce_update_product_stock', array( $this, 'track_stock_before_filter' ), 10, 1 );

		// Track stock after update.
		add_action( 'woocommerce_product_set_stock', array( $this, 'log_stock_change' ), 10, 1 );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'log_stock_change' ), 10, 1 );

		// Track order stock changes - hook before stock is reduced.
		add_action( 'woocommerce_reduce_order_stock', array( $this, 'track_order_stock_before' ), 5, 1 );
		add_action( 'woocommerce_reduce_order_stock', array( $this, 'log_order_stock_reduction' ), 10, 1 );
		add_action( 'woocommerce_restore_order_stock', array( $this, 'log_order_stock_restore' ), 10, 1 );

		// Track manual stock changes.
		add_action( 'wp_ajax_woocommerce_save_product_variations', array( $this, 'track_variation_stock' ), 5 );
		
		// Track stock before save_post_product.
		add_action( 'save_post_product', array( $this, 'track_product_stock_before_save' ), 5, 1 );
		add_action( 'save_post_product', array( $this, 'log_manual_stock_change' ), 99, 1 );

		// Track stock from REST API.
		add_action( 'woocommerce_rest_insert_product_object', array( $this, 'log_rest_stock_change' ), 10, 3 );
	}

	/**
	 * Track stock before product save.
	 *
	 * @param WC_Product $product Product object.
	 */
	public function track_stock_before( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id = $product->get_id();
		if ( ! $product_id ) {
			return;
		}

		$this->product_stock_before[ $product_id ] = $product->get_stock_quantity();
	}

	/**
	 * Track stock before filter.
	 *
	 * @param WC_Product $product Product object.
	 * @return WC_Product
	 */
	public function track_stock_before_filter( $product ) {
		if ( $product instanceof WC_Product ) {
			$product_id = $product->get_id();
			if ( $product_id ) {
				$this->product_stock_before[ $product_id ] = $product->get_stock_quantity();
			}
		}
		return $product;
	}

	/**
	 * Log stock change.
	 *
	 * @param WC_Product $product Product object.
	 */
	public function log_stock_change( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id = $product->get_id();
		if ( ! $product_id ) {
			return;
		}

		$new_stock = $product->get_stock_quantity();
		$old_stock = isset( $this->product_stock_before[ $product_id ] ) ? $this->product_stock_before[ $product_id ] : null;

		// If we don't have old stock tracked, try to get it from the last log entry.
		if ( null === $old_stock ) {
			$old_stock = $this->get_last_stock_from_log( $product_id );
		}

		// Skip if stock hasn't actually changed (and we have old stock to compare).
		// But allow logging if old_stock is null (first time logging or couldn't determine old stock).
		if ( null !== $old_stock && $old_stock === $new_stock ) {
			return;
		}

		// Determine change type and reason.
		$change_type = 'manual';
		$change_reason = null;

		// Check if we're currently saving a post (product edit).
		$is_saving_product = doing_action( 'save_post' ) || doing_action( 'save_post_product' );

		if ( doing_action( 'woocommerce_reduce_order_stock' ) ) {
			$change_type = 'order';
		} elseif ( doing_action( 'woocommerce_restore_order_stock' ) ) {
			$change_type = 'restore';
		} elseif ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$change_type = 'rest_api';
			// Try to get REST API endpoint info from stored context.
			if ( isset( $this->rest_api_context[ $product_id ] ) ) {
				$api_context = $this->rest_api_context[ $product_id ];
				$change_reason = sprintf(
					/* translators: 1: HTTP method, 2: REST API route */
					__( 'REST API: %1$s %2$s', 'anony-stock-log' ),
					$api_context['method'],
					$api_context['route']
				);
				// Clean up after use.
				unset( $this->rest_api_context[ $product_id ] );
			} else {
				// Fallback to REQUEST_URI if context not available.
				$route = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				if ( $route ) {
					$change_reason = sprintf(
						/* translators: %s: REST API route */
						__( 'REST API: %s', 'anony-stock-log' ),
						$route
					);
				}
			}
		} elseif ( $is_saving_product || ( is_admin() && isset( $_POST['post_type'] ) && 'product' === $_POST['post_type'] ) ) {
			// Manual change from product edit page.
			$change_reason = __( 'Manual edit: Product edit page', 'anony-stock-log' );
		} else {
			// Manual change - determine context.
			if ( is_admin() && isset( $_REQUEST['action'] ) && 'woocommerce_save_product_variations' === $_REQUEST['action'] ) {
				$change_reason = __( 'Manual edit: Product variation bulk save', 'anony-stock-log' );
			} elseif ( is_admin() ) {
				$change_reason = __( 'Manual edit: Admin panel', 'anony-stock-log' );
			} else {
				$change_reason = __( 'Manual edit', 'anony-stock-log' );
			}
		}

		$extra_data = array();
		if ( $change_reason ) {
			$extra_data['change_reason'] = $change_reason;
		}

		$this->save_log( $product, $old_stock, $new_stock, $change_type, $extra_data );

		// Update our tracking array with the new stock for next comparison.
		$this->product_stock_before[ $product_id ] = $new_stock;
	}

	/**
	 * Track stock before order stock reduction.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function track_order_stock_before( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			$product_id = $product->get_id();
			if ( ! $product_id ) {
				continue;
			}

			// Store current stock (before reduction).
			$this->product_stock_before[ $product_id ] = $product->get_stock_quantity();
		}
	}

	/**
	 * Log order stock reduction.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function log_order_stock_reduction( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			$product_id = $product->get_id();
			if ( ! $product_id ) {
				continue;
			}

			$new_stock = $product->get_stock_quantity();
			$old_stock = isset( $this->product_stock_before[ $product_id ] ) ? $this->product_stock_before[ $product_id ] : ( $new_stock + $item->get_quantity() );

			// Only log if stock actually changed.
			if ( $old_stock !== $new_stock ) {
				$this->save_log(
					$product,
					$old_stock,
					$new_stock,
					'order',
					array(
						'order_id'      => $order->get_id(),
						'change_reason' => sprintf(
							/* translators: %s: order ID */
							__( 'Order #%s', 'anony-stock-log' ),
							$order->get_id()
						),
					)
				);
			}
		}
	}

	/**
	 * Log order stock restore.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function log_order_stock_restore( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			$product_id = $product->get_id();
			if ( ! $product_id ) {
				continue;
			}

			$quantity = $item->get_quantity();
			$old_stock = $product->get_stock_quantity();
			$new_stock = $old_stock + $quantity;

			$this->save_log(
				$product,
				$old_stock,
				$new_stock,
				'restore',
				array(
					'order_id'      => $order->get_id(),
					'change_reason' => sprintf(
						/* translators: %s: order ID */
						__( 'Restore from order #%s', 'anony-stock-log' ),
						$order->get_id()
					),
				)
			);
		}
	}

	/**
	 * Track product stock before save_post_product.
	 *
	 * @param int $post_id Post ID.
	 */
	public function track_product_stock_before_save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['post_type'] ) || 'product' !== $_POST['post_type'] ) {
			return;
		}

		// Get the product before save to capture old stock.
		$product = wc_get_product( $post_id );
		if ( ! $product ) {
			return;
		}

		// Store old stock before save.
		$this->product_stock_before[ $post_id ] = $product->get_stock_quantity();
	}

	/**
	 * Track variation stock changes.
	 */
	public function track_variation_stock() {
		if ( ! isset( $_POST['variable_post_id'] ) ) {
			return;
		}

		$variation_ids = array_map( 'absint', wp_unslash( $_POST['variable_post_id'] ) );
		foreach ( $variation_ids as $variation_id ) {
			$product = wc_get_product( $variation_id );
			if ( ! $product ) {
				continue;
			}

			// Store old stock.
			$this->product_stock_before[ $variation_id ] = $product->get_stock_quantity();
		}
	}

	/**
	 * Log manual stock change.
	 *
	 * @param int $post_id Post ID.
	 */
	public function log_manual_stock_change( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['post_type'] ) || 'product' !== $_POST['post_type'] ) {
			return;
		}

		// Check if stock was actually changed by comparing POST data with current stock.
		$product = wc_get_product( $post_id );
		if ( ! $product ) {
			return;
		}

		// Only log if stock management is enabled for this product.
		if ( ! $product->managing_stock() ) {
			return;
		}

		$new_stock = $product->get_stock_quantity();
		$old_stock = isset( $this->product_stock_before[ $post_id ] ) ? $this->product_stock_before[ $post_id ] : null;

		// If we don't have old stock tracked, try to get it from the log.
		if ( null === $old_stock ) {
			$old_stock = $this->get_last_stock_from_log( $post_id );
		}

		// Skip if stock hasn't actually changed (only if we have old stock to compare).
		if ( null !== $old_stock && $old_stock === $new_stock ) {
			return;
		}

		// Check if stock was actually submitted in POST (indicating a manual change).
		$stock_was_submitted = isset( $_POST['_stock'] ) || isset( $_POST['_manage_stock'] );
		
		// Only log if stock was actually submitted or if we can detect a change.
		if ( ! $stock_was_submitted && null === $old_stock && null === $new_stock ) {
			return;
		}

		// Determine reason.
		$change_reason = __( 'Manual edit: Product edit page', 'anony-stock-log' );

		// Log the change.
		$this->save_log( $product, $old_stock, $new_stock, 'manual', array( 'change_reason' => $change_reason ) );
	}

	/**
	 * Log REST API stock change.
	 *
	 * @param WC_Product      $product  Product object.
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating True if creating, false if updating.
	 */
	public function log_rest_stock_change( $product, $request, $creating ) {
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		if ( $creating ) {
			// New product, no old stock to track.
			return;
		}

		$product_id = $product->get_id();
		if ( ! isset( $this->product_stock_before[ $product_id ] ) ) {
			// Try to get old stock from database.
			$old_product = wc_get_product( $product_id );
			if ( $old_product ) {
				$this->product_stock_before[ $product_id ] = $old_product->get_stock_quantity();
			}
		}

		// Store REST API details for later use.
		if ( $request instanceof WP_REST_Request ) {
			$route = $request->get_route();
			$method = $request->get_method();
			$this->rest_api_context[ $product_id ] = array(
				'route'  => $route,
				'method' => $method,
			);
		}

		$this->log_stock_change( $product );
	}

	/**
	 * Get last stock quantity from log for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return int|null Last stock quantity or null if not found.
	 */
	private function get_last_stock_from_log( $product_id ) {
		global $wpdb;
		$table_name = Anony_Stock_Log_Database::get_table_name();

		$last_stock = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT new_stock FROM {$table_name} WHERE product_id = %d ORDER BY created_at DESC, id DESC LIMIT 1",
				$product_id
			)
		);

		return null !== $last_stock ? (int) $last_stock : null;
	}

	/**
	 * Get hook location from backtrace.
	 *
	 * @return array|null Array with 'file' and 'line' keys, or null if tracking disabled.
	 */
	private function get_hook_location() {
		// Check if hook tracking is enabled.
		if ( ! class_exists( 'Anony_Stock_Log_Settings' ) || ! Anony_Stock_Log_Settings::is_hook_tracking_enabled() ) {
			return null;
		}

		// Get backtrace, skipping our own methods.
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 15 );
		if ( empty( $backtrace ) ) {
			return null;
		}

		// Skip our own classes to find the actual caller.
		$skip_classes = array( 'Anony_Stock_Logger', 'WC_Product', 'WC_Product_Variable' );
		$skip_files = array( __FILE__, 'wp-includes/plugin.php', 'wp-includes/class-wp-hook.php' );

		foreach ( $backtrace as $trace ) {
			// Skip if this is our own class or file.
			if ( ! empty( $trace['class'] ) && in_array( $trace['class'], $skip_classes, true ) ) {
				continue;
			}

			if ( ! empty( $trace['file'] ) ) {
				$file = $trace['file'];
				// Skip WordPress core files.
				foreach ( $skip_files as $skip_file ) {
					if ( strpos( $file, $skip_file ) !== false ) {
						continue 2;
					}
				}

				// Found a relevant file - get relative path.
				$file_path = str_replace( ABSPATH, '', $file );
				$line = isset( $trace['line'] ) ? $trace['line'] : 0;

				return array(
					'file' => $file_path,
					'line' => $line,
				);
			}
		}

		return null;
	}

	/**
	 * Save log entry.
	 *
	 * @param WC_Product $product    Product object.
	 * @param int|null   $old_stock  Old stock quantity.
	 * @param int        $new_stock  New stock quantity.
	 * @param string     $change_type Change type.
	 * @param array      $extra_data  Extra data to include.
	 */
	private function save_log( $product, $old_stock, $new_stock, $change_type = 'manual', $extra_data = array() ) {
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id = $product->get_id();
		if ( ! $product_id ) {
			return;
		}

		// Calculate stock change.
		$stock_change = null !== $old_stock ? ( $new_stock - $old_stock ) : 0;

		// Prepare log data.
		$log_data = array(
			'product_id'    => $product_id,
			'product_name'  => $product->get_name(),
			'product_sku'   => $product->get_sku(),
			'old_stock'     => $old_stock,
			'new_stock'     => $new_stock,
			'stock_change'  => $stock_change,
			'change_type'   => $change_type,
		);

		// Merge extra data.
		if ( ! empty( $extra_data ) ) {
			$log_data = array_merge( $log_data, $extra_data );
		}

		// Get hook location if tracking is enabled.
		$hook_location = $this->get_hook_location();
		if ( $hook_location ) {
			$log_data['hook_file'] = $hook_location['file'];
			$log_data['hook_line'] = $hook_location['line'];
		}

		// Save log.
		Anony_Stock_Log_Database::insert_log( $log_data );
	}
}

