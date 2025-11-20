<?php
/**
 * Settings page template for stock logs.
 *
 * @package Anony_Stock_Log
 * @var bool $track_hook_location Whether hook tracking is enabled.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wrap anony-stock-log-settings-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Stock Log Settings', 'anony-stock-log' ); ?></h1>
	<hr class="wp-header-end">

	<form method="post" action="">
		<?php wp_nonce_field( 'anony_stock_log_settings', 'anony_stock_log_settings_nonce' ); ?>
		<input type="hidden" name="anony_stock_log_settings" value="1">

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="track_hook_location"><?php esc_html_e( 'Track Hook Location', 'anony-stock-log' ); ?></label>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php esc_html_e( 'Track Hook Location', 'anony-stock-log' ); ?></span>
							</legend>
							<label for="track_hook_location">
								<input type="checkbox" id="track_hook_location" name="track_hook_location" value="1" <?php checked( $track_hook_location, true ); ?>>
								<?php esc_html_e( 'Enable hook location tracking', 'anony-stock-log' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, the plugin will track the file and line number where each stock change hook was fired. This is useful for debugging but may slightly impact performance on high-traffic sites.', 'anony-stock-log' ); ?>
							</p>
							<p class="description">
								<strong><?php esc_html_e( 'Performance Note:', 'anony-stock-log' ); ?></strong>
								<?php esc_html_e( 'This feature uses debug_backtrace() which can be CPU intensive. Disable it if you experience performance issues or have high stock change volume.', 'anony-stock-log' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button(); ?>
	</form>

	<div class="anony-stock-log-info-box" style="margin-top: 30px; padding: 15px; background: #f9f9f9; border-left: 4px solid #2271b1;">
		<h3><?php esc_html_e( 'About Hook Location Tracking', 'anony-stock-log' ); ?></h3>
		<p><?php esc_html_e( 'When hook location tracking is enabled, each stock log entry will include:', 'anony-stock-log' ); ?></p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'The file path where the hook was triggered', 'anony-stock-log' ); ?></li>
			<li><?php esc_html_e( 'The line number in that file', 'anony-stock-log' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'This information helps identify which plugin, theme, or custom code is causing stock changes, which is valuable for troubleshooting and debugging.', 'anony-stock-log' ); ?></p>
	</div>
</div>

