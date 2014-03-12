/* global findPosts */

(function($){

	/* hidden input for new parent */
	var $input = $('#marco-post-parent');

	/* methods */
	var method = {
		val: function(value) {
			$input.val(value).attr('name', 'marco-post-parent');
			$('#marco-attachment-parent').addClass('marco-modified');
		},
		removeVal: function() {
			$input.val('').removeAttr('name');
			$('#marco-attachment-parent').removeClass('marco-modified');
			$('#marco-s-new-anna').remove();
		}
	};

	/* open find posts div */
	$('#marco-find-posts').click(function(e) {
		e.preventDefault();
		findPosts.open('media[]', MARCO_ATT.postID);
	});

	/* find posts action on 'find-posts-div' */
	$('#find-posts-submit').click(function(e) {
		e.preventDefault();
		var array = $('#marco-find-posts-form').serializeArray(),
			found = '';
		for ( var i in array )
			if ('found_post_id' === array[i].name)
				found = array[i].value;
		if ('' !== found && MARCO_ATT.parentID !== found) {
			method.val(found);
			$.ajax({
				url: MARCO_ATT.endpoint,
				type: 'POST',
				dataType: 'html',
				data: {
					action: 'return_ajax_anna',
					annaid: found
				}
			})
			.done(function(d) {
				$('#marco-s-anna').after($(d));
			});
		} else {
			method.removeVal();
		}
		findPosts.close();
	});

	/* click separate */
	$('#marco-separate-post').click(function(e) {
		e.preventDefault();
		method.val(0);
	});

	/* click cancel */
	$('#marco-cancel').click(function(e) {
		e.preventDefault();
		method.removeVal();
	});

})(jQuery);