(function ($, gform, gform_i18n) {

	/**
	 * @function getDatepickerI18n
	 * @description Return month and day of week strings for use in the datepicker instances.
	 * @since 2.5
	 *
	 * @returns {{
	 *  dayNamesMin: *[],
	 *  monthNamesShort: *[]
	 * }}
	 */

	function getDatepickerI18n() {
		var i18n = gform_i18n.datepicker;
		return {
			dayNamesMin: [
				i18n.days.sunday,
				i18n.days.monday,
				i18n.days.tuesday,
				i18n.days.wednesday,
				i18n.days.thursday,
				i18n.days.friday,
				i18n.days.saturday,
			],
			monthNamesShort: [
				i18n.months.january,
				i18n.months.february,
				i18n.months.march,
				i18n.months.april,
				i18n.months.may,
				i18n.months.june,
				i18n.months.july,
				i18n.months.august,
				i18n.months.september,
				i18n.months.october,
				i18n.months.november,
				i18n.months.december,
			],
			firstDay: i18n.firstDay,
			iconText: i18n.iconText,
		};
	}

	/**
	 * @function getDatepickerBaseOptions
	 * @description Return base options object that configures the datepicker.
	 * @param $element The datepicker trigger.
	 * @since 2.5
	 *
	 * @returns {{
	 *  suppressDatePicker: boolean,
	 *  changeMonth: boolean,
	 *  changeYear: boolean,
	 *  onClose: onClose,
	 *  yearRange: string,
	 *  dateFormat: string,
	 *  showOn: string,
	 *  dayNamesMin: *[],
	 *  monthNamesShort: *[],
	 *  beforeShow: (function(*, *): boolean),
	 *  showOtherMonths: boolean
	 * }}
	 */

	function getDatepickerBaseOptions( $element ) {
		var i18n = getDatepickerI18n();
		var isThemeDatepicker = $element.closest( '.gform_wrapper' ).length > 0;
		var isPreview = $( '#preview_form_container' ).length > 0;
		var isRTL = window.getComputedStyle($element[0], null).getPropertyValue('direction') === 'rtl';
		var formTheme = isThemeDatepicker ? $element.closest( '.gform_wrapper' ).data( 'form-theme' ) : 'gravity-theme';
		var formId = isThemeDatepicker ? $element.closest( '.gform_wrapper' ).attr( 'id' ).replace( 'gform_wrapper_', '' ) : '';
		var formPageInstance = isThemeDatepicker ? $element.closest( '.gform_wrapper' ).attr( 'data-form-index' ) : '';

		return {
			yearRange: '-100:+20',
			showOn: 'focus',
			dateFormat: 'mm/dd/yy',
			dayNamesMin: i18n.dayNamesMin,
			monthNamesShort: i18n.monthNamesShort,
			firstDay: i18n.firstDay,
			changeMonth: true,
			changeYear: true,
			isRTL: isRTL,
			showOtherMonths: isThemeDatepicker,
			suppressDatePicker: false,
			onClose: function() {
				var self = this;
				$element.focus();
				this.suppressDatePicker = true;
				setTimeout( function() {
					self.suppressDatePicker = false;
				}, 200 );
			},
			beforeShow: function( input, inst ) {

				// Remove any classes that were added before as it could have been added to a different datepicker.
				inst.dpDiv[0].classList.remove( 'gform-theme-datepicker' );
				inst.dpDiv[0].classList.remove( 'gravity-theme' );
				inst.dpDiv[0].classList.remove( 'gform-theme' );
				inst.dpDiv[0].classList.remove( 'gform-legacy-datepicker' );
				inst.dpDiv[0].classList.remove( 'gform-theme--framework' );
				inst.dpDiv[0].classList.remove( 'gform-theme--foundation' );
				inst.dpDiv[0].classList.remove( 'gform-theme--orbital' );

				// Add classes based on the form theme
				if ( isThemeDatepicker ) {
					inst.dpDiv[ 0 ].classList.add( 'gform-theme-datepicker' );
					$( inst.dpDiv[ 0 ] ).attr( 'data-parent-form', formId + '_' + formPageInstance );
				}

				if ( formTheme === undefined || formTheme === 'gravity-theme' ) {
					$( inst.dpDiv[0] ).addClass( 'gravity-theme' );
				} else if ( formTheme === 'legacy' ) {
					$( inst.dpDiv[0] ).addClass( 'gform-legacy-datepicker' );
				} else {
					$( inst.dpDiv[0] ).addClass( 'gform-theme--' + formTheme );
					if ( formTheme === 'orbital' ) {
						$( inst.dpDiv[0] ).addClass( 'gform-theme--framework' );
						$( inst.dpDiv[0] ).addClass( 'gform-theme--foundation' );
					}
				}

				// If the form is in preview mode and the site is RTL, adjust the datepicker position.
				if ( isRTL && isPreview ) {
					var $inputContainer = $( input ).closest( '.gfield' );
					var rightOffset = $( document ).outerWidth() - ( $inputContainer.offset().left + $inputContainer.outerWidth() );
					inst.dpDiv[ 0 ].style.right = rightOffset + 'px';
				}

				return ! this.suppressDatePicker;
			},
		};
	}

	/**
	 * @function initSingleDatepicker
	 * @description Initialize a datepicker assigning various additional options based on the trigger element.
	 * @param $element The datepicker trigger.
	 * @since 2.4
	 */

	function initSingleDatepicker( $element ) {
		var i18n = getDatepickerI18n();
		var inputId = $element.attr( 'id' ) ? $element.attr( 'id' ) : '';
		var optionsObj = getDatepickerBaseOptions( $element );

		if ( $element.hasClass( 'dmy' ) ) {
			optionsObj.dateFormat = 'dd/mm/yy';
		} else if ( $element.hasClass( 'dmy_dash' ) ) {
			optionsObj.dateFormat = 'dd-mm-yy';
		} else if ( $element.hasClass( 'dmy_dot' ) ) {
			optionsObj.dateFormat = 'dd.mm.yy';
		} else if ( $element.hasClass( 'ymd_slash' ) ) {
			optionsObj.dateFormat = 'yy/mm/dd';
		} else if ( $element.hasClass( 'ymd_dash' ) ) {
			optionsObj.dateFormat = 'yy-mm-dd';
		} else if ( $element.hasClass( 'ymd_dot' ) ) {
			optionsObj.dateFormat = 'yy.mm.dd';
		}

		if ( $element.hasClass( 'gdatepicker_with_icon' ) ) {
			optionsObj.showOn = 'both';
			optionsObj.buttonImage = $element.parent().siblings( "[id^='gforms_calendar_icon_input']" ).val();
			optionsObj.buttonImageOnly = true;
			optionsObj.buttonText = i18n.iconText;
		} else {
			optionsObj.showOn = 'focus';
		}

		inputId = inputId.split( '_' );

		// allow the user to override the datepicker options object
		optionsObj = gform.applyFilters( 'gform_datepicker_options_pre_init', optionsObj, inputId[ 1 ], inputId[ 2 ], $element );

		$element.datepicker( optionsObj );

		// We give the input focus after selecting a date which differs from default Datepicker behavior; this prevents
		// users from clicking on the input again to open the datepicker. Let's add a manual click event to handle this.
		if ( $element.is( ':input' ) ) {
			$element.click( function() {
				$element.datepicker( 'show' );
			} );
		}
	}

	/**
	 * @function initDatepickers
	 * @description Iterate over uninitialized datepickers and init. Exposed on window as gformInitDatepicker.
	 * Note: this function powers both admin and theme datepickers.
	 * @since 2.4
	 */

	function initDatepickers() {
		$( '.gform-datepicker:not(.initialized)' ).each( function() {
			var $element = $( this );
			initSingleDatepicker( $element );
			$element.addClass( 'initialized' );
		} );
	}

	$( document ).ready( initDatepickers );

	// Make all and single init functions public for add-ons.
	// Naming is done in the 2.4 backwards compatible way.
	window.gformInitDatepicker = initDatepickers;
	window.gformInitSingleDatepicker = initSingleDatepicker;

})(jQuery, gform, gform_i18n);
