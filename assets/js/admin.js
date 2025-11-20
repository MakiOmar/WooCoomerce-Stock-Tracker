/**
 * Admin scripts for Anony Stock Log plugin.
 *
 * @package Anony_Stock_Log
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Date validation: ensure date_from is not after date_to
		$('#date_from, #date_to').on('change', function() {
			var dateFrom = $('#date_from').val();
			var dateTo = $('#date_to').val();

			if (dateFrom && dateTo && dateFrom > dateTo) {
				alert('Date From cannot be after Date To.');
				$(this).val('');
			}
		});

		// Auto-submit on filter change (optional enhancement)
		// Uncomment if you want auto-filtering
		/*
		$('.anony-stock-log-filters select, .anony-stock-log-filters input[type="date"]').on('change', function() {
			$(this).closest('form').submit();
		});
		*/
	});

})(jQuery);

