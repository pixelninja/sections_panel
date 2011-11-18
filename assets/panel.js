(function($) {
	// When someone selects a section, show the fields select box for that section.
	$('select[name = "config[section]"]')
		.live('change', function() {
			var id = $(this).val();

			$('div[data-section-context]')
				.hide()
				.filter('[data-section-context = ' + id + ']')
				.show();
		});
})(jQuery);