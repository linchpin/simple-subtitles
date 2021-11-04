jQuery(function($) {
	var checkTitle = function( $this ) {
			if( $this.val() === '' ) {
				$this.prev().removeClass('screen-reader-text');
			} else {
				$this.prev().addClass('screen-reader-text');
			}
		},

		$subtitle = $('#simple_subtitle')
			.on('focus', function() {
				$(this).prev().addClass('screen-reader-text');
			}).on('blur', function() {
				checkTitle( $(this) );
			}).on('keyup', function() {
				checkTitle( $(this) );
			});

	if( $subtitle.val() === '' ) {
		$subtitle.prev().removeClass('screen-reader-text');
	}
});