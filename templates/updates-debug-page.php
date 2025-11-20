<?php
/**
 * Updates debug page template for stock logs.
 *
 * @package Anony_Stock_Log
 * @var object|null $anony_stock_log_update_checker_instance Update checker instance.
 * @var array       $plugin_data Plugin data array.
 * @var string      $current_version Current plugin version.
 * @var object|null $update Update object if available.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wrap anony-stock-log-updates-wrap">
	<h1><?php esc_html_e( 'Plugin Update Checker Debug', 'anony-stock-log' ); ?></h1>

	<div class="card" style="max-width: 800px;">
		<h2><?php esc_html_e( 'Current Status', 'anony-stock-log' ); ?></h2>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Installed Version', 'anony-stock-log' ); ?></th>
					<td><strong><?php echo esc_html( $current_version ); ?></strong></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Update Checker Status', 'anony-stock-log' ); ?></th>
					<td>
						<?php if ( $anony_stock_log_update_checker_instance ) : ?>
							<span style="color: green;"><?php esc_html_e( 'Initialized ✓', 'anony-stock-log' ); ?></span>
						<?php else : ?>
							<span style="color: red;"><?php esc_html_e( 'Not Initialized ✗', 'anony-stock-log' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Update Info URL', 'anony-stock-log' ); ?></th>
					<td>
						<a href="https://github.com/MakiOmar/WooCoomerce-Stock-Tracker/raw/main/update-info.json" target="_blank">
							<?php esc_html_e( 'View update-info.json', 'anony-stock-log' ); ?>
						</a>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Update Available', 'anony-stock-log' ); ?></th>
					<td>
						<?php if ( $update ) : ?>
							<span style="color: green;">
								<?php
								printf(
									/* translators: %s: version number */
									esc_html__( 'Yes - Version %s', 'anony-stock-log' ),
									esc_html( $update->version )
								);
								?>
							</span>
						<?php else : ?>
							<span><?php esc_html_e( 'No (up to date)', 'anony-stock-log' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

		<?php if ( $update ) : ?>
			<div style="background: #e7f7e7; padding: 15px; border-left: 4px solid green; margin: 15px 0;">
				<h3 style="margin-top: 0;">
					<?php
					printf(
						/* translators: %s: version number */
						esc_html__( 'New Version Available: %s', 'anony-stock-log' ),
						esc_html( $update->version )
					);
					?>
				</h3>
				<p><strong><?php esc_html_e( 'Download URL:', 'anony-stock-log' ); ?></strong> <?php echo esc_html( $update->download_url ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<div class="card" style="max-width: 800px; margin-top: 20px;">
		<h2><?php esc_html_e( 'Actions', 'anony-stock-log' ); ?></h2>
		<form method="post" style="display: inline-block; margin-right: 10px;">
			<?php wp_nonce_field( 'clear_update_cache' ); ?>
			<button type="submit" name="clear_update_cache" class="button button-primary">
				<?php esc_html_e( 'Clear Update Cache', 'anony-stock-log' ); ?>
			</button>
			<p class="description"><?php esc_html_e( 'Clears WordPress update cache and plugin update checker cache', 'anony-stock-log' ); ?></p>
		</form>

		<form method="post" style="display: inline-block;">
			<?php wp_nonce_field( 'force_check_updates' ); ?>
			<button type="submit" name="force_check" class="button">
				<?php esc_html_e( 'Force Check for Updates', 'anony-stock-log' ); ?>
			</button>
			<p class="description"><?php esc_html_e( 'Immediately checks for updates from GitHub', 'anony-stock-log' ); ?></p>
		</form>
	</div>

	<div class="card" style="max-width: 800px; margin-top: 20px;">
		<h2><?php esc_html_e( 'Troubleshooting Steps', 'anony-stock-log' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Click "Clear Update Cache" to remove cached update data', 'anony-stock-log' ); ?></li>
			<li><?php esc_html_e( 'Click "Force Check for Updates" to check GitHub immediately', 'anony-stock-log' ); ?></li>
			<li><?php esc_html_e( 'Go to Dashboard → Updates to see if update appears', 'anony-stock-log' ); ?></li>
			<li><?php esc_html_e( 'Click the update-info.json link above to verify it\'s accessible', 'anony-stock-log' ); ?></li>
		</ol>

		<h3><?php esc_html_e( 'Cache Transients Status', 'anony-stock-log' ); ?></h3>
		<ul>
			<li>
				<?php esc_html_e( 'WordPress update_plugins:', 'anony-stock-log' ); ?>
				<?php echo get_site_transient( 'update_plugins' ) ? '<span style="color: green;">✓ Cached</span>' : '<span style="color: red;">✗ Not cached</span>'; ?>
			</li>
			<li>
				<?php esc_html_e( 'PUC request info:', 'anony-stock-log' ); ?>
				<?php echo get_transient( 'puc_request_info-anony-stock-log' ) ? '<span style="color: green;">✓ Cached</span>' : '<span style="color: red;">✗ Not cached</span>'; ?>
			</li>
		</ul>
	</div>
</div>

