<?php
/**
 * Settings handler for stock log plugin.
 *
 * @package Anony_Stock_Log
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Anony_Stock_Log_Settings
 */
class Anony_Stock_Log_Settings {

	/**
	 * Option name for settings.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'anony_stock_log_settings';

	/**
	 * Get setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get( $key, $default = false ) {
		$settings = get_option( self::OPTION_NAME, array() );
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Update setting value.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool
	 */
	public static function update( $key, $value ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$settings[ $key ] = $value;
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Get all settings.
	 *
	 * @return array
	 */
	public static function get_all() {
		return get_option( self::OPTION_NAME, array() );
	}

	/**
	 * Update all settings.
	 *
	 * @param array $settings Settings array.
	 * @return bool
	 */
	public static function update_all( $settings ) {
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Check if hook tracking is enabled.
	 *
	 * @return bool
	 */
	public static function is_hook_tracking_enabled() {
		return (bool) self::get( 'track_hook_location', false );
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'track_hook_location' => false, // Disabled by default for performance.
		);
	}

	/**
	 * Initialize default settings.
	 */
	public static function init_defaults() {
		$settings = self::get_all();
		if ( empty( $settings ) ) {
			self::update_all( self::get_defaults() );
		}
	}
}

