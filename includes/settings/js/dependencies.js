var GF_Settings_Dependencies = function ( args ) {

	var self = this;
	self.args = args;

	/**
	 * Bind change events for dependent fields.
	 *
	 * @since 2.5
	 */
	self.bindEvents = function () {

		for ( var i = 0; i < self.args.fields.length; i ++ ) {

			var rule = self.args.fields[ i ],
				inputName = self.args.prefix + '_' + rule.field;

			// Add event for each checkbox value.
			if ( rule.field_type === 'checkbox' ) {

				for ( var ii = 0; ii < rule.values.length; ii++ ) {

					var checkboxName = self.args.prefix + '_' + rule.values[ ii ],
						$checkboxes = document.querySelectorAll( '[name="' + checkboxName + '"]' );

					for ( var jj = 0; jj < $checkboxes.length; jj++ ) {
						$checkboxes[ jj ].addEventListener( 'change', self.evaluateLogic );
					}

				}

			} else {

				var $elements = document.querySelectorAll( '[name="' + inputName + '"]' );

				for ( var ii = 0; ii < $elements.length; ii++ ) {
					$elements[ ii ].addEventListener( 'change', self.evaluateLogic );
				}

			}

		}

	};

	/**
	 * Displays or hides the targeted field based on logic rules.
	 *
	 * @since 2.5
	 */
	self.evaluateLogic = function() {

		var evaluatedRules = 0,
			passedLogic = false;

		for ( var i = 0; i < self.args.fields.length; i++ ) {
			if ( self.evaluateRule( self.args.fields[ i ] ) ) {
				evaluatedRules++;
			}
		}

		if ( self.args.operator.toUpperCase() === 'ALL' && evaluatedRules === self.args.fields.length ) {
			passedLogic = true;
		} else if ( self.args.operator.toUpperCase() === 'ANY' && evaluatedRules > 0 ) {
			passedLogic = true;
		}

		if ( passedLogic ) {
			self.getTargetObject().style.display = '';
		} else {
			self.getTargetObject().style.display = 'none';
		}

	};

	/**
	 * Determine if a value is found in the rule's defined values.
	 *
	 * @since 2.5
	 *
	 * @param {Object} rule Rule object (contains field name and values).
	 *
	 * @returns {boolean}
	 */
	self.evaluateRule = function ( rule ) {

		var value,
			inputName = self.args.prefix + '_' + rule.field;

		// If rule has a callback and it exists, return it.
		if ( self.args.callback && window[ self.args.callback ] ) {
			return window[ self.args.callback ].call( self, rule );
		}

		// If rule values are not an array, force array.
		if ( ! Array.isArray( rule.values ) ) {
			if ( rule.values === undefined ) {
				rule.values = new Array( '_notempty_' );
			} else {
				rule.values = new Array( rule.values );
			}
		}

		// Handle checkbox field separately.
		if ( rule.field_type === 'checkbox' ) {

			// Loop through values. If choice is selected, return true.
			for ( var i = 0; i < rule.values.length; i++ ) {
				if ( document.querySelector( '[name="' + self.args.prefix + '_' + rule.values[ i ] + '"]' ).value == 1 ) {
					return true;
				}
			}

		} else {

			// Get field value.
			switch ( rule.field_type ) {

				case 'toggle':
					value = document.querySelector( '[name="' + inputName + '"]' ).checked;
					break;

				case 'radio':
					value = document.querySelector( '[name="' + inputName + '"]:checked' ).value;
					break;

				default:
					value = document.querySelector( '[name="' + inputName + '"]' ).value;
					break;

			}

			// Loop through rule values and test.
			for ( var i = 0; i < rule.values.length; i++ ) {
				if ( '_notempty_' === rule.values[ i ] && ( ( typeof value === 'string' && value.length > 0 ) || ( typeof value === 'boolean' && value === true ) ) ) {
					return true;
				} else if ( rule.values[ i ] === value ) {
					return true;
				}
			}

		}


		return false;

	};

	/**
	 * Returns target of dependency.
	 *
	 * @since 2.5
	 *
	 * @returns {HTMLElement}
	 */
	self.getTargetObject = function() {

		switch ( self.args.target.type ) {

			case 'save':
				return document.getElementById( 'gform-settings-save' );

			case 'section':
				return document.getElementById( self.args.target.field );

			case 'tab':
				return document.querySelector( '.gform-settings-tabs__navigation a[data-tab="' + self.args.target.field + '"]' );

			default:
				return document.getElementById( 'gform_setting_' + self.args.target.field );

		}

	};

	self.bindEvents();

};
