( function ( $ ) {

	$(document).ready(function() {
		gform.adminUtils.handleUnsavedChanges( '#gform-settings' );
	});

	var $container  = $( 'div[id="gform_setting_reset"]' ),
		$publicKey  = $( 'input[name="_gform_setting_public_key"]' ),
		$privateKey = $( 'input[name="_gform_setting_private_key"]' ),
		$reset      = $( 'input[name="_gform_setting_reset"]' );

	window.loadRecaptcha = function () {

		var $recaptcha = $( '#recaptcha' ),
			$save      = $( '#gform-settings-save' ),
			type       = $( 'input[name="_gform_setting_type"]:checked' ).val();

		// Flush existing state.
		window.___grecaptcha_cfg.clients = {};
		window.___grecaptcha_cfg.count = 0;
		$recaptcha.html( '' );
		$reset.val( 1 );

		// Reset key status.
		$( '#recpatcha .gform-settings-field__feedback' ).remove();

		// If no public or private key is provided, exit.
		if ( ! $publicKey.val() || ! $privateKey.val() ) {
			$save.prop( 'disabled', false );
			$container.hide();
			return;
		} else {
			$save.prop( 'disabled', true );
		}

		// Render reCAPTCHA.
		grecaptcha.render(
			'recaptcha',
			{
				'sitekey':        $publicKey.val(),
				'size':           type === 'invisible' ? type : '',
				'badge':          'inline',
				'error-callback': function () {
				},
				'callback':       function () {
					$save.prop( 'disabled', false );
				}
			}
		);

		switch ( type ) {

			case 'checkbox':
				$( '#gforms_checkbox_recaptcha_message, label[for="reset"]' ).show();
				break;

			case 'invisible':
				$( '#gforms_checkbox_recaptcha_message, label[for="reset"]' ).hide();
				break;

		}

		$container.show();

		if ( type === 'invisible' ) {
			grecaptcha.execute();
		}

	};

	$publicKey.on( 'change', loadRecaptcha );
	$privateKey.on( 'change', loadRecaptcha );

	$( 'input[name="_gform_setting_type"]' ).on( 'change', function () {
		loadRecaptcha();
	} );

} )( jQuery );
