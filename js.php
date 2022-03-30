<?php
if ( ! class_exists( 'GFForms' ) ) {
	die();
}
?>
<script type="text/javascript" data-js-reload="editor-footer-js">
	var gforms_dragging = 0;
	var gforms_original_json;

	function DeleteCustomChoice() {
		if (!confirm(<?php echo json_encode( __( "Delete this custom choice list? 'OK' to delete, 'Cancel' to abort.", 'gravityforms' ) ); ?>))
			return;

		//Sending AJAX request
		jQuery.post( ajaxurl, {action: "gf_delete_custom_choice", name: gform_selected_custom_choice, gf_delete_custom_choice: "<?php echo wp_create_nonce( 'gf_delete_custom_choice' ) ?>"});

		//Updating UI
		delete gform_custom_choices[gform_selected_custom_choice];
		gform_selected_custom_choice = '';

		CloseCustomChoicesPanel();
		jQuery("#gfield_bulk_add_input").val('');
		InitBulkCustomPanel();
		LoadCustomChoices();
		DisplayCustomMessage(<?php echo json_encode( esc_html__( 'Item has been deleted.', 'gravityforms' ) )?>);
	}

	function SaveCustomChoices() {

		var name = jQuery('#custom_choice_name').val();
		if (name.length == 0) {
			alert(<?php echo json_encode( esc_html__( 'Please enter name.', 'gravityforms' ) ); ?>);
			return;
		}
		else if (gform_custom_choices[name] && name != gform_selected_custom_choice) {
			alert(<?php echo json_encode( esc_html__( 'This custom choice name is already in use. Please enter another name.', 'gravityforms' ) ); ?>);
			return;
		}

		var choices = jQuery('#gfield_bulk_add_input').val().split('\n');

		//Sending AJAX request
		jQuery.post(ajaxurl, {action: "gf_save_custom_choice", previous_name: gform_selected_custom_choice, new_name: name, choices: jQuery.toJSON(choices), gf_save_custom_choice: "<?php echo wp_create_nonce( 'gf_save_custom_choice' ) ?>"});

		//deleting existing custom choice
		if (gform_selected_custom_choice.length > 0)
			delete gform_custom_choices[gform_selected_custom_choice];

		//saving new custom choice
		gform_custom_choices[name] = choices;

		InitBulkCustomPanel();
		LoadCustomChoices();

		DisplayCustomMessage(<?php echo json_encode( esc_html__( 'Item has been saved.', 'gravityforms' ) ); ?>);
	}

	function InitializeFormConditionalLogic() {
		var canHaveConditionalLogic = GetFirstRuleField() > 0;
		if (canHaveConditionalLogic) {
			jQuery("#form_button_conditional_logic").prop("disabled", false).prop("checked", form.button.conditionalLogic ? true : false);
			ToggleConditionalLogic( true, "form_button" );
		}
		else {
			jQuery("#form_button_conditional_logic").prop("disabled", false).prop("checked", false);
			jQuery("#form_button_conditional_logic_container").show().html("<span class='instruction' style='margin-left:0'>" + <?php echo json_encode( esc_html__( 'To use conditional logic, please create a field that supports conditional logic.', 'gravityforms' ) ); ?> + "</span>");
		}
	}

	function InitPaginationOptions( isInit ) {

		var pages = GetFieldsByType(["page"]);
		pages.push(new Array());
		var str = "<ul class='gform_page_names'>";

		var pageNameFields = jQuery(".gform_page_names input");
		for (var i = 0; i < pages.length; i++) {
			var pageName = form["pagination"] && form["pagination"]["pages"] && form["pagination"]["pages"][i] ? form["pagination"]["pages"][i].replace("'", "&#39") : "";
			if (pageNameFields.length > i && pageNameFields[i].value)
				pageName = pageNameFields[i].value;

			str += "<li><label class='inline' for='gform_pagename_" + i + "' >" + <?php echo json_encode( esc_html__( 'Page', 'gravityforms' ) ); ?> + " " + (i + 1) + "</label> <input type='text' class='fieldwidth-4' id='gform_pagename_" + i + "' value='" + pageName + "' /></li>";
		}
		str += "</ul>";

		jQuery("#page_names_container").html(str);

		if (jQuery("#pagination_type_none").is(":checked")) {
			jQuery(".gform_page_names input").val("");
			jQuery("#percentage_confirmation_page_name").val("");
			jQuery("#percentage_confirmation_display").prop("checked", false);

			jQuery("#page_names_setting").hide();
			jQuery("#percentage_style_setting").hide();
			jQuery("#percentage_confirmation_display_setting").hide();
		}
		else if (jQuery("#pagination_type_percentage").is(":checked")) {
			var style = form["pagination"] && form["pagination"]["style"] ? form["pagination"]["style"] : "blue";
			jQuery("#percentage_style").val(style);

			if (style == "custom" && form["pagination"]["backgroundColor"]) {
				jQuery("#percentage_style_custom_bgcolor").val(form["pagination"]["backgroundColor"]);
				SetColorPickerColor("percentage_style_custom_bgcolor", form["pagination"]["backgroundColor"], "");
			}
			if (style == "custom" && form["pagination"]["color"]) {
				jQuery("#percentage_style_custom_color").val(form["pagination"]["color"]);
				SetColorPickerColor("percentage_style_custom_color", form["pagination"]["color"], "");
			}

			jQuery("#page_names_setting").show();
			jQuery("#percentage_style_setting").show();
			jQuery("#percentage_confirmation_display_setting").show();
			jQuery("#percentage_confirmation_page_name_setting").show();

			jQuery("#percentage_confirmation_display").prop("checked", form["pagination"] && form["pagination"]["display_progressbar_on_confirmation"] ? true : false);
			//set default text to Completed when displaying progress bar on confirmation is NOT checked
			var completion_text = form["pagination"] && form["pagination"]["display_progressbar_on_confirmation"] ? form["pagination"]["progressbar_completion_text"] : <?php echo json_encode( esc_html__( 'Completed','gravityforms' ) ); ?>;
			jQuery("#percentage_confirmation_page_name").val(completion_text);
		}
		else {
			jQuery("#percentage_style_setting").hide();
			jQuery("#page_names_setting").show();
			jQuery("#percentage_confirmation_display_setting").hide();
			jQuery("#percentage_confirmation_page_name_setting").hide();
			jQuery("percentage_confirmation_page_name").val("");
			jQuery("#percentage_confirmation_display").prop("checked", false);
		}

		TogglePercentageStyle( isInit );
		TogglePercentageConfirmationText( isInit );
	}

	function HideSettings() {
	    jQuery('.field_settings').hide();
	    jQuery('#sidebar_field_info').addClass('panel-block--hidden');
	    jQuery('#sidebar_field_info').removeClass('panel-block--flex');
        jQuery('#nothing_selected').show();
	}

	function TogglePostCategoryInitialItem(isInit) {

		if (jQuery("#gfield_post_category_initial_item_enabled").is(":checked")) {
			jQuery("#gfield_post_category_initial_item_container").show();

			if (!isInit) {
				jQuery("#field_post_category_initial_item").val(<?php echo json_encode( esc_html__( 'Select a category', 'gravityforms' ) ); ?>);
			}
		}
		else {
			jQuery("#gfield_post_category_initial_item_container").hide();
			jQuery("#field_post_category_initial_item").val('');
		}

	}

	function CreateInputNames(field) {
		var field_str = "", id, value, inputs;

		var inputType = GetInputType(field);
		var legacy = jQuery.inArray(inputType, ['date', 'email', 'time', 'password'])>-1;
		inputs = !legacy ? field['inputs'] : null;

		if (!inputs || GetInputType(field) == "checkbox") {
			field_str = "<label for='field_input_name' class='inline'>" + <?php echo json_encode( esc_html__( 'Parameter Name:', 'gravityforms' ) ); ?> + "&nbsp;</label>";
			field_str += "<input type='text' value='" + field["inputName"] + "' id='field_input_name' />";
		}
		else {
			var priceId = field['id'] + 0.2;
			field_str = "<table><tr><td><strong>Field</strong></td><td><strong>" + <?php echo json_encode( esc_html__( 'Parameter Name', 'gravityforms' ) ); ?> + "</strong></td></tr>";
			for (var i = 0; i < field["inputs"].length; i++) {
				id = field["inputs"][i]["id"];

				if (inputType == 'calculation' && id == priceId) {
					continue;
				}

				field_str += "<tr class='field_input_name_row' data-input_id='" + id + "' ><td><label for='field_input_" + id + "' class='inline'>" + field["inputs"][i]["label"] + "</label></td>";
				value = typeof field["inputs"][i]["name"] != 'undefined' ? field["inputs"][i]["name"] : '';
				field_str += "<td><input class='field_input_name' type='text' value='" + value + "' id='field_input_" + id + "' /></td></tr>";
			}
		}

		jQuery("#field_input_name_container").html(field_str);
	}

	function CreateDefaultValuesUI(field) {
		var field_str, defaultValue, inputName, inputId, id, inputs;

		if (!field['inputs']) {
			field_str = "<label for='field_single_default_value' class='inline'>" + <?php echo json_encode( esc_html__( 'Default Value:', 'gravityforms' ) ); ?> + "&nbsp;</label>";
			defaultValue = typeof field["defaultValue"] != 'undefined' ? field["defaultValue"] : '';
			field_str += "<input type='text' value='" + defaultValue + "' id='field_single_default_value'/>";
		} else {
			field_str = "<table class='default_input_values'><tr><td><strong>Field</strong></td><td><strong>" + <?php echo json_encode( esc_html__( 'Default Value', 'gravityforms' ) ); ?> + "</strong></td></tr>";
			for (var i = 0; i < field["inputs"].length; i++) {
				id = field["inputs"][i]["id"];
				inputName = 'input_' + id.toString();
				inputId = inputName.replace('.', '_');
				if (!document.getElementById(inputId) && jQuery('[name="' + inputName + '"]').length == 0) {
					continue;
				}
				field_str += "<tr class='default_input_value_row' data-input_id='" + id + "' id='input_default_value_row_" + inputId + "'><td><label for='field_default_value_" + id + "' class='inline'>" + field["inputs"][i]["label"] + "</label></td>";
				defaultValue = typeof field["inputs"][i]["defaultValue"] != 'undefined' ? field["inputs"][i]["defaultValue"] : '';
				field_str += "<td><input class='default_input_value' type='text' value='" + defaultValue + "' id='field_default_value_" + id + "' /></td></tr>";
			}
		}
		jQuery("#field_default_input_values_container").html(field_str);
	}

	function CreatePlaceholdersUI(field) {
		var field_str, placeholder, inputName, inputId, id;

		if (!field["inputs"]) {
			field_str = "<label for='field_single_placeholder' class='inline'>" + <?php echo json_encode( esc_html__( 'Placeholder:', 'gravityforms' ) ); ?> + "&nbsp;</label>";
			placeholder = typeof field["placeholder"] != 'undefined' ? field["placeholder"] : '';
			field_str += "<input type='text' value='" + placeholder + "' id='field_single_placeholder' />";
		} else {
			field_str = "<table class='input_placeholders'><tr><td><strong>Field</strong></td><td><strong>" + <?php echo json_encode( esc_html__( 'Placeholder', 'gravityforms' ) ); ?> + "</strong></td></tr>";
			for (var i = 0; i < field["inputs"].length; i++) {
				id = field["inputs"][i]["id"];
				inputName = 'input_' + id.toString();
				inputId = inputName.replace('.', '_');
				if (!document.getElementById(inputId) && jQuery('[name="' + inputName + '"]').length == 0) {
					continue;
				}
				field_str += "<tr class='input_placeholder_row' data-input_id='" + id + "' id='input_placeholder_row_" + inputId + "'><td><label for='field_placeholder_" + id + "' class='inline'>" + field["inputs"][i]["label"] + "</label></td>";
				placeholder = typeof field["inputs"][i]["placeholder"] != 'undefined' ? field["inputs"][i]["placeholder"] : '';
				placeholder = placeholder.replace(/'/g, "&#039;");
				field_str += "<td><input class='input_placeholder' type='text' value='" + placeholder + "' id='field_placeholder_" + id + "' /></td></tr>";
			}
		}

		jQuery("#field_input_placeholders_container").html(field_str);
	}

	function CreateAutocompleteUI( field ) {
		var field_str, autocomplete, inputName, inputId, id, input;

		if ( ! field["inputs"] ) {
			field_str = "<label for='field_autocomplete_attribute' class='inline'>" + <?php echo json_encode( esc_html__( 'Autocomplete Attribute:', 'gravityforms' ) ); ?> + "&nbsp;</label>";
			field_str += "<input type='text' value='" + field["autocompleteAttribute"] + "' id='field_autocomplete_attribute' class='field_autocomplete_attribute' />";
			SetFieldProperty( 'autocompleteAttribute', field["autocompleteAttribute"] );
		} else {
			field_str = "<table class='input_autocompletes'><tr><td><strong>" + <?php echo json_encode( esc_html__( 'Field', 'gravityforms' ) ); ?> + "</strong></td><td><strong>" + <?php echo json_encode( esc_html__( 'Autocomplete Attribute', 'gravityforms' ) ); ?> + "</strong></td></tr>";
			for ( var i = 0; i < field["inputs"].length; i++ ) {
				id = field["inputs"][i]["id"];
				inputName = 'input_' + id.toString();
				inputId = inputName.replace('.', '_');
				input = field["inputs"][i];
				field_str += "<tr class='input_autocomplete_row' data-input_id='" + id + "' id='input_autocomplete_row_" + inputId + "'><td><label for='field_autocomplete_attribute" + id + "' class='inline'>" + field["inputs"][i]["label"] + "</label></td>";
				autocomplete = ( typeof field["inputs"][i]["autocompleteAttribute"] != 'undefined' && field["inputs"][i]["autocompleteAttribute"] !== '' ) ? field["inputs"][i]["autocompleteAttribute"] : '';
				autocomplete = autocomplete.replace(/'/g, "&#039;");
				field_str += "<td><input class='input_autocomplete' type='text' value='" + autocomplete + "' id='field_autocomplete_" + id + "' /></td></tr>";
				SetInputAutocomplete( autocomplete, inputId );
			}
		}

		jQuery( "#autocomplete_attribute_container" ).html( field_str );
	}

	function GetCustomizeInputsUI(field, showInputSwitches) {
		if (typeof showInputSwitches == 'undefined') {
			showInputSwitches = true;
		}
		var html, customLabel, isHidden, title, input, inputId, id, inputName, defaultLabel, toggleChecked, placeholder;

		if (!field['inputs']) {
			html = "<label for='field_single_input' class='inline'>" + <?php echo json_encode( esc_html__( 'Sub-Label:', 'gravityforms' ) ); ?> + "&nbsp;</label>";
			customLabel = typeof field["customInputLabel"] != 'undefined' ? field["customInputLabel"] : '';
			html += "<input type='text' value='" + customLabel + "' class='field_single_custom_label' />";
		} else {
			html = "<table class='field_custom_inputs_ui'><tr>";
			if ( showInputSwitches ) {
				html += "<td>" + <?php echo wp_json_encode( esc_html__( 'Show', 'gravityforms' ) ); ?>+ "</td>";
			}
			html += "<td><?php esc_html_e( 'Field', 'gravityforms' );?></td><td>" + <?php echo wp_json_encode( esc_html__( 'Custom Sub-Label', 'gravityforms' ) ); ?> + "</td></tr>";
			for ( var i = 0; i < field[ "inputs" ].length; i ++ ) {
				input = field[ "inputs" ][ i ];
				id = input.id;
				inputName = 'input_' + id.toString();
				inputId = inputName.replace( '.', '_' );
				if ( jQuery( 'label[for="' + inputId + '"]' ).length == 0 ) {
					continue;
				}
				isHidden = typeof input.isHidden != 'undefined' && input.isHidden ? true : false;
				title = isHidden ? <?php echo wp_json_encode( esc_html__( 'Inactive', 'gravityforms' ) ); ?> : <?php echo wp_json_encode( esc_html__( 'Active', 'gravityforms' ) ); ?>;
				html += "<tr data-input_id='" + id + "' class='field_custom_input_row field_custom_input_row_" + inputId + "'>";
				if ( showInputSwitches ) {
					toggleChecked = isHidden ? '' : ' checked';
					html += "<td>" +
						"<div data-input_id='" + input.id + "' class='gform-field__toggle'>" +
						"<span class='gform-settings-input__container'>" +
						"<input class='gform-field__toggle-input' type='checkbox' name='gforms-editor-toggle-" + input.id + "' id='gforms-editor-toggle-" + input.id + "' " + toggleChecked + ">" +
						"<label class='gform-field__toggle-container' for='gforms-editor-toggle-" + input.id + "'>" +
						"<span class='gform-field__toggle-switch-text screen-reader-text'>" + title + "</span>" +
						"<span class='gform-field__toggle-switch'></span>" +
						"</label>" +
						"</span>" +
						"</div>" +
						"</td>";
				}
				if ( isHidden ) {
					jQuery( "#input_" + inputId + "_container" ).toggle( ! isHidden );
				}
				defaultLabel = typeof input.defaultLabel != 'undefined' ? input.defaultLabel : input.label;

				// Tweak the default label for legacy inputs.
				if ( field.type === 'date' ) {
					var isLegacyInput = field[ "inputs" ][ 0 ].label === <?php echo wp_json_encode( esc_html__( 'MM', 'gravityforms' ) ); ?>;

					if ( isLegacyInput ) {
						if ( defaultLabel === <?php echo wp_json_encode( esc_html__( 'MM', 'gravityforms' ) ); ?> ) {
							defaultLabel = <?php echo wp_json_encode( esc_html__( 'Month', 'gravityforms' ) ); ?>;
						} else if ( defaultLabel === <?php echo wp_json_encode( esc_html__( 'DD', 'gravityforms' ) ); ?> ) {
							defaultLabel = <?php echo wp_json_encode( esc_html__( 'Day', 'gravityforms' ) ); ?>;
						} else if ( defaultLabel === <?php echo wp_json_encode( esc_html__( 'YYYY', 'gravityforms' ) ); ?> ) {
							defaultLabel = <?php echo wp_json_encode( esc_html__( 'Year', 'gravityforms' ) ); ?>;
						}
					}
				} else if ( field.type === 'time' ) {
					var isLegacyInput = field[ "inputs" ][ 0 ].label === <?php echo json_encode( esc_html__( 'HH', 'gravityforms' ) ); ?>;

					if ( isLegacyInput ) {
						if ( defaultLabel === <?php echo json_encode( esc_html__( 'HH', 'gravityforms' ) ); ?> ) {
							defaultLabel = <?php echo json_encode( esc_html__( 'Hour', 'gravityforms' ) ); ?>;
						} else if ( defaultLabel === <?php echo json_encode( esc_html__( 'MM', 'gravityforms' ) ); ?> ) {
							defaultLabel = <?php echo json_encode( esc_html__( 'Minute', 'gravityforms' ) ); ?>;
						}
					}
				}

				defaultLabel = defaultLabel.replace(/'/g, "&#039;");
				html += "<td><label id='field_custom_input_default_label_" + inputId + "' for='field_custom_input_label_" + input.id + "' class='inline'>" + defaultLabel + "</label></td>";
				customLabel = typeof input.customLabel != 'undefined' ? input.customLabel : '';
				customLabel = customLabel.replace(/'/g, "&#039;");

				// For the Date and Time field, remove their placeholder to prevent confusion.
				if ( field.type === 'date' || field.type === 'time' ) {
					defaultLabel = '';
				}
				html += "<td><input class='field_custom_input_default_label' type='text' placeholder='" + defaultLabel + "' value='" + customLabel + "' id='field_custom_input_label_" + input.id + "' /></td></tr>";
			}
		}

		return html;
	}

	function CreateCustomizeInputsUI(field) {
		var field_str = GetCustomizeInputsUI(field);
		jQuery("#field_customize_inputs_container").html(field_str);
	}

	function CreateInputLabelsUI(field) {
		var field_str = GetCustomizeInputsUI(field, false);
		jQuery("#field_sub_labels_container").html(field_str);
	}

	function SetCopyValuesOptionProperties(isEnabled) {
		var defaultLabel = <?php echo json_encode( esc_html__( 'Same as previous', 'gravityforms' ) ); ?>;
		SetFieldProperty('enableCopyValuesOption', isEnabled == true ? 1 : 0);
		SetFieldProperty('copyValuesOptionDefault', 0);
		SetFieldProperty('copyValuesOptionLabel', defaultLabel);
		var sourceFieldId = jQuery('#field_copy_values_option_field').val();
		SetFieldProperty('copyValuesOptionField', sourceFieldId);
	}

	function ToggleCopyValuesOption( isInit ) {

		if (jQuery('#field_enable_copy_values_option').prop('checked')) {
			jQuery('#field_copy_values_container').show();
			var field = GetSelectedField();
			jQuery('#field_copy_values_option_label').val(field.copyValuesOptionLabel);
			jQuery('.field_selected .copy_values_option_label').html(field.copyValuesOptionLabel);
			jQuery('.field_selected .copy_values_option_container').show();
		} else {
			jQuery('#field_copy_values_container').hide();
			jQuery('#field_copy_values_option_default').prop('checked', false);
			jQuery('.field_selected .copy_values_option_container').hide();
		}
	}

	function ToggleInputHidden( checkbox, inputId ) {
		var checked = checkbox.is( ':checked' );
		var screenReaderText = checkbox.next().find( '.gform-field__toggle-switch-text' );
		if ( checked ) {
			screenReaderText.text(<?php echo wp_json_encode( esc_html__( 'Active', 'gravityforms' ) ); ?>);
		} else {
			screenReaderText.text(<?php echo wp_json_encode( esc_html__( 'Inactive', 'gravityforms' ) ); ?>);
		}
		SetInputHidden( ! checked, inputId );

		return true;
	}

	function CheckDeprecatedReadyClass( field ) {
		// don't show message on legacy forms.
		if ( form.markupVersion && form.markupVersion == 1 ) {
			return;
		}

		var deprecatedClasses = [
			'gf_left_half',
			'gf_right_half',
			'gf_left_third',
			'gf_middle_third',
			'gf_right_third',
			'gf_first_quarter',
			'gf_second_quarter',
			'gf_third_quarter',
			'gf_fourth_quarter'
		];

		var classes = field.cssClass.split(/\s+/);
		var deprecatedClass = deprecatedClasses.filter( function( className ) {
			return classes.indexOf( className ) !== - 1;
		} )[ 0 ];

		// Check if deprecated class found.
		if ( ! deprecatedClass ) {
			return;
		}

		var message = '<div id="gfield-warning-deprecated" class="gform-alert gform-alert--notice gform-alert--inline" role="alert">';
			message += '<span class="gform-alert__icon gform-icon gform-icon--circle-notice-fine" aria-hidden="true"></span>';
			message += '<div class="gform-alert__message-wrap">';
			message += '<p class="gform-alert__message">' + deprecatedClass + ' ' + <?php echo json_encode( esc_html__( 'is no longer necessary.', 'gravityforms' ) ); ?> + ' <a href="https://docs.gravityforms.com/working-with-columns/" target="_blank" title="' + <?php echo json_encode( esc_attr__( 'Working with Columns in the Form Editor in Gravity Forms 2.5', 'gravityforms' ) ); ?> + '">' + <?php echo json_encode( esc_html__( 'Learn more', 'gravityforms' ) ); ?> + '</a></p>';
			message += '</div>';
			message += '</div>';

		jQuery( '#field_css_class' ).after( message ).attr( 'aria-describedby', 'gfield-warning-deprecated' );
	}

	/**
	 * Reset the deprecated ready classes notice for the field setting.
	 *
	 * @since 2.5.8
	 */
	function resetDeprecatedReadyClassNotice() {
		if ( ! jQuery( '#gfield-warning-deprecated' ).length ) {
			return;
		}

		jQuery( '#gfield-warning-deprecated' ).remove();
	}


	function SetProductField(field) {
		var field_settings = getAllFieldSettings( field );

		//ignore product field if it is not configured for the current field
		if ( field_settings.indexOf('product_field_setting') === -1 )
			return;

		var productFields = new Array();
		for (var i = 0; i < form["fields"].length; i++) {
			if (form["fields"][i]["type"] == "product")
				productFields.push(form["fields"][i]);
		}

		jQuery("#gform_no_product_field_message").remove();
		if (productFields.length < 1) {
			jQuery("#product_field").hide().after('<div id="gform_no_product_field_message" class="gform-alert gform-alert--error gform-alert--inline"><span class="gform-alert__icon gform-icon gform-icon--circle-error-fine" aria-hidden="true"></span><div class="gform-alert__message-wrap"><p class="gform-alert__message">' + <?php echo json_encode( esc_html__( 'This field is not associated with a product. Please add a Product Field to the form.', 'gravityforms' ) ); ?> + '</p></div></div>');
		}
		else {
			var product_field = jQuery("#product_field");
			product_field.show();
			product_field.html("");
			var is_selected = false;
			for (var i = 0; i < productFields.length; i++) {
				selected = "";
				if (productFields[i]["id"] == field["productField"]) {
					selected = "selected='selected'";
					is_selected = true;
				}
				product_field.append("<option value='" + productFields[i]["id"] + "' " + selected + ">" + GetLabel(productFields[i]) + "</option>");
			}

			//Adds existing product field if it is not found in the list (to prevent confusion)
			if (!is_selected && field["productField"] != "") {
				product_field.append("<option value='" + field["productField"] + "' selected='selected'>[" + <?php echo json_encode( esc_html__( 'Deleted Field', 'gravityforms' ) ); ?> + "]</option>");
			}

		}
	}

	function LoadFieldConditionalLogic(isEnabled, objectType) {
		var obj = GetConditionalObject(objectType);

		if( 'button' === objectType ) {
			obj.id = 'submit';
		}

		new generateGFConditionalLogic( obj.id, objectType );
	}

	function GetCurrentCurrency() {
		<?php
		$current_currency = RGCurrency::get_currency( GFCommon::get_currency() );
		?>
		var currency = new Currency(<?php echo GFCommon::json_encode( $current_currency )?>);
		return currency;
	}

	function ToggleColumns( isInit ) {
		var field = GetSelectedField();

		if (jQuery('#field_columns_enabled').is(":checked")) {
			jQuery('#gfield_settings_columns_container').show();

			if (!field.choices)
				field.choices = new Array(new Choice(<?php echo json_encode( esc_html__( 'Column 1', 'gravityforms' ) ); ?>), new Choice(<?php echo json_encode( esc_html__( 'Column 2', 'gravityforms' ) ); ?>), new Choice(<?php echo json_encode( esc_html__( 'Column 3', 'gravityforms' ) ); ?>));

			LoadFieldChoices(field, true);
		}
		else {
			field.choices = null;
			jQuery('#gfield_settings_columns_container').hide();
		}

		UpdateFieldChoices(GetInputType(field));

	}

	function DuplicateTitleMessage() {
		jQuery("#please_wait_container").hide();
		alert(<?php echo json_encode( esc_html__( 'The form title you have entered is already taken. Please enter a unique form title', 'gravityforms' ) ); ?>);
	}

	function ValidateForm() {
		var error = "";
		if (jQuery.trim(form.title).length == 0) {
			error = <?php echo json_encode( esc_html__( 'Please enter a Title for this form. When adding the form to a page or post, you will have the option to hide the title.', 'gravityforms' ) ); ?>;
		}
		else {
			var last_page_break = -1;
			var has_option = false;
			var has_product = false;
			for (var i = 0; i < form["fields"].length; i++) {
				var field = form["fields"][i];
				switch (field["type"]) {
					case "page" :
						if (i == last_page_break + 1 || i == form["fields"].length - 1)
							error = <?php echo json_encode( esc_html__( 'Your form currently has one or more pages without any fields in it. Blank pages are a result of Page Breaks that are positioned as the first or last field in the form or right after each other. Please adjust your Page Breaks and try again.', 'gravityforms' ) ); ?>;

						last_page_break = i;
						break;

					case "product" :
						has_product = true;
						if (jQuery.trim(field["label"]).length == 0)
							error = <?php echo json_encode( esc_html__( "Your form currently has a product field with a blank label.\nPlease enter a label for all product fields.", 'gravityforms' ) ); ?>;
						break;

					case "option" :
						has_option = true;
						break;
				}
			}
			if (has_option && !has_product) {
				error = <?php echo json_encode( esc_html__( "Your form currently has an option field without a product field.\nYou must add a product field to your form.", 'gravityforms' ) ); ?>;
			}

			/**
			 * Allow the form editor validation error to be overridden.
			 *
			 * @since 2.2.5.11
			 *
			 * @param string error       The error message.
			 * @param object form        The current form.
			 * @param bool   has_product Indicates if the current form has a product field.
			 * @param bool   has_option  Indicates if the current form has a option field.
			 */
			error = gform.applyFilters('gform_validation_error_form_editor', error, form, has_product, has_option);
		}
		if (error) {
			jQuery("#please_wait_container").hide();
			alert(error);
			return false;
		}
		return true;
	}

	function SaveForm(isNew) {

		UpdateFormObject();

		if (!ValidateForm()) {
			return false;
		}

		// remove data that is no longer stored in the form object (as of 1.7)
		delete form.notification;
		delete form.autoResponder;
		delete form.notifications;
		delete form.confirmation;
		delete form.confirmations;

		//updating original json. used when verifying if there has been any changes unsaved changed before leaving the page
		var form_json = jQuery.toJSON(form);
		gforms_original_json = form_json;

		jQuery("#gform_meta").val(form_json);
		jQuery("#gform_update").submit();

		return true;
	}

	/**
	 * Prompt before deleting a form
	 *
	 * @since 2.5
	 *
	 */
	function DeleteForm() {
		if ( confirm( <?php echo wp_json_encode( __( "You are about to move this form to the trash. 'Cancel' to stop, 'OK' to move to trash.", "gravityforms" ) ); ?> ) ) {
			gf_vars.isFormTrash = true; jQuery('#form_trash')[0].submit();
		}
	}

	function SetDefaultValues( field, index ) {

		var inputType = GetInputType(field);
		switch (inputType) {
			case "post_category" :
				field.label = <?php echo json_encode( esc_html__( 'Post Category', 'gravityforms' ) ); ?>;
				field.inputs = null;
				field.choices = new Array();
				field.displayAllCategories = true;
				field.inputType = 'select';
				break;

			case "section" :
				field.label = <?php echo json_encode( esc_html__( 'Section Break', 'gravityforms' ) ); ?>;
				field.inputs = null;
				field["displayOnly"] = true;
				break;

			case "page" :
				field.label = "";
				field.inputs = null;
				field["displayOnly"] = true;
				field["nextButton"] = new Button();
				field["nextButton"]["text"] = <?php echo json_encode( esc_html__( 'Next', 'gravityforms' ) ); ?>;
				field["previousButton"] = new Button();
				field["previousButton"]["text"] = <?php echo json_encode( esc_html__( 'Previous', 'gravityforms' ) ); ?>;
				break;

			case "html" :
				field.label = <?php echo json_encode( esc_html__( 'HTML Block', 'gravityforms' ) ); ?>;
				field.inputs = null;
				field["displayOnly"] = true;
				break;

			case "list" :
				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'List', 'gravityforms' ) ); ?>;

				field.inputs = null;

				break;

			case "name" :
				if (!field.label){
					field.label = <?php echo json_encode( esc_html__( 'Name', 'gravityforms' ) ); ?>;
				}

				field.id = parseFloat(field.id);
				field.nameFormat = "advanced";
				field.inputs = GetAdvancedNameFieldInputs(field, true, true, true);

				break;

			case "checkbox" :
				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Untitled', 'gravityforms' ) ); ?>;

				if (!field.choices)
					field.choices = new Array(new Choice(<?php echo json_encode( esc_html__( 'First Choice', 'gravityforms' ) ); ?>), new Choice(<?php echo json_encode( esc_html__( 'Second Choice', 'gravityforms' ) ); ?>), new Choice(<?php echo json_encode( esc_html__( 'Third Choice', 'gravityforms' ) ); ?>));

				field.inputs = new Array();
				for (var i = 1; i <= field.choices.length; i++) {
					field.inputs.push(new Input(field.id + (i / 10), field.choices[i - 1].text));
				}

				break;
			case "radio" :
				if (!field.label)
					field.label = "<?php _e( 'Untitled', 'gravityforms' ); ?>";

				field.validateState = true;
				field.inputs = null;
				if (!field.choices) {
					field.choices = field["enablePrice"] ? new Array(new Choice(<?php echo json_encode( esc_html__( 'First Choice', 'gravityforms' ) ); ?>, "", "0.00"), new Choice(<?php echo json_encode( esc_html__( 'Second Choice', 'gravityforms' ) ); ?>, "", "0.00"), new Choice(<?php echo json_encode( esc_html__( 'Third Choice', 'gravityforms' ) ); ?>, "", "0.00"))
						: new Array(new Choice(<?php echo json_encode( esc_html__( 'First Choice', 'gravityforms' ) ); ?>), new Choice(<?php echo json_encode( esc_html__( 'Second Choice', 'gravityforms' ) ); ?>), new Choice(<?php echo json_encode( esc_html__( 'Third Choice', 'gravityforms' ) ); ?>));
				}
				break;

			case "multiselect" :
				field.storageType = 'json';
			case "select" :
				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Untitled', 'gravityforms' ) ); ?>;

				field.inputs = null;
				if (!field.choices) {
					if (field.type === 'quantity') {
						field.choices = [new Choice('1'), new Choice('2'), new Choice('3')];
					} else {
						field.choices = field["enablePrice"] ? new Array(new Choice(<?php echo json_encode( esc_html__( 'First Choice', 'gravityforms' ) ); ?>, "", "0.00"), new Choice(<?php echo json_encode( esc_html__( 'Second Choice', 'gravityforms' ) ); ?>, "", "0.00"), new Choice(<?php echo json_encode( esc_html__( 'Third Choice', 'gravityforms' ) ); ?>, "", "0.00"))
							: new Array(new Choice(<?php echo json_encode( esc_html__( 'First Choice', 'gravityforms' ) ); ?>), new Choice(<?php echo json_encode( esc_html__( 'Second Choice', 'gravityforms' ) ); ?>), new Choice(<?php echo json_encode( esc_html__( 'Third Choice', 'gravityforms' ) ); ?>));
					}
				}
				break;
			case "address" :

				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Address', 'gravityforms' ) ); ?>;
				field.addressType = <?php echo json_encode( GF_Fields::get( 'address' )->get_default_address_type( rgget( 'id' ) ) ) ?>;
				field.inputs = [
					new Input(
						field.id + 0.1,
						<?php echo json_encode( gf_apply_filters( array( 'gform_address_street', rgget( 'id' ) ), esc_html__( 'Street Address', 'gravityforms' ), rgget( 'id' ) ) ); ?>,
						"address-line1"
					),
					new Input(
						field.id + 0.2,
						<?php echo json_encode( gf_apply_filters( array( 'gform_address_street2', rgget( 'id' ) ), esc_html__( 'Address Line 2', 'gravityforms' ), rgget( 'id' ) ) ); ?>,
						"address-line2"
					),
					new Input(
						field.id + 0.3,
						<?php echo json_encode( gf_apply_filters( array( 'gform_address_city', rgget( 'id' ) ), esc_html__( 'City', 'gravityforms' ), rgget( 'id' ) ) ); ?>,
						"address-level2"
					),
					new Input(
						field.id + 0.4,
						<?php echo json_encode( gf_apply_filters( array( 'gform_address_state', rgget( 'id' ) ), __( 'State / Province', 'gravityforms' ), rgget( 'id' ) ) ); ?>,
						"address-level1"
					),
					new Input(
						field.id + 0.5,
						<?php echo json_encode( gf_apply_filters( array( 'gform_address_zip', rgget( 'id' ) ), esc_html__( 'ZIP / Postal Code', 'gravityforms' ), rgget( 'id' ) ) ); ?>,
						"postal-code"
					),
					new Input(
						field.id + 0.6,
						<?php echo json_encode( gf_apply_filters( array( 'gform_address_country', rgget( 'id' ) ), esc_html__( 'Country', 'gravityforms' ), rgget( 'id' ) ) ); ?>,
						"country-name"
					)
				];
				break;
			case "creditcard" :

				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Credit Card', 'gravityforms' ) ); ?>;
				var ccNumber, ccExpirationMonth, ccExpirationYear, ccSecruityCode, ccCardType, ccName;

				ccNumber = new Input(field.id + ".1", <?php echo json_encode( gf_apply_filters( array( 'gform_card_number', rgget( 'id' ) ), esc_html__( 'Card Number', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
				ccExpirationMonth = new Input(field.id + ".2_month", <?php echo json_encode( gf_apply_filters( array( 'gform_card_expiration', rgget( 'id' ) ), esc_html__( 'Expiration Month', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
				ccExpirationMonth.defaultLabel = <?php echo json_encode( esc_html__( 'Expiration Date', 'gravityforms' ) ); ?>;
				ccExpirationYear = new Input(field.id + ".2_year", <?php echo json_encode( gf_apply_filters( array( 'gform_card_expiration', rgget( 'id' ) ), esc_html__( 'Expiration Year', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
				ccSecruityCode = new Input(field.id + ".3", <?php echo json_encode( gf_apply_filters( array( 'gform_card_security_code', rgget( 'id' ) ), esc_html__( 'Security Code', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
				ccCardType = new Input(field.id + ".4", <?php echo json_encode( gf_apply_filters( array( 'gform_card_type', rgget( 'id' ) ), __( 'Card Type', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
				ccName = new Input(field.id + ".5", <?php echo json_encode( gf_apply_filters( array( 'gform_card_name', rgget( 'id' ) ), esc_html__( 'Cardholder Name', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
				field.inputs = [ccNumber, ccExpirationMonth, ccExpirationYear, ccSecruityCode, ccCardType, ccName];
				break;
			case "email" :
				field.inputs = GetEmailFieldInputs(field);
				field.autocompleteAttribute = 'email';

				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Email', 'gravityforms' ) ); ?>;

				break;
			case "number" :
				field.inputs = null;

				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Number', 'gravityforms' ) ); ?>;

				if (!field.numberFormat)
					field.numberFormat = "decimal_dot";

				break;
			case "phone" :
				field.inputs = null;
				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Phone', 'gravityforms' ) ); ?>;
				field.phoneFormat = "standard";
				field.autocompleteAttribute = "tel";
				break;
			case "date" :
				field.inputs = GetDateFieldInputs(field);
				field.dateType = 'datepicker';
				field.dateFormat = 'mdy';
				field.dateFormatPlacement = 'below';
				field.calendarIconType = 'none';
				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Date', 'gravityforms' ) ); ?>;
				break;
			case "time" :
				field.inputs = GetTimeFieldInputs(field);
				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Time', 'gravityforms' ) ); ?>;
				break;
			case "website" :
				field.inputs = null;
				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Website', 'gravityforms' ) ); ?>;
				if (!field.placeholder)
					field.placeholder = 'https://';
				break;
			case "password" :
				field.inputs = GetPasswordFieldInputs(field);
				field["displayOnly"] = true;
				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Password', 'gravityforms' ) ); ?>;
				break;
			case "fileupload" :
				field.inputs = null;
				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'File', 'gravityforms' ) ); ?>;
				break;
			case "hidden" :
				field.inputs = null;
				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Hidden Field', 'gravityforms' ) ); ?>;
				break;
			case "post_title" :
				field.inputs = null;
				field.label = <?php echo json_encode( esc_html__( 'Post Title', 'gravityforms' ) ); ?>;
				break;
			case "post_content" :
				field.inputs = null;
				field.label = <?php echo json_encode( esc_html__( 'Post Body', 'gravityforms' ) ); ?>;
				break;
			case "post_excerpt" :
				field.inputs = null;
				field.label = <?php echo json_encode( esc_html__( 'Post Excerpt', 'gravityforms' ) ); ?>;
				field.size = "small";
				break;
			case "post_tags" :
				field.inputs = null;
				field.label = <?php echo json_encode( esc_html__( 'Post Tags', 'gravityforms' ) ); ?>;
				field.size = "large";
				break;
			case "post_custom_field" :
				field.inputs = null;
				if (!field.inputType)
					field.inputType = "text";
				field.label = <?php echo json_encode( esc_html__( 'Post Custom Field', 'gravityforms' ) ); ?>;
				break;
			case "post_image" :
				field.label = <?php echo json_encode( esc_html__( 'Post Image', 'gravityforms' ) ); ?>;
				field.inputs = null;
				field["allowedExtensions"] = "jpg, jpeg, png, gif";
				break;
			case "captcha" :
				field.inputs = null;
				field["displayOnly"] = true;

				field.label = <?php echo json_encode( esc_html__( 'CAPTCHA', 'gravityforms' ) ); ?>;

				break;
			case "calculation" :
				field.enableCalculation = true;
			case "singleproduct" :
			case "product" :
			case "hiddenproduct" :
				field.label = <?php echo json_encode( esc_html__( 'Product Name', 'gravityforms' ) ); ?>;
				field.inputs = null;

				if (!field.inputType)
					field.inputType = "singleproduct";

				if (field.inputType == "singleproduct" || field.inputType == "hiddenproduct" || field.inputType == "calculation") {
					//convert field id to a number so it isn't treated as a string
					//caused concatenation below instead of addition
					field_id = parseFloat(field.id);
					field.inputs = [new Input(field_id + 0.1, <?php echo json_encode( esc_html__( 'Name', 'gravityforms' ) ); ?>), new Input(field_id + 0.2, <?php echo json_encode( esc_html__( 'Price', 'gravityforms' ) ); ?>), new Input(field_id + 0.3, <?php echo json_encode( esc_html__( 'Quantity', 'gravityforms' ) ); ?>)];
					field.enablePrice = null;
				}

				productDependentFields = GetFieldsByType(["option", "quantity"]);
				for (var i = 0; i < productDependentFields.length; i++) {
					if (!productDependentFields[i]["productField"])
						productDependentFields[i]["productField"] = field.id;
				}
				break;
			case "singleshipping" :
			case "shipping" :
				field.label = <?php echo json_encode( esc_html__( 'Shipping', 'gravityforms' ) ); ?>;
				field.inputs = null;

				if (!field.inputType)
					field.inputType = "singleshipping";

				if (field.inputType == "singleshipping")
					field.enablePrice = null;

				break;
			case "total" :
				field.label = <?php echo json_encode( esc_html__( 'Total', 'gravityforms' ) ); ?>;
				field.inputs = null;

				break;

			case "option" :
				field.label = <?php echo json_encode( esc_html__( 'Option', 'gravityforms' ) ); ?>;

				if (!field.inputType)
					field.inputType = "select";

				if (!field.choices) {
					field.choices = new Array(new Choice(<?php echo json_encode( esc_html__( 'First Option', 'gravityforms' ) ); ?>, "", "0.00"), new Choice(<?php echo json_encode( esc_html__( 'Second Option', 'gravityforms' ) ); ?>, "", "0.00"), new Choice(<?php echo json_encode( esc_html__( 'Third Option', 'gravityforms' ) ); ?>, "", "0.00"));
				}
				field["enablePrice"] = true;

				productFields = GetFieldsByType(["product"]);
				if (productFields.length > 0)
					field["productField"] = productFields[0]["id"];

				break;
			case "donation" :

				field.label = <?php echo json_encode( esc_html__( 'Donation', 'gravityforms' ) ); ?>;

				if (!field.inputType)
					field.inputType = "donation";


				field.inputs = null;
				field.enablePrice = null;

				break;

			case "price" :

				field.label = <?php echo json_encode( esc_html__( 'Price', 'gravityforms' ) ); ?>;

				if (!field.inputType)
					field.inputType = "price";

				field.inputs = null;
				field["enablePrice"] = null;

				break;

			case "quantity" :
				field.label = <?php echo json_encode( esc_html__( 'Quantity', 'gravityforms' ) ); ?>;

				if (!field.inputType)
					field.inputType = "number";

				productFields = GetFieldsByType(["product"]);
				if (productFields.length > 0)
					field["productField"] = productFields[0]["id"];

				if (!field.numberFormat)
					field.numberFormat = "decimal_dot";

				break;

			case 'consent':
				field.label = <?php echo json_encode( esc_html__( 'Consent', 'gravityforms' ) ); ?>;
				field.inputs = [new Input(field.id + ".1", <?php echo json_encode( esc_html__( 'Consent', 'gravityforms' ) ); ?>), new Input(field.id + ".2", <?php echo json_encode( esc_html__( 'Text', 'gravityforms' ) ); ?>), new Input(field.id + ".3", <?php echo json_encode( esc_html__( 'Description', 'gravityforms' ) ); ?>)];
				// Hide the description from select columns.
				field.inputs[1].isHidden = true;
				field.inputs[2].isHidden = true;
				field.checkboxLabel = <?php echo json_encode( esc_html__( 'I agree to the privacy policy.', 'gravityforms' ) ); ?>;
				field.descriptionPlaceholder = <?php echo json_encode( esc_html__( 'Enter consent agreement text here.  The Consent Field will store this agreement text with the form entry in order to track what the user has consented to.', 'gravityforms' ) ); ?>;
				if (!field.inputType)
					field.inputType = "consent";
				// Add choices so we have a dropdown in the conditional logic.
				if (!field.choices)
					field.choices = new Array(new Choice(<?php echo json_encode( esc_html__( 'Checked', 'gravityforms' ) ); ?>, '1'));
				break;

			<?php do_action( 'gform_editor_js_set_default_values' ); ?>

			default :
				field.inputs = null;
				if (!field.label)
					field.label = <?php echo json_encode( esc_html__( 'Untitled', 'gravityforms' ) ); ?>;
				break;
				break;
		}

		if ( field.size && form.markupVersion && form.markupVersion >= 2 ) {
			field.size = 'large';
		}

		if (window["SetDefaultValues_" + inputType])
			field = window["SetDefaultValues_" + inputType](field);
	}

	function GetAdvancedNameFieldInputs(field, prefixHidden, middleHidden, suffixHidden) {
		var prefixInput = new Input(
			field.id + '.2',
			<?php echo json_encode( gf_apply_filters( array( 'gform_name_prefix', rgget( 'id' ) ), esc_html__( 'Prefix', 'gravityforms' ), rgget( 'id' ) ) ); ?>,
			'honorific-prefix'
		);
		prefixInput.choices = GetDefaultPrefixChoices();
		prefixInput.isHidden = prefixHidden;

		var firstInput = new Input(
			field.id + '.3',
			<?php echo json_encode( gf_apply_filters( array( 'gform_name_first', rgget( 'id' ) ), esc_html__( 'First', 'gravityforms' ), rgget( 'id' ) ) ); ?>,
			'given-name'
		);

		/**
		 * Allows for modification for the middle name input for the Name Field in a form
		 *
		 * @param int The ID for the field
		 * @oaram string The Label for the input
		 */
		var middleInput = new Input(
			field.id + '.4',
			<?php echo json_encode( gf_apply_filters( array( 'gform_name_middle', rgget( 'id' ) ), esc_html__( 'Middle', 'gravityforms' ), rgget( 'id' ) ) ); ?>,
			'additional-name'
		);
		middleInput.isHidden = middleHidden;

		var lastInput = new Input(
			field.id + '.6',
			<?php echo json_encode( gf_apply_filters( array( 'gform_name_last', rgget( 'id' ) ), esc_html__( 'Last', 'gravityforms' ), rgget( 'id' ) ) ); ?>,
			'family-name'
		);
		var suffixInput = new Input(
			field.id + '.8',
			<?php echo json_encode( gf_apply_filters( array( 'gform_name_suffix', rgget( 'id' ) ), esc_html__( 'Suffix', 'gravityforms' ), rgget( 'id' ) ) ); ?>,
			'honorific-suffix'
		);
		suffixInput.isHidden = suffixHidden;
		prefixInput.inputType = 'radio';

		return [prefixInput, firstInput, middleInput, lastInput, suffixInput];
	}

	function GetDateFieldInputs(field) {
		if (typeof field.dateType == 'undefined' || field.dateType == 'datepicker' || field.dateType == '') {
			return null;
		}

		var inputs, day, month, year;

		switch (field.dateType) {
			case 'datefield' :
				month = new Input(field.id + '.1', <?php echo json_encode( esc_html__( 'Month', 'gravityforms' ) ); ?>);
				day = new Input(field.id + '.2', <?php echo json_encode( esc_html__( 'Day', 'gravityforms' ) ); ?>);
				year = new Input(field.id + '.3', <?php echo json_encode( esc_html__( 'Year', 'gravityforms' ) ); ?>);
				break;
			case 'datedropdown' :
				month = new Input(field.id + '.1', <?php echo json_encode( esc_html__( 'Month', 'gravityforms' ) ); ?>);
				month.placeholder = <?php echo json_encode( esc_html__( 'Month', 'gravityforms' ) ); ?>;
				day = new Input(field.id + '.2', <?php echo json_encode( esc_html__( 'Day', 'gravityforms' ) );?>);
				day.placeholder = <?php echo json_encode( esc_html__( 'Day', 'gravityforms' ) ); ?>;
				year = new Input(field.id + '.3', <?php echo json_encode( esc_html__( 'Year', 'gravityforms' ) ); ?>);
				year.placeholder = <?php echo json_encode( esc_html__( 'Year', 'gravityforms' ) ); ?>;
				break;
			default:
		}

		inputs = [month, day, year];

		return inputs;
	}

	function GetTimeFieldInputs(field) {
		var min, hour, ampm;

		hour = new Input(field.id + '.1', <?php echo json_encode( esc_html__( 'Hour', 'gravityforms' ) )?>);
		min = new Input(field.id + '.2', <?php echo json_encode( esc_html__( 'Minute', 'gravityforms' ) )?>);
		ampm = new Input(field.id + '.3', <?php echo json_encode( esc_html__( 'AM/PM', 'gravityforms' ) )?>);

		return [hour, min, ampm];
	}

	function GetEmailFieldInputs(field) {

		if (typeof field.emailConfirmEnabled == 'undefined' || field.emailConfirmEnabled == false) {
			return null;
		}

		var email, confirmation;

		email = new Input(
			field.id,
			<?php echo json_encode( esc_html__( 'Enter Email', 'gravityforms' ) ); ?>,
			'email'
		);
		confirmation = new Input(
			field.id + '.2',
			<?php echo json_encode( esc_html__( 'Confirm Email', 'gravityforms' ) ); ?>,
			'email'
		);

		return [email, confirmation];
	}

	function GetPasswordFieldInputs(field) {

		var password, confirmation;

		password = new Input(field.id, <?php echo json_encode( esc_html__( 'Enter Password', 'gravityforms' ) ); ?>);
		confirmation = new Input(field.id + '.2', <?php echo json_encode( esc_html__( 'Confirm Password', 'gravityforms' ) ); ?>);

		return [password, confirmation];
	}


	function UpgradeCreditCardField(field) {
		var legacyExpirationInput = GetInput(field, field.id + ".2");

		if (legacyExpirationInput) {
			var monthInput = new Input(field.id + ".2_month", <?php echo json_encode( gf_apply_filters( array( 'gform_card_expiration', rgget( 'id' ) ), esc_html__( 'Expiration Month', 'gravityforms' ), rgget( 'id' ) ) ); ?>);
			monthInput.defaultLabel = <?php echo json_encode( esc_html__( 'Expiration Date', 'gravityforms' ) ); ?>;
			var yearInput = new Input(field.id + ".2_year", <?php echo json_encode( esc_html__( 'Expiration Year', 'gravityforms' ) ); ?>);
			field.inputs.splice(1, 1, monthInput, yearInput);
			var nameInput = GetInput(field, field.id + ".5");
			nameInput.label = <?php echo json_encode( gf_apply_filters( array( 'gform_card_name', rgget( 'id' ) ), __( 'Cardholder Name', 'gravityforms' ), rgget( 'id' ) ) ); ?>;
		}

		return field;
	}

	function GetDefaultPrefixChoices() {
		return gf_vars.nameFieldDefaultPrefixes;
	}

	function CreateField( id, type, index ) {
		var field = new Field(id, type);
		SetDefaultValues( field, index );

		if (field.type == "captcha") {
			<?php
			$publickey = get_option( 'rg_gforms_captcha_public_key' );
			$privatekey = get_option( 'rg_gforms_captcha_private_key' );
			$site_key = get_option( 'rg_gforms_captcha_site_key' );
			$secret_key = get_option( 'rg_gforms_captcha_secret_key' );
			if ( class_exists( 'ReallySimpleCaptcha' ) && ( ( empty( $publickey ) || empty( $privatekey ) ) && ( empty( $site_key ) || empty( $secret_key ) ) ) ){
				?>
			field.captchaType = "simple_captcha";
			<?php
		}
		?>
		}
		return field;
	}

	function CanFieldBeAdded(type) {
		switch (type) {
			case "captcha" :
				if (GetFieldsByType(["captcha"]).length > 0) {
					alert(<?php echo json_encode( esc_html__( 'Only one reCAPTCHA field can be added to the form', 'gravityforms' ) ); ?>);
					return false;
				}
				break;

			case "shipping" :
				if (GetFieldsByType(["shipping"]).length > 0) {
					alert(<?php echo json_encode( esc_html__( 'Only one Shipping field can be added to the form', 'gravityforms' ) ); ?>);
					return false;
				}
				break;

			case "post_content" :
				if (GetFieldsByType(["post_content"]).length > 0) {
					alert(<?php echo json_encode( esc_html__( 'Only one Post Content field can be added to the form', 'gravityforms' ) ); ?>);
					return false;
				}
				break;
			case "post_title" :
				if (GetFieldsByType(["post_title"]).length > 0) {
					alert(<?php echo json_encode( esc_html__( 'Only one Post Title field can be added to the form', 'gravityforms' ) ); ?>);
					return false;
				}
				break;
			case "post_excerpt" :
				if (GetFieldsByType(["post_excerpt"]).length > 0) {
					alert(<?php echo json_encode( esc_html__( 'Only one Post Excerpt field can be added to the form', 'gravityforms' ) ); ?>);
					return false;
				}
				break;
			case "creditcard" :
				if (GetFieldsByType(["creditcard"]).length > 0) {
					alert(<?php echo json_encode( esc_html__( 'Only one credit card field can be added to the form', 'gravityforms' ) ); ?>);
					return false;
				}
				break;
			case "quantity" :
			case "option" :
				if (GetFieldsByType(["product"]).length <= 0) {
					alert(<?php echo json_encode( esc_html__( 'You must add a product field to the form first', 'gravityforms' ) ); ?>);
					return false;
				}
				break;
			default :
				return gform.applyFilters('gform_form_editor_can_field_be_added', true, type);
		}

		return true;
	}

	function StartAddField(type, index) {

		if (!CanFieldBeAdded(type)) {
			jQuery('#gform_adding_field_spinner').remove();
			return;
		}


		if (gf_vars["currentlyAddingField"] == true)
			return;

		gf_vars["currentlyAddingField"] = true;

		var nextId = GetNextFieldId();
		var field = CreateField( nextId, type, index );

		var mysack = new sack("<?php echo admin_url( 'admin-ajax.php' )?>");
		mysack.execute = 1;
		mysack.method = 'POST';
		mysack.setVar("action", "rg_add_field");
		mysack.setVar("rg_add_field", "<?php echo wp_create_nonce( 'rg_add_field' ) ?>");
		mysack.setVar("index", index);
		mysack.setVar("field", jQuery.toJSON(field));
		mysack.setVar('form_id', form.id);
		mysack.onError = function () {
			alert(<?php echo json_encode( esc_html__( 'Ajax error while adding field', 'gravityforms' ) ); ?>)
		};

		mysack.onCompletion = function() {
			/**
			 * Do something after we make an AJAX call to get a field preview.
			 *
			 * @since 2.5
			 *
			 * @param {object} form  The Form object
			 * @param {object} field The field for which the preview was refreshed.
			 * @param {string} index The index of the affected field.
			 */
			gform.doAction( 'gform_after_get_field_markup', form, field, index );
		};

		/**
		 * Do something before we make an AJAX call to get a field preview.
		 *
		 * @since 2.5
		 *
		 * @param {object} form  The Form object
		 * @param {object} field The field for which the preview was refreshed.
		 * @param {string} index The index of the affected field.
		 */
		gform.doAction( 'gform_before_get_field_markup', form, field, index );

		mysack.runAJAX();

		return true;
	}

	function DuplicateField(field, sourceFieldId) {

		jQuery.post(ajaxurl, {
				action: "rg_duplicate_field",
				rg_duplicate_field: "<?php echo wp_create_nonce( 'rg_duplicate_field' ) ?>",
				field: jQuery.toJSON(field),
				source_field_id: sourceFieldId,
				form_id: form.id
			},
			function (data) {
				data = jQuery.evalJSON(data);
				EndDuplicateField(data["field"], data["fieldString"], data["sourceFieldId"]);
			}
		);

		return true;
	}

	function RefreshSelectedFieldPreview(callback) {
		if (!field)
			field = GetSelectedField();
		var fieldId = field.id,
			data = {'action': 'rg_refresh_field_preview', 'rg_refresh_field_preview': '<?php echo wp_create_nonce( 'rg_refresh_field_preview' ) ?>', 'field': jQuery.toJSON(field), 'formId': form.id};

		/**
		 * Do something before a field's preview has been refreshed.
		 *
		 * @since 2.5
		 *
		 * @param {string} The field ID for which the preview was refreshed.
		 */
		gform.doAction( 'gform_before_refresh_field_preview', field.id );

		jQuery.post(ajaxurl, data,
			function (data) {
				field   = GetSelectedField();
				fieldId = field.id;
				if ( data.fieldId == fieldId ) {
					jQuery('.field_selected').children().not('.field_settings').remove();
					jQuery("#field_" + fieldId).prepend(data.fieldString);
				} else {
					jQuery("#field_" + data.fieldId).html(data.fieldString);
				}
				SetFieldLabel(field.label);
				SetFieldSize(field.size);
				SetFieldDefaultValue(field.defaultValue);
				SetFieldDescription(field.description);
				SetFieldCheckboxLabel(field.checkboxLabel);
				SetFieldRequired(field.isRequired);
				InitializeFields();
				/**
				 * Do something after a field's preview has been refreshed.
				 *
				 * @since 2.5
				 *
				 * @param object field The field for which the preview was refreshed.
				 */
				gform.doAction( 'gform_after_refresh_field_preview', data.fieldId );
				if (field["type"] == "address") {
					SetAddressType( false );
				}
				if (callback) {
					callback();
				}
			}, 'json'
		);

	}

	function StartChangeInputType(type, field) {
		if (type == "")
			return;
		// TODO make sure this is not breaking other things
		//jQuery("#field_settings").insertBefore("#gform_fields");
        jQuery('.field_settings').css('opacity', '0.5');
		if (!field)
			field = GetSelectedField();

		field["inputType"] = type;
		SetDefaultValues(field);
		var mysack = new sack("<?php echo admin_url( 'admin-ajax.php' )?>");
		mysack.execute = 1;
		mysack.method = 'POST';
		mysack.setVar("action", "rg_change_input_type");
		mysack.setVar("rg_change_input_type", "<?php echo wp_create_nonce( 'rg_change_input_type' ) ?>");
		mysack.setVar("field", jQuery.toJSON(field));
		mysack.setVar('form_id', form.id);
		mysack.onError = function () {
			alert(<?php echo json_encode( esc_html__( 'Ajax error while changing input type', 'gravityforms' ) ); ?>)
		};
		mysack.runAJAX();

		return true;
	}

	function GetFieldChoices(field) {
		if (field.choices == undefined)
			return "";

		var currency = GetCurrentCurrency();
		var str = "";
		for (var i = 0; i < field.choices.length; i++) {

			var checked = field.choices[i].isSelected ? "checked" : "";
			var inputType = GetInputType(field);
			var type = inputType === 'checkbox' ? 'checkbox' : 'radio';

			/**
			 * Allow the choice selected input type to be overridden.
			 *
			 * @since 2.2.5.11
			 *
			 * @param string type  The choice selected input type. Defaults to checkbox for checkbox type fields or radio for other field types.
			 * @param object field The current field.
			 */
			type = gform.applyFilters('gform_field_choice_selected_type_form_editor', type, field);

			var text = String(field.choices[i].text),
				value = field.enableChoiceValue ? String(field.choices[i].value) : text,
				price = field.choices[i].price ? currency.toMoney(field.choices[i].price) : "";
			if (!price){
				price = "";
			}

			var elementNames = {
				labelClass: 'field-choice-label field-choice-label--' + inputType + ' gform-choice__selected-label',
				choiceTypeInput: 'gfield_choice_' + type + ' field-choice-type field-choice-type--' + type + ' gform-choice__selected gform-choice__selected--' + type,
				textInput: inputType + '_choice_text_' + i,
				valueInput: inputType + '_choice_value_' + i,
				priceInput: inputType + '_choice_price_' + i,
			}

			str += "<li class='field-choice-row gform-choice' data-input_type='" + inputType + "' data-index='" + i + "'>";
			str += '<i class="field-choice-handle gform-choice__handle gform-icon gform-icon--drag" focusable="true"></i>';
			str += "<input type='" + type + "' class='" + elementNames.choiceTypeInput + "' name='choice_selected' id='" + inputType + "_choice_selected_" + i + "' " + checked
					+ " onclick=\"SetFieldChoice('" + inputType + "', " + i + ");\" onkeypress=\"SetFieldChoice('" + inputType + "', " + i + ");\" /> ";
			str += "<label class='" + elementNames.labelClass + "' for='" + inputType + "_choice_selected_" + i + "'><i class='gform-choice__selected-icon gform-icon gform-icon--check' /></i></label>";
			str += "<input type='text' id='" + elementNames.textInput + "' value=\"" + text.replace(/"/g, "&quot;") + "\" class='field-choice-input field-choice-text field-choice-text--" + inputType + " gform-choice__input gform-choice__input--label gform-input gform-input--text' />";
			str += "<input type='text' id='" + elementNames.valueInput + "' value=\"" + value.replace(/"/g, "&quot;") + "\" class='field-choice-input field-choice-value field-choice-value--" + inputType + " gform-choice__input gform-choice__input--value gform-input gform-input--text' />";
			str += "<input type='text' id='" + elementNames.priceInput + "' value=\"" + price.replace(/"/g, "&quot;") + "\" class='field-choice-input field-choice-price field-choice-price--" + inputType + " gform-choice__input gform-choice__input--price gform-input gform-input--text' />";

			if (window["gform_append_field_choice_option_" + field.type])
				str += window["gform_append_field_choice_option_" + field.type](field, i);

			str += gform.applyFilters('gform_append_field_choice_option', '', field, i);

			str += "<button class='field-choice-button field-choice-button--insert gf_insert_field_choice gform-choice__button gform-choice__button--add gform-st-icon gform-st-icon--circle-plus' onclick=\"InsertFieldChoice(" + (i + 1) + ");\" aria-label='<?php esc_attr_e( 'Add choice', 'gravityforms' ); ?>'></button>";

			if (field.choices.length > 1) {
				str += "<button class='field-choice-button field-choice-button--delete gf_delete_field_choice gform-choice__button gform-choice__button--add gform-st-icon gform-st-icon--circle-minus' onclick=\"DeleteFieldChoice(" + i + ");\" aria-label='<?php esc_attr_e( 'Delete choice', 'gravityforms' ); ?>'></button>";
			}

			str += "</li>";

		}
		return str;
	}

	function GetInputChoices(input) {
		if (input.choices == undefined)
			return "";

		var str = "";
		var inputId = input.id.toString();
		for (var i = 0; i < input.choices.length; i++) {

			var checked = input.choices[i].isSelected ? "checked" : "";
			var inputType = GetInputType(input);
			var type = inputType == 'checkbox' ? 'checkbox' : 'radio';

			var text = String(input.choices[i].text),
				value = input.enableChoiceValue ? String(input.choices[i].value) : text;

			str += "<li class='field-choice-row' data-index='" + i + "' data-input_id='" + inputId + "'>";
			str += '<i class="field-choice-handle" focusable="true"></i>';
			str += "<input type='" + type + "' class='field-input-choice-" + inputId.replace('.', '_') + " gfield_choice_" + type + "' name='choice_selected' id='" + inputType + "_choice_selected_" + i + "' " + checked + " /> " + "<label for='" + inputType + "_choice_selected_" + i + "'  ></label>";
			str += "<input type='text' id='" + inputType + "_choice_text_" + i + "' value=\"" + text.replace(/"/g, "&quot;") + "\" class='field-choice-input field-choice-text' />";
			str += "<input type='text' id='" + inputType + "_choice_value_" + i + "' value=\"" + value.replace(/"/g, "&quot;") + "\" class='field-choice-input field-choice-value' />";

			str += "<button class='field-input-insert-choice field-choice-button field-choice-button--insert gf_insert_field_choice gform-choice__button gform-choice__button--add gform-st-icon gform-st-icon--circle-plus' onclick=\"InsertFieldChoice(" + (i + 1) + ");\" aria-label='<?php esc_attr_e( 'Add choice', 'gravityforms' ); ?>'></button>";

			if (input.choices.length > 1) {
				str += "<button class='field-input-delete-choice field-choice-button field-choice-button--delete gf_delete_field_choice gform-choice__button gform-choice__button--add gform-st-icon gform-st-icon--circle-minus' onclick=\"DeleteFieldChoice(" + i + ");\" aria-label='<?php esc_attr_e( 'Delete choice', 'gravityforms' ); ?>'></button>";
			}

			str += "</li>";

		}
		return str;
	}

	function GetCaptchaUrl(pos) {
		if (pos == undefined)
			pos = "";

		var field = GetSelectedField();
		var size = field.simpleCaptchaSize == undefined ? "medium" : field.simpleCaptchaSize;
		var fg = field.simpleCaptchaFontColor == undefined ? "" : field.simpleCaptchaFontColor;
		var bg = field.simpleCaptchaBackgroundColor == undefined ? "" : field.simpleCaptchaBackgroundColor;

		var url = "<?php echo admin_url( 'admin-ajax.php?action=rg_captcha_image' )?>" + "&type=" + field.captchaType + "&pos=" + pos + "&size=" + size + "&fg=" + fg.replace("#", "%23") + "&bg=" + bg.replace("#", "%23");
		return url;
	}

	function SetFieldPhoneFormat(phoneFormat) {
		var instruction = phoneFormat == "standard" ? <?php echo json_encode( esc_html__( 'Phone format:', 'gravityforms' ) ); ?> + " (###) ###-####" : "";
		var display = phoneFormat == "standard" ? "block" : "none";

		jQuery(".field_selected .instruction").css('display', display).html(instruction);

		SetFieldProperty('phoneFormat', phoneFormat);
	}

	function LoadMessageVariables() {
		var options = "<option>" + <?php echo json_encode( esc_html__( 'Select a field', 'gravityforms' ) ); ?> + "</option><option value='{form_title}'>" + <?php echo json_encode( esc_html__( 'Form Title', 'gravityforms' ) ); ?> + "</option><option value='{date_mdy}'>" + <?php echo json_encode( esc_html__( 'Date', 'gravityforms' ) ); ?> + " (mm/dd/yyyy)</option><option value='{date_dmy}'>" + <?php echo json_encode( esc_html__( 'Date', 'gravityforms' ) ); ?> + " (dd/mm/yyyy)</option><option value='{ip}'>" + <?php echo json_encode( esc_html__( 'User IP Address', 'gravityforms' ) ); ?> + "</option><option value='{all_fields}'>" + <?php echo json_encode( esc_html__( 'All Submitted Fields', 'gravityforms' ) ); ?> + "</option>";

		for (var i = 0; i < form.fields.length; i++)
			options += "<option value='{" + form.fields[i].label + ":" + form.fields[i].id + "}'>" + form.fields[i].label + "</option>";

		jQuery("#form_autoresponder_variable").html(options);
	}

	/**
	 * Set the accessibility warning for a field settings.
	 *
	 * @since 2.5
	 *
	 * @param {string} fieldSetting The field setting class name.
	 * @param {string} position     The position to put the warning, can be 'above' or 'below'.
	 * @param {string} [message]    The message to be set in the warning.
	 */
	function SetFieldAccessibilityWarning( fieldSetting, position, message ) {
		var predefinedMessages = {
			post_category_field_type_setting: <?php echo json_encode( esc_html__( 'The Multi Select field type is hard to use for people who cannot use a mouse. Please select a different field type to improve the accessibility of your form.', 'gravityforms' ) ); ?>,
			date_format_placement_setting: <?php echo json_encode( esc_html__( 'Users can enter a date in the field without using the date picker. Display the date format so they know what is the specified format.', 'gravityforms' ) ); ?>,
			date_input_type_setting: <?php echo json_encode( esc_html__( 'The datepicker is not accessible for users who rely on the keyboard or screen reader. Please select a different input type to improve the accessibility of your form.', 'gravityforms' ) ); ?>,
			enable_enhanced_ui_setting: <?php echo json_encode( esc_html__( 'The Enhanced User Interface is not accessible for screen reader users and people who cannot use a mouse.', 'gravityforms' ) ); ?>,
			label_placement_setting: <?php echo json_encode( esc_html__( 'Hiding the label can make it difficult for users to fill out your form. Please keep the label visible to improve the accessibility of your form.', 'gravityforms' ) ); ?>,
			submit_type_setting: <?php echo json_encode( esc_html__( 'The image button is not accessible for users who rely on a screen reader. Please use a text button to improve the accessibility of your form.', 'gravityforms' ) ); ?>,
			label_setting:
			<?php
			/* translators: 1. Open abbr tag 2. Close abbr tag */
			echo json_encode( sprintf( esc_html__( 'To better comply with %1$sWCAG%2$s, we use the placeholder or description as a hidden label for screen readers.', 'gravityforms' ), '<abbr title="Web Content Accessibility Guidelines">', '</abbr>' ) );
			?>
		};

		// Set post_tag and multiselect messages to match the post_category message.
		predefinedMessages.post_tag_type_setting = predefinedMessages.post_category_field_type_setting;
		predefinedMessages.multiselect = predefinedMessages.post_category_field_type_setting;

		if ( message === undefined ) {
			if ( predefinedMessages.hasOwnProperty( fieldSetting ) ) {
				message = '<p class="gform-alert__message">' + predefinedMessages[ fieldSetting ] + '</p>';
				message += '<a class="gform-alert__cta gform-button gform-button--white gform-button--size-xs" href="https://docs.gravityforms.com/field-accessibility-warning" target="_blank">';
				message += <?php echo json_encode( esc_html__( 'Learn more', 'gravityforms' ) ); ?>;
				message += '</a>';
			} else {
				message = '<p class="gform-alert__message"><a href="https://docs.gravityforms.com/field-accessibility-warning" target="_blank">';
				message += <?php echo json_encode( esc_html__( 'This field has accessibility issues.', 'gravityforms' ) ); ?>;
				message += '</a></p>';
			}
		}

		// For messages that display on top of the field, convert the field type to "label_setting" as the fieldSetting.
		var fieldTypes = [ 'captcha', 'multiselect' ];
		if ( fieldTypes.includes( fieldSetting ) ) {
			fieldSetting = 'label_setting';
		}

		var warningDiv = '<div class="gform-alert gform-alert--accessibility gform-alert--inline">';
			warningDiv += '<span class="gform-alert__icon gform-icon gform-icon--accessibility" aria-hidden="true"></span>';
			warningDiv += '<div class="gform-alert__message-wrap">' + message + '</div>';
			warningDiv += '</div>';

		var fieldSetting = jQuery( '.' + fieldSetting );
		if ( position === 'above' ) {
			fieldSetting.prevAll( '.accessibility_warning' ).remove();
			fieldSetting.before( warningDiv );
		} else {
			fieldSetting.nextAll( '.accessibility_warning' ).remove();
			fieldSetting.after( warningDiv );
		}
	}

	/**
	 * Get the field error with url for a field settings.
	 *
	 * @since 2.5
	 *
	 * @param {string} fieldSetting The field setting class name.
	 */
	function getFieldErrorMessage( fieldSetting ) {
		var predefinedMessages = {
			label_setting:
			<?php
			/* translators: 1. Open abbr tag 2. Close abbr tag */
			echo json_encode( sprintf( esc_html__( 'An empty label violates %1$sWCAG%2$s. Please use descriptive text for your label. To hide the label, use the "Field Label Visibility" setting.', 'gravityforms' ), '<abbr title="Web Content Accessibility Guidelines">', '</abbr>' ) );
			?>
		};
		var message = '';

		if ( predefinedMessages.hasOwnProperty( fieldSetting ) ) {
			message += '<p class="gform-alert__message">' + predefinedMessages[ fieldSetting ] + '</p>';
			message += '<a class="gform-alert__cta gform-button gform-button--white gform-button--size-xs" href="https://docs.gravityforms.com/field-accessibility-warning" target="_blank">';
			message += <?php echo json_encode( esc_html__( 'Learn more', 'gravityforms' ) ); ?>;
			message += '</a>';
		} else {
			message += '<a href="https://docs.gravityforms.com/field-accessibility-warning" target="_blank">';
			message += <?php echo json_encode( esc_html__( 'This field has errors.', 'gravityforms' ) ); ?>;
			message += '</a>';
		}

		return message;
	}

	/**
	 * Set a Notification for a field setting.
	 *
	 * @since 2.6
	 *
	 * @param {string} fieldSetting The field setting class name.
	 * @param {string} position     The position to put the notification, can be 'above' or 'below'.
	 * @param {string} [message]    The message to be set in the notification.
	 */
	function SetFieldNotification( fieldSetting, position, message ) {
		var predefinedMessages = {
			submit_location_setting: <?php echo json_encode( esc_html__( 'The submit button can\'t be placed inline on multi-page forms.', 'gravityforms' ) ); ?>,
			submit_image_setting: <?php echo json_encode( esc_html__( 'If a valid image url is not present a text-only submit button will be used.', 'gravityforms' ) ); ?>
		};

		var notificationMessage = message !== undefined ? message : '';
		notificationMessage = ! notificationMessage && predefinedMessages.hasOwnProperty( fieldSetting ) ? predefinedMessages[ fieldSetting ] : '';

		if ( ! notificationMessage ) {
			return;
		}

		var notificationDiv = '<div class="gform-alert gform-alert--notice gform-alert--inline">';
		notificationDiv += '<span class="gform-alert__icon gform-icon gform-icon--circle-notice-fine" aria-hidden="true"></span>';
		notificationDiv += '<div class="gform-alert__message-wrap"><p class="gform-alert__message">' + notificationMessage + '</p></div>';
		notificationDiv += '</div>';

		var fieldSetting = jQuery( '.' + fieldSetting );
		if ( position === 'above' ) {
			fieldSetting.prevAll( '.gform-alert--notice' ).remove();
			fieldSetting.before( notificationDiv );
		} else {
			fieldSetting.nextAll( '.gform-alert--notice' ).remove();
			fieldSetting.after( notificationDiv );
		}
	}

	</script>

<?php wp_print_scripts( array( 'gform_form_editor' ) ); ?>

<span id="gform_editor_js_action_output_wrapper">
	<?php do_action( 'gform_editor_js' ); ?>
</span>
