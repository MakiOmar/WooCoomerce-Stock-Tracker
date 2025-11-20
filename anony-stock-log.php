<?php
/**
 * Plugin Name: Anony Stock Log
 * Plugin URI: https://github.com/MakiOmar/WooCoomerce-Stock-Tracker
 * Description: Accurate stock logging for WooCommerce products with advanced filtering capabilities. Track all stock changes including manual edits, orders, restorations, and REST API updates. Filter by product ID, SKU, name, date range, and change type. Fully WPCS compliant.
 * Version: 1.0.0
 * Author: Mohammad Omar
 * Author URI: https://github.com/MakiOmar
 * Text Domain: anony-stock-log
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'ANONY_STOCK_LOG_VERSION', '1.0.0' );
define( 'ANONY_STOCK_LOG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ANONY_STOCK_LOG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ANONY_STOCK_LOG_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class.
 */
class Anony_Stock_Log {

	/**
	 * Plugin instance.
	 *
	 * @var Anony_Stock_Log
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Anony_Stock_Log
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
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once ANONY_STOCK_LOG_PLUGIN_DIR . 'includes/class-anony-stock-log-database.php';
		require_once ANONY_STOCK_LOG_PLUGIN_DIR . 'includes/class-anony-stock-log-settings.php';
		require_once ANONY_STOCK_LOG_PLUGIN_DIR . 'includes/class-anony-stock-logger.php';
		require_once ANONY_STOCK_LOG_PLUGIN_DIR . 'includes/class-anony-stock-log-admin.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'check_woocommerce' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Check if WooCommerce is active.
	 */
	public function check_woocommerce() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Initialize components.
		Anony_Stock_Log_Database::get_instance();
		Anony_Stock_Log_Database::maybe_migrate_table(); // Run migration check on load.
		Anony_Stock_Log_Settings::init_defaults();
		Anony_Stock_Logger::get_instance();
		Anony_Stock_Log_Admin::get_instance();
	}

	/**
	 * Show notice if WooCommerce is not active.
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Anony Stock Log requires WooCommerce to be installed and active.', 'anony-stock-log' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				esc_html__( 'Anony Stock Log requires WooCommerce to be installed and active.', 'anony-stock-log' ),
				esc_html__( 'Plugin Activation Error', 'anony-stock-log' ),
				array( 'back_link' => true )
			);
		}

		Anony_Stock_Log_Database::create_tables();
		Anony_Stock_Log_Database::maybe_migrate_table();
		Anony_Stock_Log_Settings::init_defaults();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Cleanup if needed.
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'anony-stock-log',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
}

/**
 * Initialize plugin.
 */
function anony_stock_log_init() {
	return Anony_Stock_Log::get_instance();
}

// Start the plugin.
anony_stock_log_init();

