
var __gf_timeout_handle;

gform.addAction( 'gform_input_change', function( elem, formId, fieldId ) {
	if( ! window.gf_form_conditional_logic ) {
		return;
	}
	var dependentFieldIds = rgars( gf_form_conditional_logic, [ formId, 'fields', gformExtractFieldId( fieldId ) ].join( '/' ) );
	if( dependentFieldIds ) {
		gf_apply_rules( formId, dependentFieldIds );
	}
}, 10 );

function gf_apply_rules(formId, fields, isInit){

	jQuery(document).trigger( 'gform_pre_conditional_logic', [ formId, fields, isInit ] );
	gform.utils.trigger( {
		event: 'gform/conditionalLogic/applyRules/start',
		native: false,
		data: { formId: formId, fields: fields, isInit: isInit },
	} );
	for(var i=0; i < fields.length; i++){
		gf_apply_field_rule(formId, fields[i], isInit, function(){
			var is_last_field = ( i >= fields.length - 1 );
			if( is_last_field ) {
				jQuery(document).trigger('gform_post_conditional_logic', [formId, fields, isInit]);
				gform.utils.trigger( {
					event: 'gform/conditionalLogic/applyRules/end',
					native: false,
					data: { formId: formId, fields: fields, isInit: isInit },
				} );
				if(window["gformCalculateTotalPrice"]){
					window["gformCalculateTotalPrice"](formId);
				}
			}
		});
	}
}

function gf_check_field_rule(formId, fieldId, isInit, callback){

	//if conditional logic is not specified for that field, it is supposed to be displayed
	var conditionalLogic = gf_get_field_logic( formId, fieldId );
	if ( ! conditionalLogic ) {
		return 'show';
	}

	var action = gf_get_field_action(formId, conditionalLogic["section"]);

	//If section is hidden, always hide field. If section is displayed, see if field is supposed to be displayed or hidden
	if(action != "hide")
		action = gf_get_field_action(formId, conditionalLogic["field"]);

	return action;
}

/**
 * Retrieves the conditional logic properties for the specified field.
 *
 * @since 2.4.16
 *
 * @param {(string|number)} formId  The ID of the current form.
 * @param {(string|number)} fieldId The ID of the current field.
 *
 * @return {(boolean|object)} False or the field conditional logic properties.
 */
function gf_get_field_logic(formId, fieldId) {
	var formConditionalLogic = rgars( window, 'gf_form_conditional_logic/' + formId );
	if ( ! formConditionalLogic ) {
		return false;
	}

	var conditionalLogic = rgars( formConditionalLogic, 'logic/' + fieldId );
	if ( conditionalLogic ) {
		return conditionalLogic;
	}

	var dependents = rgar( formConditionalLogic, 'dependents' );
	if ( ! dependents ) {
		return false;
	}

	// Attempting to get section field conditional logic instead.
	for ( var key in dependents ) {
		if ( dependents[key].indexOf( fieldId ) !== -1 ) {
			return rgars( formConditionalLogic, 'logic/' + key );
		}
	}

	return false;
}

function gf_apply_field_rule(formId, fieldId, isInit, callback){

	var action = gf_check_field_rule(formId, fieldId, isInit, callback);

	gf_do_field_action(formId, action, fieldId, isInit, callback);

	var conditionalLogic = window["gf_form_conditional_logic"][formId]["logic"][fieldId];
	//perform conditional logic for the next button
	if(conditionalLogic["nextButton"]){
		action = gf_get_field_action(formId, conditionalLogic["nextButton"]);
		gf_do_next_button_action(formId, action, fieldId, isInit);
	}

}

function gf_get_field_action(formId, conditionalLogic){
	if(!conditionalLogic)
		return "show";

	var matches = 0;
	for(var i = 0; i < conditionalLogic["rules"].length; i++){
		/**
		 * Filter the conditional logic rule before it is evaluated on the frontend.
		 *
		 * @param {object}          rule             The conditional logic rule about to be evaluated.
		 * @param {(string|number)} formId           The current form ID.
		 * @param {object}          conditionalLogic All details required to evaluate an objects conditional logic.
		 *
		 * @since 2.4.22
		 */
		var rule = gform.applyFilters( 'gform_rule_pre_evaluation', jQuery.extend( {}, conditionalLogic["rules"][i] ), formId, conditionalLogic );
		if(gf_is_match(formId, rule))
			matches++;
	}

	var action;
	if( (conditionalLogic["logicType"] == "all" && matches == conditionalLogic["rules"].length) || (conditionalLogic["logicType"] == "any"  && matches > 0) )
		action = conditionalLogic["actionType"];
	else
		action = conditionalLogic["actionType"] == "show" ? "hide" : "show";

	return action;
}

function gf_is_match( formId, rule ) {

	var $               = jQuery,
		inputId         = rule['fieldId'],
		fieldId         = gformExtractFieldId( inputId ),
		inputIndex      = gformExtractInputIndex( inputId ),
		isInputSpecific = inputIndex !== false,
		$inputs;

	if( isInputSpecific ) {
		$inputs = $( '#input_{0}_{1}_{2}'.gformFormat( formId, fieldId, inputIndex ) );
	} else {
		$inputs = $( 'input[id="input_{0}_{1}"], input[id^="input_{0}_{1}_"], input[id^="choice_{0}_{1}_"], select#input_{0}_{1}, textarea#input_{0}_{1}'.gformFormat( formId, fieldId ) );
	}

	var isCheckable = $.inArray( $inputs.attr( 'type' ), [ 'checkbox', 'radio' ] ) !== -1;
	var isMatch     = isCheckable ? gf_is_match_checkable( $inputs, rule, formId, fieldId ) : gf_is_match_default( $inputs.eq( 0 ), rule, formId, fieldId );

	return gform.applyFilters( 'gform_is_value_match', isMatch, formId, rule );
}

function gf_is_match_checkable( $inputs, rule, formId, fieldId ) {

	// Rule is checking if the checkable is/isn't blank. Return a specific check for that use-case.
	if ( rule.value === '' ) {
		return rule.operator === 'is' ? gf_is_checkable_empty( $inputs ) : ! gf_is_checkable_empty( $inputs );
	}

	var isMatch = false;

	$inputs.each( function() {

		var $input           = jQuery( this ),
			fieldValue       = gf_get_value( $input.val() ),
			isRangeOperator  = jQuery.inArray( rule.operator, [ '<', '>' ] ) !== -1,
			isStringOperator = jQuery.inArray( rule.operator, [ 'contains', 'starts_with', 'ends_with' ] ) !== -1;

		// if we are looking for a specific value and this is not it, skip
		if( fieldValue != rule.value && ! isRangeOperator && ! isStringOperator ) {
			return; // continue
		}

		// force an empty value for unchecked items
		if( ! $input.is( ':checked' ) ) {
			fieldValue = '';
		}
		// if the 'other' choice is selected, get the value from the 'other' text input
		else if ( fieldValue == 'gf_other_choice' ) {
			fieldValue = jQuery( '#input_{0}_{1}_other'.gformFormat( formId, fieldId ) ).val();
		}

		if( gf_matches_operation( fieldValue, rule.value, rule.operator ) ) {
			isMatch = true;
			return false; // break
		}

	} );

	return isMatch;
}

/**
 * Check if a collection of checkable inputs has any checked,
 * or if they are all unchecked.
 *
 * @param {jQuery} $inputs A collection of inputs to check.
 *
 * @returns {boolean}
 */
function gf_is_checkable_empty( $inputs ) {
	var isEmpty = true;

	$inputs.each( function() {
		if ( jQuery( this ).is( ':checked' ) ) {
			isEmpty = false;
		}
	} );

	return isEmpty;
}

function gf_is_match_default( $input, rule, formId, fieldId ) {

	var val           = $input.val(),
		values        = ( val instanceof Array ) ? val : [ val ], // transform regular value into array to support multi-select (which returns an array of selected items)
		matchCount    = 0,
		valuesLength  = Math.max( values.length, 1 ); // jQuery 3.0: Make sure our length is at least 1 so that the following loop fires.

	for( var i = 0; i < valuesLength; i++ ) {

		// fields with pipes in the value will use the label for conditional logic comparison
		var hasLabel   = values[i] ? values[i].indexOf( '|' ) >= 0 : true,
			fieldValue = gf_get_value( values[i] );

		var fieldNumberFormat = gf_get_field_number_format( rule.fieldId, formId, 'value' );
		if( fieldNumberFormat && ! hasLabel ) {
			fieldValue = gf_format_number( fieldValue, fieldNumberFormat );
		}

		var ruleValue = rule.value;
		//if ( fieldNumberFormat ) {
		//	ruleValue = gf_format_number( ruleValue, fieldNumberFormat );
		//}

		if( gf_matches_operation( fieldValue, ruleValue, rule.operator ) ) {
			matchCount++;
		}

	}

	// if operator is 'isnot', none of the values can match
	var isMatch = rule.operator == 'isnot' ? matchCount == valuesLength : matchCount > 0;

	return isMatch;
}

function gf_format_number( value, fieldNumberFormat ) {

	decimalSeparator = '.';

	if( fieldNumberFormat == 'currency' ) {
		decimalSeparator = gformGetDecimalSeparator( 'currency' );
	} else if( fieldNumberFormat == 'decimal_comma' ) {
		decimalSeparator = ',';
	} else if( fieldNumberFormat == 'decimal_dot' ) {
		decimalSeparator = '.';
	}

	// transform to a decimal dot number
	value = gformCleanNumber( value, '', '', decimalSeparator );

	/**
	 * Looking at format specified by wp locale creates issues. When performing conditional logic, all numbers will be formatted to decimal dot and then compared that way. AC
	 */
	// now transform to number specified by locale
	// if( window['gf_number_format'] && window['gf_number_format'] == 'decimal_comma' ) {
	//     value = gformFormatNumber( value, -1, ',', '.' );
	// }

	if( ! value ) {
		value = 0;
	}

	number = value.toString();

	return number;
}

function gf_try_convert_float(text){

	/*
	 * The only format that should matter is the field format. Attempting to do this by WP locale creates a lot of issues with consistency.
	 * var format = window["gf_number_format"] == "decimal_comma" ? "decimal_comma" : "decimal_dot";
	 */

	var format = 'decimal_dot';
	if( gformIsNumeric( text, format ) ) {
		var decimal_separator = format == "decimal_comma" ? "," : ".";
		return gformCleanNumber( text, "", "", decimal_separator );
	}

	return text;
}

function gf_matches_operation(val1, val2, operation){
	val1 = val1 ? val1.toLowerCase() : "";
	val2 = val2 ? val2.toLowerCase() : "";

	switch(operation){
		case "is" :
			return val1 == val2;
			break;

		case "isnot" :
			return val1 != val2;
			break;

		case ">" :
			val1 = gf_try_convert_float(val1);
			val2 = gf_try_convert_float(val2);

			return gformIsNumber(val1) && gformIsNumber(val2) ? val1 > val2 : false;
			break;

		case "<" :
			val1 = gf_try_convert_float(val1);
			val2 = gf_try_convert_float(val2);

			return gformIsNumber(val1) && gformIsNumber(val2) ? val1 < val2 : false;
			break;

		case "contains" :
			return val1.indexOf(val2) >=0;
			break;

		case "starts_with" :
			return val1.indexOf(val2) ==0;
			break;

		case "ends_with" :
			var start = val1.length - val2.length;
			if(start < 0)
				return false;

			var tail = val1.substring(start);
			return val2 == tail;
			break;
	}
	return false;
}

function gf_get_value(val){
	if(!val)
		return "";

	val = val.split("|");
	return val[0];
}

function gf_do_field_action(formId, action, fieldId, isInit, callback){
	var conditional_logic = window["gf_form_conditional_logic"][formId];
	var dependent_fields = conditional_logic["dependents"][fieldId];

	for(var i=0; i < dependent_fields.length; i++){
		var targetId = fieldId == 0 ? "#gform_submit_button_" + formId : "#field_" + formId + "_" + dependent_fields[i];
		var defaultValues = conditional_logic["defaults"][dependent_fields[i]];

		//calling callback function on the last dependent field, to make sure it is only called once
		do_callback = (i+1) == dependent_fields.length ? callback : null;

		/**
		 * Allow add-ons to abort gf_do_action() function.
		 *
		 * @since 2.6.2
		 *
		 * @param bool   $doAbort      The value being filtered. True to abort conditional logic action, false to continue. Defaults to false.
		 * @param string $action       The conditional logic action that will be performed. Possible values: show or hide
		 * @param string $targetId     HTML element id that will be the targed of the conditional logic action.
		 * @param bool   $doAnimation  True to perform animation while showing/hiding field. False to hide/show field without animation.
		 * @param array  $defaultValue Array containg default field values.
		 * @param bool   $isInit       True if form is being initialized (i.e. before user has interacted with any input). False otherwise.
		 * @param array  $formId       The current form ID.
		 * @param func   $do_callback   Callback function to be executed after conditional logic is executed.
		 */
		let abort = gform.applyFilters( 'gform_abort_conditional_logic_do_action', false, action, targetId, conditional_logic[ "animation" ], defaultValues, isInit, formId, do_callback );
		if ( ! abort ) {
			gf_do_action( action, targetId, conditional_logic[ "animation" ], defaultValues, isInit, do_callback, formId );
		}

		gform.doAction('gform_post_conditional_logic_field_action', formId, action, targetId, defaultValues, isInit);
	}
}

function gf_do_next_button_action(formId, action, fieldId, isInit){
	var conditional_logic = window["gf_form_conditional_logic"][formId];
	var targetId = "#gform_next_button_" + formId + "_" + fieldId;

	/**
	 * Allow add-ons to abort gf_do_action() function.
	 *
	 * @since 2.6.2
	 *
	 * @param bool   $doAbort      The value being filtered. True to abort conditional logic action, false to continue. Defaults to false.
	 * @param string $action       The conditional logic action that will be performed. Possible values: show or hide
	 * @param string $targetId     HTML element id that will be the targed of the conditional logic action.
	 * @param bool   $doAnimation  True to perform animation while showing/hiding field. False to hide/show field without animation.
	 * @param array  $defaultValue Array containg default field values.
	 * @param bool   $isInit       True if form is being initialized (i.e. before user has interacted with any input). False otherwise.
	 * @param array  $formId       The current form ID.
	 * @param func   $do_callback   Callback function to be executed after conditional logic is executed.
	 */
	let abort = gform.applyFilters( 'gform_abort_conditional_logic_do_action', false, action, targetId, conditional_logic[ "animation" ], null, isInit, formId, null );
	if ( ! abort ) {
		gf_do_action( action, targetId, conditional_logic[ "animation" ], null, isInit, null, formId );
	}
}

function gf_do_action(action, targetId, useAnimation, defaultValues, isInit, callback, formId){
	var $target = jQuery( targetId );

	/**
	 * Do not re-enable inputs that are disabled by default. Check if field's inputs have been assessed. If not, add
	 * designator class so these inputs are exempted below.
	 */
	if( ! $target.data( 'gf-disabled-assessed' ) ) {
		$target.find( ':input:disabled' ).addClass( 'gf-default-disabled' );
		$target.data( 'gf-disabled-assessed', true );
	}

	// honeypot should not be impacted by conditional logic.
	if( $target.hasClass( 'gfield--type-honeypot') ) {
		return;
	}

	if(action == "show"){

		// reset tabindex for selects
		$target.find( 'select' ).each( function() {
			var $select = jQuery( this );
			$select.attr( 'tabindex', $select.data( 'tabindex' ) );
		} );

		if(useAnimation && !isInit){
			if($target.length > 0){
				$target.find(':input:hidden:not(.gf-default-disabled)').removeAttr( 'disabled' );
				if ( $target.is( 'input[type="submit"]' ) || $target.hasClass( 'gform_next_button' ) ) {
					$target.removeAttr( 'disabled' ).css( 'display', '' );
					$target.attr( 'data-conditional-logic', 'hidden' );
					if ( '1' == gf_legacy.is_legacy ) {
						// for legacy markup, remove screen reader class.
						$target.removeClass( 'screen-reader-text' );
					}
				}
				$target.slideDown(callback);
			} else if(callback){
				callback();
			}
		}
		else{

			var display = $target.data('gf_display');

			// set display if previous (saved) display isn't set for any reason
			if ( display == '' || display == 'none' ){
				display = '1' === gf_legacy.is_legacy ? 'list-item' : 'block';
			}
			$target.find(':input:hidden:not(.gf-default-disabled)').removeAttr( 'disabled' ).attr( 'data-conditional-logic', 'visible' );

			// Handle conditional submit and next buttons.
			if ( $target.is( 'input[type="submit"]' ) || $target.hasClass( 'gform_next_button' ) ) {
				$target.removeAttr( 'disabled' ).css( 'display', '' );
				$target.attr( 'data-conditional-logic', 'visible' );
				if ( '1' == gf_legacy.is_legacy ) {
					// for legacy markup, remove screen reader class.
					$target.removeClass( 'screen-reader-text' );
				}
			} else {
				$target.css( 'display', display );
				if( display == 'none' ) {
					$target.attr( 'data-conditional-logic', 'hidden' );
				} else {
					$target.attr( 'data-conditional-logic', 'visible' );
				}
			}

			if(callback){
				callback();
			}
		}
	}
	else{

		//if field is not already hidden, reset its values to the default
		var child = $target.children().first();
		if (child.length > 0){
			var reset = gform.applyFilters('gform_reset_pre_conditional_logic_field_action', true, formId, targetId, defaultValues, isInit);

			if(reset && !gformIsHidden(child)){
				gf_reset_to_default(targetId, defaultValues);
			}
		}

		// remove tabindex and stash as a data attr for selects
		$target.find( 'select' ).each( function() {
			var $select = jQuery( this );
			$select.data( 'tabindex', $select.attr( 'tabindex' ) ).removeAttr( 'tabindex' );
		} );

		//Saving existing display so that it can be reset when showing the field
		if( ! $target.data('gf_display') ){
			$target.data('gf_display', $target.css('display'));
		}

		if(useAnimation && !isInit){
			if( $target.is( 'input[type="submit"]' ) || $target.hasClass( 'gform_next_button' ) ) {
				$target.attr( 'disabled', 'disabled' ).hide();
				if ( '1' === gf_legacy.is_legacy ) {
					// for legacy markup, let screen readers read the button.
					$target.addClass( 'screen-reader-text' );
				}
			} else if ( $target.length > 0 && $target.is( ":visible" ) ) {
				$target.slideUp( callback );
			} else if ( callback ) {
				callback();
			}
		} else{

			// Handle conditional submit and next buttons.
			if ( $target.is( 'input[type="submit"]' ) || $target.hasClass( 'gform_next_button' ) ) {
				$target.attr( 'disabled', 'disabled' ).hide();
				$target.attr( 'data-conditional-logic', 'hidden' );
				if ( '1' === gf_legacy.is_legacy ) {
					// for legacy markup, let screen readers read the button.
					$target.addClass( 'screen-reader-text' );
				}
			} else {
				$target.css( 'display', 'none' );
				$target.attr( 'data-conditional-logic', 'hidden' );
			}
			$target.find(':input:hidden:not(.gf-default-disabled)').attr( 'disabled', 'disabled' );
			if(callback){
				callback();
			}
		}
	}

}

function gf_reset_to_default(targetId, defaultValue){

	var dateFields = jQuery( targetId ).find( '.gfield_date_month input, .gfield_date_day input, .gfield_date_year input, .gfield_date_dropdown_month select, .gfield_date_dropdown_day select, .gfield_date_dropdown_year select' );
	if( dateFields.length > 0 ) {

		dateFields.each( function(){

			var element = jQuery( this );

			// defaultValue is associative array (i.e. [ m: 1, d: 13, y: 1987 ] )
			if( defaultValue ) {

				var key = 'd';
				if (element.parents().hasClass('gfield_date_month') || element.parents().hasClass('gfield_date_dropdown_month') ){
					key = 'm';
				}
				else if(element.parents().hasClass('gfield_date_year') || element.parents().hasClass('gfield_date_dropdown_year') ){
					key = 'y';
				}

				val = defaultValue[ key ];

			}
			else{
				val = "";
			}

			if(element.prop("tagName") == "SELECT" && val != '' )
				val = parseInt(val, 10);


			if(element.val() != val)
				element.val(val).trigger("change");
			else
				element.val(val);

		});

		return;
	}

	//cascading down conditional logic to children to support nested conditions
	//text fields and drop downs, filter out list field text fields name with "_shim"
	var target = jQuery(targetId).find( 'select, input[type="text"]:not([id*="_shim"]), input[type="number"], input[type="hidden"], input[type="email"], input[type="tel"], input[type="url"], textarea' );
	var target_index = 0;

	// When a List field is hidden via conditional logic during a page submission, the markup will be reduced to a
	// single row. Add enough rows/inputs to satisfy the default value.
	if( defaultValue && target.parents( '.ginput_list' ).length > 0 && target.length < defaultValue.length ) {
		while( target.length < defaultValue.length ) {
			gformAddListItem( target.eq( 0 ), 0 );
			target = jQuery(targetId).find( 'select, input[type="text"]:not([id*="_shim"]), input[type="number"], textarea' );
		}
	}

	target.each(function(){

		var val = "";

		var element = jQuery(this);

		// Only reset Single Product and Shipping hidden inputs.
		if( element.is( '[type="hidden"]' ) && ! gf_is_hidden_pricing_input( element ) ) {
			return;
		}

		//get name of previous input field to see if it is the radio button which goes with the "Other" text box
		//otherwise field is populated with input field name
		var radio_button_name = element.prev("input").attr("value");
		if(radio_button_name == "gf_other_choice"){
			val = element.attr("value");
		}
		else if( jQuery.isArray( defaultValue ) && ! element.is( 'select[multiple]' ) ) {
			val = defaultValue[target_index];
		}
		else if(jQuery.isPlainObject(defaultValue)){
			val = defaultValue[element.attr("name")];
			if( ! val && element.attr( 'id' ) ) {
				// 'input_123_3_1' => '3.1'
				var inputId = element.attr( 'id' ).split( '_' ).slice( 2 ).join( '.' );
				val = defaultValue[ inputId ];
			}
			if( ! val && element.attr( 'name' ) ) {
				var inputId = element.attr( 'name' ).split( '_' )[1];
				val = defaultValue[ inputId ];
			}
		}
		else if(defaultValue){
			val = defaultValue;
		}

		if( element.is('select:not([multiple])') && ! val ) {
			val = element.find( 'option' ).not( ':disabled' ).eq(0).val();
		}

		if(element.val() != val) {
			element.val(val).trigger('change');
			if (element.is('select') && element.next().hasClass('chosen-container')) {
				element.trigger('chosen:updated');
			}
			// Check for Single Product & Shipping input and force visual price update.
			if( gf_is_hidden_pricing_input( element ) ) {
				var ids = gf_get_ids_by_html_id( element.parents( '.gfield' ).attr( 'id' ) );
				jQuery( '#input_' + ids[0] + '_' + ids[1] ).text( gformFormatMoney( element.val() ) );
				element.val( gformFormatMoney( element.val() ) );
			}
		}
		else{
			element.val(val);
		}

		target_index++;
	});

	//checkboxes and radio buttons
	var elements = jQuery(targetId).find('input[type="radio"], input[type="checkbox"]:not(".copy_values_activated")');

	elements.each(function(){

		//is input currently checked?
		var isChecked = jQuery(this).is(':checked') ? true : false;

		//does input need to be marked as checked or unchecked?
		var doCheck = defaultValue ? jQuery.inArray(jQuery(this).attr('id'), defaultValue) > -1 : false;

		//if value changed, trigger click event
		if(isChecked != doCheck){
			//setting input as checked or unchecked appropriately

			if(jQuery(this).attr("type") == "checkbox"){
				jQuery(this).trigger('click');
			}
			else{
				jQuery(this).prop('checked', doCheck).change();
			}

		}
	});

}

function gf_is_hidden_pricing_input( element ) {

	// Check for Single Product fields.
	if( element.attr( 'id' ) && element.attr( 'id' ).indexOf( 'ginput_base_price' ) === 0 ) {
		return true;
	}

	if( element.attr( 'type' ) !== 'hidden' ) {
		return false;
	}

	// Check for Shipping fields.
	return element.parents( '.gfield_shipping' ).length;
}
