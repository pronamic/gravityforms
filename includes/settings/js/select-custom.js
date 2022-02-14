window.addEventListener( 'load' , function() {

	var $selectOptions = document.querySelectorAll( '.gform-settings-field__select_custom select' );
	var $buttons       = document.querySelectorAll( '.gform-settings-select-custom__reset' );

	$selectOptions.forEach( function( $select ) {

		var $inputField = $select.parentNode.nextSibling;

		$select.addEventListener( 'change', function( e ) {

			if ( e.target.value !== 'gf_custom' ) {
				return;
			}

			// Hide drop down, show input.
			$select.style.display     = 'none';
			$inputField.style.display = 'block';

		} );

	} );

	$buttons.forEach( function( $button ) {

		var $inputField = $button.parentNode;

		$button.addEventListener( 'click', function( e ) {

			// Hide input, show drop down.
			$inputField.style.display = 'none';

			$selectOptions.forEach( function( $select ) {
				$select.value         = '';
				$select.style.display = 'block';
			} );

		} );

	} );

} );
