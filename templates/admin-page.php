<?php
/**
 * Admin page template for stock logs.
 *
 * @package Anony_Stock_Log
 * @var array $filters Current filters.
 * @var array $logs_data Logs data.
 * @var array $change_types Available change types.
 * @var string $page_slug Page slug.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wrap anony-stock-log-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Stock Log', 'anony-stock-log' ); ?></h1>
	<hr class="wp-header-end">

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="anony-stock-log-filters">
		<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>">
		
		<div class="anony-filter-row">
			<div class="anony-filter-field">
				<label for="product_id"><?php esc_html_e( 'Product ID', 'anony-stock-log' ); ?></label>
				<input type="number" id="product_id" name="product_id" value="<?php echo esc_attr( $filters['product_id'] ); ?>" placeholder="<?php esc_attr_e( 'Enter product ID', 'anony-stock-log' ); ?>">
			</div>

			<div class="anony-filter-field">
				<label for="product_sku"><?php esc_html_e( 'SKU', 'anony-stock-log' ); ?></label>
				<input type="text" id="product_sku" name="product_sku" value="<?php echo esc_attr( $filters['product_sku'] ); ?>" placeholder="<?php esc_attr_e( 'Enter SKU', 'anony-stock-log' ); ?>">
			</div>

			<div class="anony-filter-field">
				<label for="product_name"><?php esc_html_e( 'Product Name', 'anony-stock-log' ); ?></label>
				<input type="text" id="product_name" name="product_name" value="<?php echo esc_attr( $filters['product_name'] ); ?>" placeholder="<?php esc_attr_e( 'Enter product name', 'anony-stock-log' ); ?>">
			</div>
		</div>

		<div class="anony-filter-row">
			<div class="anony-filter-field">
				<label for="date_from"><?php esc_html_e( 'Date From', 'anony-stock-log' ); ?></label>
				<input type="date" id="date_from" name="date_from" value="<?php echo esc_attr( $filters['date_from'] ); ?>">
			</div>

			<div class="anony-filter-field">
				<label for="date_to"><?php esc_html_e( 'Date To', 'anony-stock-log' ); ?></label>
				<input type="date" id="date_to" name="date_to" value="<?php echo esc_attr( $filters['date_to'] ); ?>">
			</div>

			<div class="anony-filter-field">
				<label for="change_type"><?php esc_html_e( 'Change Type', 'anony-stock-log' ); ?></label>
				<select id="change_type" name="change_type">
					<option value=""><?php esc_html_e( 'All Types', 'anony-stock-log' ); ?></option>
					<?php foreach ( $change_types as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['change_type'], $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="anony-filter-actions">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'anony-stock-log' ); ?></button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug ) ); ?>" class="button">
				<?php esc_html_e( 'Reset', 'anony-stock-log' ); ?>
			</a>
		</div>
	</form>

	<?php if ( ! empty( $logs_data['logs'] ) ) : ?>
		<div class="anony-stock-log-stats">
			<p>
				<?php
				printf(
					/* translators: 1: total items, 2: current page, 3: total pages */
					esc_html__( 'Showing %1$d entries (Page %2$d of %3$d)', 'anony-stock-log' ),
					esc_html( number_format_i18n( $logs_data['total_items'] ) ),
					esc_html( number_format_i18n( $logs_data['page'] ) ),
					esc_html( number_format_i18n( $logs_data['total_pages'] ) )
				);
				?>
			</p>
		</div>

		<table class="wp-list-table widefat fixed striped anony-stock-log-table">
			<thead>
				<tr>
					<th class="column-id"><?php esc_html_e( 'ID', 'anony-stock-log' ); ?></th>
					<th class="column-date"><?php esc_html_e( 'Date', 'anony-stock-log' ); ?></th>
					<th class="column-product"><?php esc_html_e( 'Product', 'anony-stock-log' ); ?></th>
					<th class="column-sku"><?php esc_html_e( 'SKU', 'anony-stock-log' ); ?></th>
					<th class="column-stock"><?php esc_html_e( 'Old Stock', 'anony-stock-log' ); ?></th>
					<th class="column-stock"><?php esc_html_e( 'New Stock', 'anony-stock-log' ); ?></th>
					<th class="column-change"><?php esc_html_e( 'Change', 'anony-stock-log' ); ?></th>
					<th class="column-type"><?php esc_html_e( 'Type', 'anony-stock-log' ); ?></th>
					<th class="column-reason"><?php esc_html_e( 'Reason', 'anony-stock-log' ); ?></th>
					<th class="column-user"><?php esc_html_e( 'User', 'anony-stock-log' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs_data['logs'] as $log ) : ?>
					<tr>
						<td class="column-id"><?php echo esc_html( $log['id'] ); ?></td>
						<td class="column-date">
							<?php
							echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log['created_at'] ) ) );
							?>
						</td>
						<td class="column-product">
							<strong>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $log['product_id'] ) . '&action=edit' ) ); ?>">
									<?php echo esc_html( $log['product_name'] ); ?>
								</a>
							</strong>
							<br>
							<small>ID: <?php echo esc_html( $log['product_id'] ); ?></small>
						</td>
						<td class="column-sku"><?php echo esc_html( $log['product_sku'] ? $log['product_sku'] : '—' ); ?></td>
						<td class="column-stock">
							<?php echo null !== $log['old_stock'] ? esc_html( number_format_i18n( $log['old_stock'] ) ) : '—'; ?>
						</td>
						<td class="column-stock">
							<strong><?php echo esc_html( number_format_i18n( $log['new_stock'] ) ); ?></strong>
						</td>
						<td class="column-change">
							<?php
							$admin_instance = Anony_Stock_Log_Admin::get_instance();
							echo wp_kses_post( $admin_instance->format_stock_change( $log['stock_change'] ) );
							?>
						</td>
						<td class="column-type">
							<?php
							$admin_instance = Anony_Stock_Log_Admin::get_instance();
							echo esc_html( $admin_instance->format_change_type( $log['change_type'] ) );
							?>
						</td>
						<td class="column-reason"><?php echo esc_html( $log['change_reason'] ? $log['change_reason'] : '—' ); ?></td>
						<td class="column-user">
							<?php
							if ( ! empty( $log['user_id'] ) ) {
								$user = get_user_by( 'id', $log['user_id'] );
								if ( $user ) {
									echo esc_html( $user->display_name );
								} else {
									echo '—';
								}
							} else {
								echo '—';
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $logs_data['total_pages'] > 1 ) : ?>
			<div class="anony-stock-log-pagination">
				<?php
				$pagination_args = array(
					'base'      => admin_url( 'admin.php?page=' . $page_slug . '%_%' ),
					'format'    => '&paged=%#%',
					'current'   => $logs_data['page'],
					'total'     => $logs_data['total_pages'],
					'prev_text' => '&laquo; ' . __( 'Previous', 'anony-stock-log' ),
					'next_text' => __( 'Next', 'anony-stock-log' ) . ' &raquo;',
				);

				// Preserve filter parameters in pagination.
				$query_args = array();
				foreach ( $filters as $key => $value ) {
					if ( 'page' !== $key && 'per_page' !== $key && ! empty( $value ) ) {
						$query_args[ $key ] = $value;
					}
				}
				if ( ! empty( $query_args ) ) {
					$pagination_args['add_args'] = $query_args;
				}

				echo paginate_links( $pagination_args );
				?>
			</div>
		<?php endif; ?>

	<?php else : ?>
		<div class="notice notice-info">
			<p><?php esc_html_e( 'No stock logs found.', 'anony-stock-log' ); ?></p>
		</div>
	<?php endif; ?>
</div>

