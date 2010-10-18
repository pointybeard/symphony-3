jQuery(document).ready(function() {
	var url = jQuery('script[src *= "upload_progress"]').get(0).src.match('(.*)/publish.js$')[1];
	var unique_id = Date.now();
	var input = jQuery('<input />')
		.attr('type', 'hidden')
		.prependTo(jQuery('form'));
	var indicator = jQuery('<div />')
		.addClass('progress')
		.append('<div />')
		.insertAfter(jQuery('form > .actions button:first'))
		.hide();
	var update = function(waiting) {
		jQuery.ajax({
			async:		false,
			url:		url + '/status.php?for=' + input.val(),
			success:	function(data) {
				if (!data) {
					if (waiting) setTimeout(function() { update(true); }, 1000);
					
					indicator.hide()
						.children().css('width', '0');
					
					return false;
				}
				
				indicator.show()
					.children()
					.css('width', (100 * data.bytes_uploaded / data.bytes_total) + '%');
				
				setTimeout(function() { update(false); }, 1000);
			}
		});
	};
	
	// Load method information:
	jQuery.ajax({
		url:		url + '/method.php',
		success:	function(data) {
			input
				.attr('name', data.input_name)
				.val(data.input_value);
		}
	});
	
	// Start upload:
	jQuery('form').bind('submit', function() {
		update(true);
	});
});