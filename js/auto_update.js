jQuery( document ).ready( function( $ ) {
	$( '.gf-toggle-auto-update' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( '.gf-update-setting' ).removeClass( 'hidden' ).attr( 'aria-hidden', false );
		var url    = gf_update_ajax.ajaxurl;
		var task   = $( this ).attr( 'data-gfaction' );
		var nonce  = $( this ).data( 'nonce' );
		var action = 'update_auto_update_setting';
		var data   = {
			'action': action,
			'task': task,
			'nonce': nonce,
		};

		$.post( url, data, function ( response ) {
			if ( ! response.success ) {
				$( '.gf-auto-update-notice' ).html( response.data ).show();
			} else {
				var enabled        = $( '.auto-update-enabled span' );
				var disabled       = $( '.auto-update-disabled span' );
				var enabledNumber  = parseInt( enabled.text().replace( /[^\d]+/g, '' ), 10 ) || 0;
				var disabledNumber = parseInt( disabled.text().replace( /[^\d]+/g, '' ), 10 ) || 0;

				if ( 'enable-gf-updates' == task ) {
					$( '.gf-toggle-auto-update' ).attr( 'data-gfaction', 'disable-gf-updates' );
					$( '.gf-update-label' ).html( gf_update_ajax.disable_text );
					++enabledNumber;
					--disabledNumber;
				} else {
					$( '.gf-toggle-auto-update' ).attr( 'data-gfaction', 'enable-gf-updates' );
					$( '.gf-update-label' ).html( gf_update_ajax.enable_text );
					--enabledNumber;
					++disabledNumber;
				}

				enabledNumber  = Math.max( 0, enabledNumber );
				disabledNumber = Math.max( 0, disabledNumber );

				enabled.text( '(' + enabledNumber + ')' );
				disabled.text( '(' + disabledNumber + ')' );
			}
			$( '.gf-update-setting' ).addClass( 'hidden' ).attr( 'aria-hidden', true );
		} );
	});
});