(function($) {
	$('select[name = "config[section]"]')
		.live('change', function() {
			var id = $(this).val();

			$('div[data-section-context]')
				.hide()
				.filter('[data-section-context = ' + id + ']')
				.show();
		});

	$(document).ready(function() {
		$('select[name = "config[section]"]')
			.trigger('change');
	});
})(jQuery);