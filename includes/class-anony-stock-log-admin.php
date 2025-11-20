<?php
/**
 * Admin interface for stock logs.
 *
 * @package Anony_Stock_Log
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Anony_Stock_Log_Admin
 */
class Anony_Stock_Log_Admin {

	/**
	 * Instance of this class.
	 *
	 * @var Anony_Stock_Log_Admin
	 */
	private static $instance = null;

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'anony-stock-log';

	/**
	 * Get instance of this class.
	 *
	 * @return Anony_Stock_Log_Admin
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Stock Log', 'anony-stock-log' ),
			__( 'Stock Log', 'anony-stock-log' ),
			'manage_woocommerce',
			$this->page_slug,
			array( $this, 'render_page' )
		);

		add_submenu_page(
			'woocommerce',
			__( 'Stock Log Settings', 'anony-stock-log' ),
			__( 'Stock Log Settings', 'anony-stock-log' ),
			'manage_woocommerce',
			$this->page_slug . '-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $hook Current page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'woocommerce_page_' . $this->page_slug !== $hook && 'woocommerce_page_' . $this->page_slug . '-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'anony-stock-log-admin',
			ANONY_STOCK_LOG_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ANONY_STOCK_LOG_VERSION
		);

		wp_enqueue_script(
			'anony-stock-log-admin',
			ANONY_STOCK_LOG_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			ANONY_STOCK_LOG_VERSION,
			true
		);
	}

	/**
	 * Render admin page.
	 */
	public function render_page() {
		// Get filter parameters.
		$filters = array(
			'product_id'   => isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : '',
			'product_sku'  => isset( $_GET['product_sku'] ) ? sanitize_text_field( wp_unslash( $_GET['product_sku'] ) ) : '',
			'product_name' => isset( $_GET['product_name'] ) ? sanitize_text_field( wp_unslash( $_GET['product_name'] ) ) : '',
			'date_from'    => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
			'date_to'      => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
			'change_type'  => isset( $_GET['change_type'] ) ? sanitize_text_field( wp_unslash( $_GET['change_type'] ) ) : '',
			'per_page'     => isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 20,
			'page'         => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1,
		);

		// Get logs.
		$logs_data = Anony_Stock_Log_Database::get_logs( $filters );

		// Get change types.
		$change_types = array(
			'manual'   => __( 'Manual', 'anony-stock-log' ),
			'order'    => __( 'Order', 'anony-stock-log' ),
			'restore'  => __( 'Restore', 'anony-stock-log' ),
			'rest_api' => __( 'REST API', 'anony-stock-log' ),
		);

		// Make variables available to template.
		$page_slug = $this->page_slug;

		include ANONY_STOCK_LOG_PLUGIN_DIR . 'templates/admin-page.php';
	}

	/**
	 * Get filter URL.
	 *
	 * @param array $args Additional query args.
	 * @return string
	 */
	private function get_filter_url( $args = array() ) {
		$base_url = admin_url( 'admin.php?page=' . $this->page_slug );
		$current_filters = array(
			'product_id'   => isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : '',
			'product_sku'  => isset( $_GET['product_sku'] ) ? sanitize_text_field( wp_unslash( $_GET['product_sku'] ) ) : '',
			'product_name' => isset( $_GET['product_name'] ) ? sanitize_text_field( wp_unslash( $_GET['product_name'] ) ) : '',
			'date_from'    => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
			'date_to'      => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
			'change_type'  => isset( $_GET['change_type'] ) ? sanitize_text_field( wp_unslash( $_GET['change_type'] ) ) : '',
			'per_page'     => isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 20,
		);

		$filters = array_merge( $current_filters, $args );
		$filters = array_filter( $filters );

		if ( ! empty( $filters ) ) {
			$base_url .= '&' . http_build_query( $filters );
		}

		return $base_url;
	}

	/**
	 * Format change type for display.
	 *
	 * @param string $change_type Change type.
	 * @return string
	 */
	public function format_change_type( $change_type ) {
		$types = array(
			'manual'   => __( 'Manual', 'anony-stock-log' ),
			'order'    => __( 'Order', 'anony-stock-log' ),
			'restore'  => __( 'Restore', 'anony-stock-log' ),
			'rest_api' => __( 'REST API', 'anony-stock-log' ),
		);

		return isset( $types[ $change_type ] ) ? $types[ $change_type ] : $change_type;
	}

	/**
	 * Format stock change for display.
	 *
	 * @param int $stock_change Stock change value.
	 * @return string
	 */
	public function format_stock_change( $stock_change ) {
		if ( $stock_change > 0 ) {
			return '<span class="stock-increase">+' . esc_html( number_format_i18n( $stock_change ) ) . '</span>';
		} elseif ( $stock_change < 0 ) {
			return '<span class="stock-decrease">' . esc_html( number_format_i18n( $stock_change ) ) . '</span>';
		}
		return '<span class="stock-no-change">' . esc_html( number_format_i18n( 0 ) ) . '</span>';
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		// Handle form submission.
		if ( isset( $_POST['anony_stock_log_settings'] ) && check_admin_referer( 'anony_stock_log_settings', 'anony_stock_log_settings_nonce' ) ) {
			$settings = array(
				'track_hook_location' => isset( $_POST['track_hook_location'] ) && '1' === $_POST['track_hook_location'],
			);

			Anony_Stock_Log_Settings::update_all( $settings );

			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully.', 'anony-stock-log' ) . '</p></div>';
		}

		// Get current settings.
		$track_hook_location = Anony_Stock_Log_Settings::is_hook_tracking_enabled();

		include ANONY_STOCK_LOG_PLUGIN_DIR . 'templates/settings-page.php';
	}
}

