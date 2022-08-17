$(function() {
	/**
	 * Pager jump to on enter
	 */
	 $('.jump-to-page input').on('keydown', function(e) {
		if(e.which == 13) {
			jump_to_page($(this));
		}
	});
});

// Jump to function for pager
function jump_to_page(el) {
	id = $(el).prop('id').replace('jump-to-page', 'pager');

	val = $(el).val();
	lnk = document.createElement('a');
	lnk.href = $('#' + id + ' a').first().prop('href');

	params = new URLSearchParams(lnk.search);
	params.set('p', val);
	lnk.search = params.toString();
	window.location.href = lnk.href;
}