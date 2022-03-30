/* eslint-env jquery */

//-------------------------------------------------
// INITIALIZING PAGE
//-------------------------------------------------

jQuery( document ).ready(
	function () {
		InitializeEditor();
	}
);

function InitializeEditor() {
	jQuery( '.search-button > input' ).on( 'keyup change click paste', function( e ) {
		FieldSearch( this );
		addClearButton( this );
	} );

	jQuery( '.search-button > input' ).on( 'keyup paste', function( e ) {
		jQuery( '.sidebar' ).tabs( {
			active: 0,
		} );
	} );

	jQuery( '.clear-button' ).on( 'click', function( e ) {
		clearInput( this );
	} );

	jQuery( '.gf-topmenu-dynamic' ).on( 'click', function( e ) {
		var position = jQuery( this ).position();
		jQuery( '.gf-popover' ).css( 'left', ( position.left + ( jQuery( this ).width() / 2 ) + 6 ) + 'px' );
		var currentDisplay = jQuery( '.gf-popover' ).css( 'display' );
		jQuery( '.gf-popover' ).css( 'display', ( currentDisplay === 'block' ? 'none' : 'block' ) );
	} );

	jQuery( '.gf-popover__button' ).on( 'click', function() {
		var url = jQuery( this ).data( 'url' );
		if ( url !== '' ) {
			window.location.href = url;
		}
	} );

	jQuery( document ).on( 'click', function( e ) {
		var container = jQuery( ".gf-topmenu-dynamic" );
		if ( ! container.is( e.target ) && container.has( e.target ).length === 0 ) {
			jQuery( '.gf-popover' ).hide();
		}
	} );

	jQuery( '.add-buttons button' ).each( function() {
		var $this = jQuery( this );
		var type = $this.attr( 'data-type' );
		var onClick = $this.attr( 'onclick' );
		if ( typeof type == 'undefined' && onClick ) {
			// deprecate buttons without the type data attribute
			if ( onClick.indexOf( 'StartAddField' ) > -1 ) {
				if ( /StartAddField\([ ]?'(.*?)[ ]?'/.test( onClick ) ) {
					type = onClick.match( /'(.*?)'/ )[ 1 ];
					$this.data( 'type', type );
				}
			}
		}
		if ( typeof type != 'undefined' && ( typeof onClick == 'undefined' || onClick == '') ) {
			jQuery( this ).click( function() {
				StartAddField( type );
			} );
		}
	} );

	jQuery( '#field_choices, #field_columns' ).sortable( {
		axis: 'y',
		handle: '.field-choice-handle',
		update: function( event, ui ) {
			var fromIndex = ui.item.data( "index" );
			var toIndex = ui.item.index();
			MoveFieldChoice( fromIndex, toIndex );
		},
	} );

	jQuery( '.field_input_choices' ).sortable( {
		axis: 'y',
		handle: '.field-choice-handle',
		update: function( event, ui ) {
			var fromIndex = ui.item.data( "index" );
			var toIndex = ui.item.index();
			var inputId = ui.item.data( "input_id" );
			var $ul = ui.item.parent();
			MoveInputChoice( $ul, inputId, fromIndex, toIndex );
		},
	} );

	if ( typeof gf_global[ 'view' ] == 'undefined' || gf_global[ 'view' ] != 'settings' )
		InitializeForm( form );

	//for backwards compatibility <1.7
	jQuery( document ).trigger( 'gform_load_form_settings', [ form ] );

	SetupUnsavedChangesWarning();

	//log deprecated events
	if ( window.console ) {
		var doc = jQuery( document )[ 0 ];
		var data = jQuery.hasData( doc ) && jQuery._data( doc );
		if ( data ){
			var deprecatedEvents = new Array( 'gform_load_form_settings' );
			for ( var e in data.events ) {
				if ( jQuery.inArray( e, deprecatedEvents ) !== -1 ) {
					console.log( 'Gravity Forms API warning: The jQuery event "' + e + '" is deprecated on this page since version 1.7' );
				}
			}
		}
	}

	// store original value of input before change
	jQuery( document ).on( 'focus', '#field_choices input.field-choice-text, #field_choices input.field-choice-value', function() {
		jQuery( this ).data( 'previousValue', jQuery( this ).val() );
	} );

	InitializeFieldSettings();

	jQuery( '.sidebar' ).tabs({
		activate: function( event, ui ) {
			ui.newPanel.css("display","flex");
		}
	});
	jQuery( '#field_settings' ).tabs();
	jQuery( '.field_settings' ).accordion( gform.options.jqEditorAccordions );
	jQuery( '#add_fields_menu .panel-block-tabs__wrapper' ).accordion( gform.options.jqAddFieldAccordions );
	jQuery( '.panel-block-tabs' ).find( '.panel-block-tabs__toggle' ).each( function( i, element ) {
		jQuery( element ).append( '<i></i>' );
	} );
	ResetFieldAccordions();

	// Loop keypresses in the field settings area through them, or focus back on the active fields
	// settings trigger if esc is used.

	jQuery( '.panel-block > .field_settings' ).on( 'keydown', function( e ) {
		// esc key, refocus the settings trigger in the editor preview for the active field
		if ( e.keyCode === 27 ) {
			jQuery( '.gfield.field_selected .gfield-edit').focus();
			return;
		}
		// not tab key, exit
		if ( e.keyCode !== 9 ) {
			return;
		}
		// get visible focusable items
		var focusable = gform.tools.getFocusable( this );
		// store first and last visible item
		var firstFocusableEl = focusable[0];
		var lastFocusableEl = focusable[ focusable.length - 1 ];

		// shiftkey was involved, we're going backwards, focus last el if we are leaving first
		if ( e.shiftKey ) /* shift + tab */ {
			if (document.activeElement === firstFocusableEl) {
				lastFocusableEl.focus();
				e.preventDefault();
			}
		// regular tabbing direction, bring us back to first el at reaching end
		} else /* tab */ {
			if (document.activeElement === lastFocusableEl) {
				firstFocusableEl.focus();
				e.preventDefault();
			}
		}
	} );

	// Prior to gravityformsppcp 2.3, the PayPal buttons show up in the editor instead of in the sidebar.
	jQuery( '#field_submit #gform_ppcp_smart_payment_buttons' ).remove();
}

function InitializeFieldSettings(){

	gform.addFilter( 'gform_editor_field_settings', 'hideDefaultMarginOnTopLabelAlignment' );

    jQuery('#field_max_file_size').on('input propertychange', function(){
        var $this = jQuery(this),
            inputValue = parseInt($this.val());
        var value = inputValue ? inputValue : '';

        SetFieldProperty('maxFileSize', value);

    }).on('change', function(){
		var field = GetSelectedField();
		var value = field.maxFileSize ? field.maxFileSize : '';
		var maskedValue = value === '' ? '' : value + "MB";
		this.value = maskedValue;
	});
    jQuery(document).on('input propertychange', '.field_default_value', function(){
        SetFieldDefaultValue(this.value);
    });
    jQuery(document).on('input propertychange', '.field_placeholder, .field_placeholder_textarea', function(){
        SetFieldPlaceholder(this.value);

	    var field = GetSelectedField();
	    if ( field.label === '' ) {
		    setFieldError( 'label_setting', 'below' );

		    if ( this.value !== '' ) {
			    resetFieldError( 'label_setting' );
		    }
	    }
    });

	jQuery('#field_choices').on('change' , '.field-choice-price', function() {
		var field = GetSelectedField();
		var i = jQuery(this).parent('li').index();
		var price = field.choices[i].price;
		this.value = price;
	});

	jQuery('.field_input_choices')
		.on('input propertychange', 'input', function () {
			var $li = jQuery(this).closest('li'),
				index = $li.data('index'),
				inputId = $li.data('input_id'),
				value = $li.find('.field-choice-value').val(),
				text = $li.find('.field-choice-text').val();
			SetInputChoice(inputId, index, value, text);
		})
        .on('click keypress', 'input:radio, input:checkbox', function () {
            var $li = jQuery(this).closest('li'),
                index = $li.data('index'),
                inputId = $li.data('input_id'),
                value = $li.find('.field-choice-value').val(),
                text = $li.find('.field-choice-text').val();
            SetInputChoice(inputId, index, value, text);
        })
		.on('click keypress', '.field-input-insert-choice', function () {
			var $li = jQuery(this).closest('li'),
				$ul = $li.closest('ul'),
				index = $li.data('index'),
				inputId = $li.data('input_id');
			InsertInputChoice($ul, inputId, index + 1);
		})
		.on('click keypress', '.field-input-delete-choice', function () {
			var $li = jQuery(this).closest('li'),
				$ul = $li.closest('ul'),
				index = $li.data('index'),
				inputId = $li.data('input_id');
			DeleteInputChoice($ul, inputId, index);
		});

	jQuery('.field_input_choice_values_enabled').on('click keypress', function(){
        var $container = jQuery(this).parent().siblings('.gfield_settings_input_choices_container');
        ToggleInputChoiceValue($container, this.checked);
        var $ul = $container.find('ul');
        SetInputChoices($ul);
    });

	jQuery('.input_placeholders_setting')
		.on('input propertychange', '.input_placeholder', function(){
			var inputId = jQuery(this).closest('.input_placeholder_row').data('input_id');
			SetInputPlaceholder(this.value, inputId);
		})
		.on('input propertychange', '#field_single_placeholder', function(){
			SetFieldPlaceholder(this.value);
		});

	//add onclick event to disable placeholder when the rich text editor is on
	jQuery('#field_rich_text_editor').on('click keypress', function(){
			var field = GetSelectedField();
			if (this.checked ){
				var disablePlaceHolder = true;
				//see if a field is using this in conditional logic and warn it will not work with rich text editor
				if ( HasConditionalLogicDependency(field.id,field.value) ){
					if ( ! confirm(gf_vars.conditionalLogicRichTextEditorWarning) ){
						//user cancelled setting rte, uncheck
						jQuery('#field_rich_text_editor').prop('checked', false);
						disablePlaceHolder = false;
					}
				}

				if (disablePlaceHolder){
					jQuery('#field_placeholder, #field_placeholder_textarea').prop('disabled', true);
					jQuery('span#placeholder_warning').css('display','block');
				}
			}
			else{
				jQuery('#field_placeholder, #field_placeholder_textarea').prop('disabled', false);
				jQuery('span#placeholder_warning').css('display','none');
			}
		});

	jQuery('.prepopulate_field_setting')
		.on('input propertychange', '.field_input_name', function(){
			var inputId = jQuery(this).closest('.field_input_name_row').data('input_id');
			SetInputName(this.value, inputId);
		})
		.on('input propertychange', '#field_input_name', function(){
			SetInputName(this.value);
		});

	jQuery( '.custom_inputs_setting, .custom_inputs_sub_setting, .sub_labels_setting' )
		.on( 'change', '.gform-field__toggle-input', function() {
			var inputId = jQuery( this ).closest( '.gform-field__toggle' ).data( 'input_id' );
			ToggleInputHidden( jQuery( this ), inputId );
		} )
		.on( 'click', '#field_password_fields_container .gform-field__toggle', function() {
			// special handling for the password field checkboxes
			var inputId = jQuery( this ).data( 'input_id' );
			var toggleInput = jQuery( this ).find( '.gform-field__toggle-input' );
			toggleInput[ 0 ].focus();
			toggleInput[ 0 ].checked = ! toggleInput[ 0 ].checked;
			ToggleInputHidden( toggleInput, inputId );
		} )
		.on( 'input propertychange', '.field_custom_input_default_label', function() {
			var inputId = jQuery( this ).closest( '.field_custom_input_row' ).data( 'input_id' );
			SetInputCustomLabel( this.value, inputId );
		} )
		.on( 'input propertychange', '.field_single_custom_label', function() {
			SetInputCustomLabel( this.value );
		} );

	jQuery('.default_input_values_setting')
		.on('input propertychange', '.default_input_value', function(){
			var inputId = jQuery(this).closest('.default_input_value_row').data('input_id');
			SetInputDefaultValue(this.value, inputId);
		})
		.on('input', '#field_single_default_value', function(){
			SetFieldDefaultValue(this.value);
		});

	jQuery('.choices_setting, .columns_setting')
		.on('input propertychange', '.field-choice-input', function(e){
			var $this = jQuery(this);
			var li = $this.closest('li.field-choice-row');
			var inputType = li.data('input_type');
			var i = li.data('index');
			SetFieldChoice( inputType, i);
			if($this.hasClass('field-choice-text') || $this.hasClass('field-choice-value')){
				CheckChoiceConditionalLogicDependency(this);
				e.stopPropagation();
			}

		});

    jQuery('#field_enable_copy_values_option').on('click keypress', function(){
        SetCopyValuesOptionProperties(this.checked);
        ToggleCopyValuesOption( false );

        if(this.checked == false){
            ToggleCopyValuesActivated(false);
        }
    });

    jQuery('#field_copy_values_option_label').on('input propertychange', function(){
        SetCopyValuesOptionLabel(this.value);
    });

    jQuery('#field_copy_values_option_field').on('change', function(){
        SetFieldProperty('copyValuesOptionField', jQuery(this).val());
    });

    jQuery('#field_copy_values_option_default').on('change', function(){
        SetFieldProperty('copyValuesOptionDefault', this.checked == true ? 1 : 0);
        ToggleCopyValuesActivated(this.checked);
    });

	jQuery('#field_label')
		.on('input propertychange', function(){
			SetFieldLabel( this.value );
			SetAriaLabel( this.value );

			if ( this.value !== '' ) {
				resetFieldError( 'label_setting' );
				ResetFieldAccessibilityWarning( 'label_setting' );
			}
		})
		.on( 'blur', function () {
			if ( this.value === '' ) {
				setFieldError( 'label_setting', 'below' );
			}
		} );

	jQuery('#submit_text').on('input propertychange', function(){
		jQuery('#gform_submit_button_' + form.id ).val( this.value );
	});

	jQuery('#submit_image').on('input propertychange', function(){
		ToggleSubmitType( false );
	});

	jQuery('#field_description').on('blur', function(){
		var field = GetSelectedField();
		if ( field.description != this.value ) {
			SetFieldDescription(this.value);
			RefreshSelectedFieldPreview();
		}

		if ( field.label === '' ) {
			setFieldError( 'label_setting', 'below' );

			if ( this.value !== '' ) {
				resetFieldError( 'label_setting' );
			}
		}
	});

	jQuery( 'input[ name="field_visibility" ]' ).on( 'DOMSubTreeModified change', function() {
		var field = GetSelectedField();
		SetFieldProperty( 'visibility', this.value );
		var hidden_markup = '<div class="admin-hidden-markup"><i class="gform-icon gform-icon--hidden"></i><span>Hidden</span></div>';
		if ( field[ 'visibility' ] === 'hidden' ) {
			jQuery( '#field_' + field.id ).addClass( 'admin-hidden' );
			jQuery( '#field_' + field.id + ' .gfield_label' ).before( hidden_markup );
			jQuery( '#field_' + field.id + ' .gsection_title' ).before( hidden_markup );
		} else {
			jQuery( '#field_' + field.id ).removeClass( 'admin-hidden' );
			jQuery( '#field_' + field.id + ' .admin-hidden-markup' ).remove();
		}
	});

	jQuery('#field_checkbox_label').on('input propertychange', function(){
		var field = GetSelectedField();
		if ( field.checkboxLabel != this.value ) {
			SetFieldCheckboxLabel(this.value);
			RefreshSelectedFieldPreview();
		}
	});

	jQuery('#field_content').on('input propertychange', function(){
		SetFieldProperty('content', this.value);
	});

	jQuery('#next_button_text_input, #next_button_image_url').on('input propertychange', function(){
		SetPageButton('next');
	});

	jQuery('#previous_button_image_url, #previous_button_text_input').on('input propertychange', function(){
		SetPageButton('previous');
	});

	jQuery('#field_custom_field_name_text').on('input propertychange', function(){
		SetFieldProperty('postCustomFieldName', this.value);
	});

	jQuery('#field_customfield_content_template').on('input propertychange', function(){
		SetCustomFieldTemplate();
	});

	jQuery('#gfield_calendar_icon_url').on('input propertychange', function(){
		SetFieldProperty('calendarIconUrl', this.value);
	});

	jQuery('#field_max_files').on('input propertychange', function(){
		SetFieldProperty('maxFiles', this.value);
	});

	jQuery('#field_maxrows').on('input propertychange', function(){
		SetFieldProperty('maxRows', this.value);
	});

	jQuery('#field_mask_text').on('input propertychange', function(){
		SetFieldProperty('inputMaskValue', this.value);
	});

	jQuery('#field_file_extension').on('input propertychange', function(){
		SetFieldProperty('allowedExtensions', this.value);
	});

	jQuery('#field_maxlen')
		.on('keypress', function(event){
			return ValidateKeyPress(event, GetMaxLengthPattern(), false)
		})
		.on('change keyup', function(){
			SetMaxLength(this);
		});

	jQuery('#field_range_min').on('input propertychange', function(){
		SetFieldProperty('rangeMin', this.value);
	});

	jQuery('#field_range_max').on('input propertychange', function(){
		SetFieldProperty('rangeMax', this.value);
	});

	jQuery('#field_calculation_formula').on('input propertychange', function(){
		SetFieldProperty('calculationFormula', this.value.trim());
	});

	jQuery('#field_error_message').on('input propertychange', function(){
		SetFieldProperty('errorMessage', this.value);
	});

	jQuery( '#field_css_class' ).on( 'focus', function () {
		jQuery( this ).data( 'previousClass', this.value );
	}).on( 'change', function() {
		SetFieldProperty( 'cssClass', this.value );
		previousClass = jQuery( this ).data( 'previousClass' );
		jQuery( '#field_' + field.id ).removeClass( previousClass ).addClass( this.value );
		CheckDeprecatedReadyClass( field );
	});

	jQuery('#field_admin_label').on('input propertychange', function(){
		SetFieldProperty('adminLabel', this.value);
	});

	jQuery( '.autocomplete_setting' )
		.on( 'input propertychange', '.input_autocomplete', function() {
			var inputId = jQuery( this ).closest( '.input_autocomplete_row' ).data( 'input_id') ;
			SetInputAutocomplete( this.value, inputId );
		} )
		.on( 'input propertychange', '#field_autocomplete_attribute', function() {
			SetFieldProperty( 'autocompleteAttribute', this.value );
		});

	jQuery('#field_add_icon_url').on('input propertychange', function(){
		SetFieldProperty('addIconUrl', this.value);
	});

	jQuery('#field_delete_icon_url').on('input propertychange', function(){
		SetFieldProperty('deleteIconUrl', this.value);
	});
}

/**
 * Filters out the Hide Default Margins option when labels are top-aligned.
 *
 * @since 2.5
 *
 * @param {array} settings The settings for this field.
 * @param {array} field    The current field.
 *
 * @return {array}
 */
function hideDefaultMarginOnTopLabelAlignment( settings, field ) {
	if ( form[ 'labelPlacement' ] !== 'top_label' ) {
		return settings;
	}

	// Labels are top-aligned; remove the disable margins setting so it doesn't display.
	for ( var key in settings ) {
		if ( settings[ key ] === '.disable_margins_setting' ) {
			settings.splice( key, 1 );
			break;
		}
	}

	return settings;
}

function InitializeForm(form){

	// Submit button settings.
	jQuery( "#submit_text" ).val( form.button.text );
	jQuery( "#submit_image" ).val( form.button.imageUrl );
	if( form.button.width ) {
		jQuery( "#submit_width_" + form.button.width ).prop( 'checked', true );
	} else {
		jQuery( "#submit_width_auto" ).prop( 'checked', true );
	}
	if( form.button.location ) {
		jQuery( "#submit_location_" + form.button.location ).prop( 'checked', true );
	} else {
		jQuery( "#submit_location_bottom" ).prop( 'checked', true );
	}
	if( form.button.type ) {
		jQuery( "#submit_type_" + form.button.type ).prop( 'checked', true );
	} else {
		jQuery( "#submit_type_" ).prop( 'checked', true );
	}

    if(form.lastPageButton && form.lastPageButton.type === 'image')
        jQuery('#last_page_button_image').prop('checked', true);
    else if(!form.lastPageButton || form.lastPageButton.type !== 'image')
        jQuery('#last_page_button_text').prop('checked', true);

    jQuery('#last_page_button_text_input').val(form.lastPageButton ? form.lastPageButton.text : gf_vars['previousLabel']);
    jQuery('#last_page_button_image_url').val(form.lastPageButton ? form.lastPageButton.imageUrl : '');
    TogglePageButton('last_page', true);

    if(form.postStatus)
        jQuery('#field_post_status').val(form.postStatus);

    if(form.postAuthor)
        jQuery('#field_post_author').val(form.postAuthor);

    //default to checked
    if(form.useCurrentUserAsAuthor === undefined)
        form.useCurrentUserAsAuthor = true;

    jQuery('#gfield_current_user_as_author').prop('checked', form.useCurrentUserAsAuthor ? true : false);

    if(form.postCategory)
        jQuery('#field_post_category').val(form.postCategory);

    if(form.postFormat)
        jQuery('#field_post_format').val(form.postFormat);

    if(form.postContentTemplateEnabled){
        jQuery('#gfield_post_content_enabled').prop('checked', true);
        jQuery('#field_post_content_template').val(form.postContentTemplate);
    }
    else{
        jQuery('#gfield_post_content_enabled').prop('checked', false);
        jQuery('#field_post_content_template').val('');
    }
    TogglePostContentTemplate(true);

    if(form.postTitleTemplateEnabled){
        jQuery('#gfield_post_title_enabled').prop('checked', true);
        jQuery('#field_post_title_template').val(form.postTitleTemplate);
    }
    else{
        jQuery('#gfield_post_title_enabled').prop('checked', false);
        jQuery('#field_post_title_template').val('');
    }
    TogglePostTitleTemplate(true);

    jQuery('#gform_pagination, #gform_last_page_settings').on('click', function ( event ) {
    	FieldClick(this);
    	event.stopPropagation();
    });

    jQuery('#gform_fields').on('click', '.gfield', function ( event ) {
    	FieldClick(this);
    	event.stopPropagation();
    });

    var paginationType = form['pagination'] && form['pagination']['type'] ? form['pagination']['type'] : 'percentage';
    var paginationSteps = paginationType === 'steps' ? true : false;
    var paginationPercentage = paginationType === 'percentage' ? true : false;
    var paginationNone = paginationType === 'none' ? true : false;

    if(paginationSteps)
        jQuery('#pagination_type_steps').prop('checked', true);
    else if(paginationPercentage)
        jQuery('#pagination_type_percentage').prop('checked', true);
    else if(paginationNone)
        jQuery('#pagination_type_none').prop('checked', true);

    jQuery('#first_page_css_class').val(form['firstPageCssClass']);

    TogglePageBreakSettings();
    InitPaginationOptions( true );

    InitializeFields();
}

function LoadFieldSettings(){
    // Loads settings
    field = GetSelectedField();
    var inputType = GetInputType(field);

	// Reset accessibility warnings
	resetAllFieldAccessibilityWarnings();
	// Reset errors
	resetAllFieldErrors();
	// Reset notices
	resetAllFieldNotices();
	// Reset deprecated ready class notice
	resetDeprecatedReadyClassNotice();

    jQuery("#field_label").val(field.label);
    if(field.type == "html"){
        jQuery(".tooltip_form_field_label").hide();
        jQuery(".tooltip_form_field_label_html").show();
        //jQuery(".tooltip_form_field_label").hide();
    }
    else{
		jQuery(".tooltip_form_field_label").show();
		jQuery(".tooltip_form_field_label_html").hide();
    }

    jQuery("#field_admin_label").val(field.adminLabel);
    jQuery("#field_content").val(field["content"] == undefined ? "" : field["content"]);
    jQuery("#post_custom_field_type").val(field.inputType);
    jQuery("#post_tag_type").val(field.inputType);
    jQuery("#field_size").val(field.size);
    jQuery("#field_required").prop("checked", field.isRequired == true ? true : false);
    jQuery("#field_margins").prop("checked", field.disableMargins == true ? true : false);
    jQuery("#field_no_duplicates").prop("checked", field.noDuplicates == true ? true : false);
    jQuery("#field_default_value").val(field.defaultValue == undefined ? "" : field.defaultValue);
    jQuery("#field_default_value_textarea").val(field.defaultValue == undefined ? "" : field.defaultValue);
    jQuery("#field_autocomplete_attribute").val(field.autocompleteAttribute);
    jQuery("#field_description").val(field.description == undefined ? "" : field.description);
	jQuery("#field_description").attr('placeholder', field.descriptionPlaceholder == undefined ? "" : field.descriptionPlaceholder);
	jQuery("#field_checkbox_label").val(field.checkboxLabel == undefined ? "" : field.checkboxLabel);
    jQuery("#field_css_class").val(field.cssClass == undefined ? "" : field.cssClass);
    jQuery("#field_range_min").val( field.rangeMin == undefined || field.rangeMin === false ? "" : field.rangeMin);
    jQuery("#field_range_max").val(field.rangeMax == undefined  || field.rangeMax === false ? "" : field.rangeMax);
    jQuery("#field_name_format").val(field.nameFormat);
    jQuery('#field_force_ssl').prop('checked', field.forceSSL ? true : false);

	if( '' !== field.cssClass ) {
		CheckDeprecatedReadyClass( field );
	}

	if (field.useRichTextEditor){
		//disable the placeholder when the rich text editor is checked, show message indicating why disabled
		jQuery('#field_placeholder, #field_placeholder_textarea').prop('disabled', true);
		jQuery('span#placeholder_warning').css('display','block');
		//jQuery('span#placeholder_warning').text('Placeholder text is not supported when using the Rich Text Editor.');
	}
	else{
		jQuery('#field_placeholder, #field_placeholder_textarea').prop('disabled', false);
		jQuery('span#placeholder_warning').css('display','none');
		//jQuery('span#placeholder_warning').text('');
	}

    if(typeof field.labelPlacement == 'undefined'){
        field.labelPlacement = '';
    }
    if(typeof field.descriptionPlacement == 'undefined'){
        field.descriptionPlacement = '';
    }
    if(typeof field.subLabelPlacement == 'undefined'){
        field.subLabelPlacement = '';
    }
    jQuery("#field_label_placement").val(field.labelPlacement);
    jQuery("#field_description_placement").val(field.descriptionPlacement);
    jQuery("#field_sub_label_placement").val(field.subLabelPlacement);
    if((field.labelPlacement == 'left_label' || field.labelPlacement == 'right_label' || (field.labelPlacement == '' && form.labelPlacement != 'top_label'))){
        jQuery('#field_description_placement_container').hide();
    } else {
        jQuery('#field_description_placement_container').show();
    }

    // field.adminOnly is the old property which stored the visibility setting; only reference if field.visibility is not set
    SetFieldVisibility( field.visibility, true );

    if(typeof field.placeholder == 'undefined'){
        field.placeholder = '';
    }
    jQuery("#field_placeholder, #field_placeholder_textarea").val(field.placeholder);

    jQuery("#field_file_extension").val(field.allowedExtensions == undefined ? "" : field.allowedExtensions);
    jQuery("#field_multiple_files").prop("checked", field.multipleFiles ? true : false);
    jQuery("#field_max_files").val(field.maxFiles ? field.maxFiles : "" );
    jQuery("#field_max_file_size").val(field.maxFileSize ? field.maxFileSize + "MB" : "" );
    ToggleMultiFile(true);


    jQuery("#field_phone_format").val(field.phoneFormat);
    jQuery("#field_error_message").val(field.errorMessage);
    jQuery('#field_select_all_choices').prop('checked', field.enableSelectAll ? true : false);
    jQuery('#field_other_choice').prop('checked', field.enableOtherChoice ? true : false);
    jQuery('#field_add_icon_url').val(field.addIconUrl ? field.addIconUrl : "");
    jQuery('#field_delete_icon_url').val(field.deleteIconUrl ? field.deleteIconUrl : "");
    jQuery('#gfield_enable_enhanced_ui').prop('checked', field.enableEnhancedUI ? true : false);

    jQuery("#gfield_password_strength_enabled").prop("checked", field.passwordStrengthEnabled == true ? true : false);
    jQuery("#gfield_password_visibility_enabled").prop("checked", field.passwordVisibilityEnabled == true ? true : false);
    TogglePasswordVisibility( true );
    jQuery("#gfield_min_strength").val(field.minPasswordStrength == undefined ? "" : field.minPasswordStrength);
    TogglePasswordStrength( true );

    jQuery("#gfield_email_confirm_enabled").prop("checked", field.emailConfirmEnabled == true ? true : false);

    //Creating blank item for number format to existing number fields so that user is not force into a format (for backwards compatibility)
    if(!field.numberFormat){
        if(jQuery("#field_number_format #field_number_format_blank").length == 0){
            jQuery("#field_number_format").prepend("<option id='field_number_format_blank' value=''>" + gf_vars["selectFormat"] + "</option>");
        }
    }
    else
        jQuery("#field_number_format_blank").remove();

    jQuery("#field_number_format").val(field.numberFormat ? field.numberFormat : "");

    // Handle calculation options

    // hide rounding option for calculation product fields
    if (field.type == 'product' && field.inputType == 'calculation') {
        field.enableCalculation = true;
        jQuery('.field_calculation_rounding').hide();
        jQuery('.field_enable_calculation').hide();
    } else {
        jQuery('.field_enable_calculation').show();
        if (field.type == 'number' && field.numberFormat == "currency") {
            jQuery('.field_calculation_rounding').hide();
        } else {
            jQuery('.field_calculation_rounding').show();
        }
    }

    jQuery('#field_enable_calculation').prop('checked', field.enableCalculation ? true : false);
    ToggleCalculationOptions(field.enableCalculation, field);

    jQuery('#field_calculation_formula').val(field.calculationFormula);
    var rounding = gformIsNumber(field.calculationRounding) ? field.calculationRounding : "norounding";
    jQuery('#field_calculation_rounding').val(rounding);

    jQuery("#option_field_type").val(field.inputType);
    var productFieldType = jQuery("#product_field_type");
    productFieldType.val(field.inputType);
    if(has_entry(field.id)){
        productFieldType.prop("disabled", true);
    } else{
        productFieldType.prop("disabled", false);
    }

    jQuery("#donation_field_type").val(field.inputType);
    jQuery("#quantity_field_type").val(field.inputType);

    if(field["inputType"] == "hiddenproduct" || field["inputType"] == "singleproduct" || field["inputType"] == "singleshipping" || field["inputType"] == "calculation"){
        var basePrice = field.basePrice == undefined ? "" : field.basePrice;
        jQuery("#field_base_price").val(field.basePrice == undefined ? "" : field.basePrice);
        SetBasePrice(basePrice);
    }

	jQuery("#shipping_field_type").val(field.inputType);

    jQuery("#field_disable_quantity").prop("checked", field.disableQuantity == true ? true : false);
    SetDisableQuantity(field.disableQuantity == true);

    var isPassword = field.enablePasswordInput ? true : false
    jQuery("#field_password").prop("checked", isPassword ? true : false);

    jQuery("#field_maxlen").val(typeof field.maxLength == "undefined" ? "" : field.maxLength);
    jQuery("#field_maxrows").val(typeof field.maxRows == "undefined" ? "" : field.maxRows);

    var addressType = field.addressType == undefined ? "international" : field.addressType;
    jQuery('#field_address_type').val(addressType);

    if(field.type == 'address'){
        field = UpgradeAddressField(field);
    }

    if(field.type == 'email' || field.inputType == 'email' ){
        field = UpgradeEmailField(field);
    }

    if(field.type === 'consent'){
        field = UpgradeConsentField(field);
    }

    var defaultState = field.defaultState == undefined ? "" : field.defaultState;
    var defaultProvince = field.defaultProvince == undefined ? "" : field.defaultProvince; //for backwards compatibility
    var defaultStateProvince = addressType == "canadian" && defaultState == "" ? defaultProvince : defaultState;

    jQuery("#field_address_default_state_" + addressType).val(defaultStateProvince);
    jQuery("#field_address_default_country_" + addressType).val(field.defaultCountry == undefined ? "" : field.defaultCountry);

    SetAddressType( true );

    jQuery("#gfield_display_alt").prop("checked", field.displayAlt == true ? true : false);
    jQuery("#gfield_display_title").prop("checked", field.displayTitle == true ? true : false);
    jQuery("#gfield_display_caption").prop("checked", field.displayCaption == true ? true : false);
    jQuery("#gfield_display_description").prop("checked", field.displayDescription == true ? true : false);

    var customFieldExists = CustomFieldExists(field.postCustomFieldName);
    jQuery("#field_custom_field_name_select")[0].selectedIndex = 0;

    jQuery("#field_custom_field_name_text").val("");
    if(customFieldExists)
        jQuery("#field_custom_field_name_select").val(field.postCustomFieldName);
    else
        jQuery("#field_custom_field_name_text").val(field.postCustomFieldName);

    if(customFieldExists)
        jQuery("#field_custom_existing").prop("checked", true);
    else
        jQuery("#field_custom_new").prop("checked", true);

    ToggleCustomField( true );

    jQuery('#gfield_customfield_content_enabled').prop("checked", field.customFieldTemplateEnabled ? true : false);
    jQuery('#field_customfield_content_template').val(field.customFieldTemplateEnabled ? field.customFieldTemplate : "");
    ToggleCustomFieldTemplate(true);

    if(field.displayAllCategories)
        jQuery("#gfield_category_all").prop("checked", true);
    else
        jQuery("#gfield_category_select").prop("checked", true);

    ToggleCategory( true );

    jQuery('#gfield_post_category_initial_item_enabled').prop("checked", field.categoryInitialItemEnabled ? true : false);
    jQuery('#field_post_category_initial_item').val(field.categoryInitialItemEnabled ? field.categoryInitialItem : "");
    TogglePostCategoryInitialItem(true);

    var hasPostFeaturedImage = field.postFeaturedImage ? true : false;
    jQuery('#gfield_featured_image').prop('checked', hasPostFeaturedImage);

	if (typeof field.inputMaskIsCustom != 'boolean') {
		field.inputMaskIsCustom = !IsStandardMask(field.inputMaskValue);
	}

	var isStandardMask = !field.inputMaskIsCustom;

    jQuery("#field_input_mask").prop('checked', field.inputMask ? true : false);

    if(isStandardMask){
        jQuery("#field_mask_standard").prop("checked", true);
        jQuery("#field_mask_select").val(field.inputMaskValue);
    }
    else{
        jQuery("#field_mask_custom").prop("checked", true);
        jQuery("#field_mask_text").val(field.inputMaskValue);
    }

    ToggleInputMask(true);
    ToggleInputMaskOptions(true);

    InitAutocompleteOptions(true);

    if(inputType == "creditcard"){
        field = UpgradeCreditCardField(field);
        if(!field.creditCards || field.creditCards.length <= 0)
            field.creditCards = ['amex', 'visa', 'discover', 'mastercard'];

        for(i in field.creditCards) {
            if(!field.creditCards.hasOwnProperty(i))
                continue;

            jQuery('#field_credit_card_' + field.creditCards[i]).prop('checked', true);
        }
    }

    if(inputType == 'date'){
        field = UpgradeDateField(field);
    }

    if(inputType == 'time'){
        field = UpgradeTimeField(field);
    }

    CreateDefaultValuesUI(field);
    CreatePlaceholdersUI(field);
    CreateAutocompleteUI(field);
    CreateCustomizeInputsUI(field);
    CreateInputLabelsUI(field);

    if(!field["dateType"] && inputType == "date"){
        field["dateType"] = "datepicker";
    }

    jQuery("#field_date_input_type").val(field["dateType"]);
    jQuery("#gfield_calendar_icon_url").val(field["calendarIconUrl"] == undefined ? "" : field["calendarIconUrl"]);
    jQuery('#field_date_format').val(field['dateFormat'] == undefined ? "mdy" : field['dateFormat']);
    jQuery('#field_time_format').val(field['timeFormat'] == "24" ? "24" : "12");

    SetCalendarIconType(field["calendarIconType"], true);

    ToggleDateCalendar( true );
    LoadDateInputs();
    LoadTimeInputs();

    field.allowsPrepopulate = field.allowsPrepopulate ? true : false; //needed when property is undefined
	field.useRichTextEditor = field.useRichTextEditor ? true : false;

    jQuery("#field_prepopulate").prop("checked", field.allowsPrepopulate ? true : false);

	jQuery("#field_rich_text_editor").prop("checked", field.useRichTextEditor ? true : false);

	if(has_entry(field.id)){
		jQuery('#field_rich_text_editor').prop("disabled", true);
	} else{
		jQuery('#field_rich_text_editor').prop("disabled", false);
	}

    CreateInputNames(field);
    ToggleInputName( true );

    var canHaveConditionalLogic = GetFirstRuleField() > 0;
    if(field["type"] == "page"){
        LoadFieldConditionalLogic(canHaveConditionalLogic, "next_button");
        LoadFieldConditionalLogic(canHaveConditionalLogic, "page");
    } else if(field["type"] == "submit"){
		LoadFieldConditionalLogic(canHaveConditionalLogic, "button");
	}
    else{
        LoadFieldConditionalLogic(canHaveConditionalLogic, "field");
    }

    jQuery("#field_enable_copy_values_option").prop("checked", field.enableCopyValuesOption == true ? true : false);
    jQuery("#field_copy_values_option_default").prop("checked", field.copyValuesOptionDefault == true ? true : false);
    var copyValueOptions = GetCopyValuesFieldsOptions(field.copyValuesOptionField, field);
    if(copyValueOptions.length>0){
        jQuery("#field_enable_copy_values_option").prop("disabled", false);
        jQuery("#field_copy_values_disabled").hide();
        jQuery("#field_copy_values_option_field").html(copyValueOptions);

    } else {
        jQuery("#field_enable_copy_values_option").prop("disabled", true);
        jQuery("#field_copy_values_disabled").show();
    }

    ToggleCopyValuesOption( field.enableCopyValuesOption, true );

    if(field.nextButton){

        if(field.nextButton.type == "image")
            jQuery("#next_button_image").prop("checked", true);
        else
            jQuery("#next_button_text").prop("checked", true);

        jQuery("#next_button_text_input").val(field.nextButton.text);
        jQuery("#next_button_image_url").val(field.nextButton.imageUrl);
    }

    if(field.previousButton){

        if(field.previousButton.type == "image")
            jQuery("#previous_button_image").prop("checked", true);
        else
            jQuery("#previous_button_text").prop("checked", true);

        jQuery("#previous_button_text_input").val(field.previousButton.text);
        jQuery("#previous_button_image_url").val(field.previousButton.imageUrl);
    }
    TogglePageButton("next", true);
    TogglePageButton("previous", true);

    jQuery(".gfield_category_checkbox").each(function(){
        if(field["choices"]){
            for(var i=0; i<field["choices"].length; i++){
                if(this.value == field["choices"][i].value){
                    this.checked = true;
                    return;
                }
            }
        }
        this.checked = false;
    });

    if(has_entry(field.id))
        jQuery("#field_type, #field_multiple_files").prop("disabled", true);
    else
        jQuery("#field_type, #field_multiple_files").prop("disabled", false);

    jQuery("#field_custom_field_name").val(field.postCustomFieldName);

    jQuery( '#field_columns_enabled' )
	    .prop( 'checked', Boolean( field.enableColumns ) )
	    .prop( 'disabled', has_entry( field.id ) );

    LoadFieldChoices(field);

    //displays appropriate settings
    jQuery(".field_setting").hide();

    var allSettings = getAllFieldSettings( field );

    jQuery(allSettings).show();

    //hide post category drop down if post category field is in the form
    for(var i=0; i<form.fields.length; i++){
        if(form.fields[i].type == "post_category"){
            jQuery(".post_category_setting").hide();
            break;
        }
    }

    // hide "Display placeholder" option for post category field if input type is not a select
    if(field.type == 'post_category' && inputType != 'select') {
        jQuery('.post_category_initial_item_setting').hide();
        jQuery('#gfield_post_category_initial_item_enabled').prop('checked', false);
        SetCategoryInitialItem();
    }

	// A11y enhancements: Do no allow Multi-Select input type for new forms.
	if ( field.type === 'post_tags' || field.type === 'post_category' ) {
		var inputTypeObj = ( field.type === 'post_tags' ) ? jQuery( '#post_tag_type' ) : jQuery( '#post_category_field_type' );
		if ( field.inputType == 'multiselect' ) {
			if ( inputTypeObj.data( 'multiselect' ) ) {
				inputTypeObj.append( '<option value="multiselect">' + inputTypeObj.data('multiselect') + '</option>' );
				inputTypeObj.val( 'multiselect' );
				inputTypeObj.data( 'multiselect', null );
			}

			var fieldSetting = ( field.type === 'post_tags' ) ? 'post_tag_type_setting' : 'post_category_field_type_setting';
			SetFieldAccessibilityWarning( fieldSetting, 'below' );
		}
	}

    //hide "Enable calculation" option for quantity fields
    if(field.type == 'quantity') {
        jQuery('.calculation_setting').hide();
    }

    jQuery("#post_category_field_type").val(field.inputType);

    var fg = field.simpleCaptchaFontColor == undefined ? "" : field.simpleCaptchaFontColor;
    jQuery("#field_captcha_fg").val(fg);
    SetColorPickerColor("field_captcha_fg", fg);

    var bg = field.simpleCaptchaBackgroundColor == undefined ? "" : field.simpleCaptchaBackgroundColor;
    jQuery("#field_captcha_bg").val(bg);
    SetColorPickerColor("field_captcha_bg", bg);

	jQuery("#field_captcha_type").val(field.captchaType == undefined ? "captcha" : field.captchaType);
	jQuery("#field_captcha_badge").val(field.captchaBadge == undefined ? "bottomright" : field.captchaBadge);
	jQuery("#field_captcha_size").val(field.simpleCaptchaSize == undefined ? "medium" : field.simpleCaptchaSize);

	//controlling settings based on captcha type
    if(field["type"] == "captcha"){
        SetFieldAccessibilityWarning( 'captcha', 'above' );

        var recaptcha_settings = ".captcha_language_setting, .captcha_theme_setting";
        var simple_captcha_settings = ".captcha_size_setting, .captcha_fg_setting, .captcha_bg_setting";

        if(field["captchaType"] == "simple_captcha" || field["captchaType"] == "math"){
            jQuery(simple_captcha_settings).show();
            jQuery(recaptcha_settings).hide();
        }
        else{
            jQuery(simple_captcha_settings).hide();
            jQuery(recaptcha_settings).show();
        }

        //mapping blackglass (from older version) to dark and all other themes to light
		var theme =  field.captchaTheme == undefined  || ['blackglass', 'dark'].indexOf( field.captchaTheme ) < 0 ? 'light' : 'dark';

        jQuery('#field_captcha_theme').val( theme).show();

        //check the captcha theme to reset the language since the language cannot be specifically checked
		var lang = field.captchaLanguage == undefined ? 'en' : field.captchaLanguage;
		jQuery('#field_captcha_language').val( lang ).show();

        //add captcha option to drop down if it does not already exist
        if ( jQuery('#field_captcha_type option[value="captcha"]').length < 1){
            jQuery('#field_captcha_type').prepend('<option value="captcha">reCAPTCHA</option>');
        }

    }

    //Display custom field template for texareas and text fields
    if(field["type"] == "post_custom_field" && (field["inputType"] == "textarea" || field["inputType"] == "text")){
        jQuery(".customfield_content_template_setting").show();
    }

    if(field["type"] == "name"){
		if(typeof field["nameFormat"] == 'undefined' || field["nameFormat"] != "advanced"){
            field = MaybeUpgradeNameField(field);
        } else {
            SetUpAdvancedNameField();
        }

        if(field["nameFormat"] == "simple"){
            jQuery(".default_value_setting").show();
            jQuery(".size_setting").show();
            jQuery('#field_name_fields_container').html('').hide();
            jQuery('.sub_label_placement_setting').hide();
            jQuery('.name_prefix_choices_setting').hide();
            jQuery('.name_format_setting').hide();
            jQuery('.name_setting').hide();
            jQuery('.default_input_values_setting').hide();
            jQuery('.default_value_setting').show();
        } else if(field["nameFormat"] == "extended") {
            jQuery('.name_format_setting').show();
            jQuery('.name_prefix_choices_setting').hide();
            jQuery('.name_setting').hide();
            jQuery('.default_input_values_setting').hide();
            jQuery('.input_placeholders_setting').hide();
        }
    }

    // if a product or option field, hide "other choice" setting
    if(jQuery.inArray(field['type'], ['product', 'option', 'shipping']) != -1) {
        jQuery(".other_choice_setting").hide();
    }

    // if calc enabled, hide range
    if(field.enableCalculation) {
        jQuery('li.range_setting').hide();
    }

    if(field.type == 'text') {
        if(field.inputMask) {
            jQuery(".maxlen_setting").hide();
        } else {
            jQuery(".maxlen_setting").show();
        }
    }

    if(inputType == "date"){
        ToggleDateSettings(field);
    }

    if(inputType == "email"){
        ToggleEmailSettings(field);
    }

	// Setup Password field.
	if ( field.type === 'password' || field.inputType === 'password' ) {

		// Upgrade Password field properties.
		field = UpgradePasswordField( field );

		// Create Password inputs UI.
		var passwordFields = GetCustomizeInputsUI( field );
		jQuery( '#field_password_fields_container' ).html( passwordFields );
		jQuery( '#field_password_fields_container table tr:eq(1) td:eq(0) div' ).remove();

		// Show/Hide Size setting.
		var confirmEnabled = field.inputs[1].isHidden == 'undefined' ? true : ! field.inputs[1].isHidden;
		if ( confirmEnabled ) {
			jQuery( '.size_setting' ).hide();
		}

		// Hide Password sub-label.
		jQuery( '.password_setting .custom_inputs_setting ' ).on( 'click keypress', '.gform-field__toggle', function () {
			var field = GetSelectedField(),
				confirmEnabled = ! field.inputs[ 1 ].isHidden,
				passwordSubLabel = jQuery( 'label[for="input_' + field.id + '"]' );

			if ( confirmEnabled ) {
				passwordSubLabel.show();
				jQuery( '.size_setting' ).hide();
			} else {
				passwordSubLabel.hide();
				jQuery( '.size_setting' ).show();
			}

		} );

	}

	// Accessibility and other warnings
	if ( ( field.type === 'multiselect' || field.type === 'select' ) && field.enableEnhancedUI ) {
		SetFieldAccessibilityWarning( 'enable_enhanced_ui_setting', 'below' );
	}

    if ( field.type === 'multiselect' ) {
        SetFieldAccessibilityWarning( 'multiselect', 'above' );
    }

    if ( field.labelPlacement === 'hidden_label' ) {
		SetFieldAccessibilityWarning( 'label_placement_setting', 'above' );
	}

	if ( field.label === '' ) {
		setFieldError( 'label_setting', 'below' );
	}

	if ( field.dateType === 'datepicker' ) {
		SetFieldAccessibilityWarning( 'date_input_type_setting', 'above' );
	}

	if ( field.type === 'submit' ) {
		if ( HasPageField() ) {
			SetFieldNotification( 'submit_location_setting', 'above' );
		}
		if ( form.button.type === 'image' ) {
			SetFieldAccessibilityWarning( 'submit_type_setting', 'below' );

			if ( ! form.button.imageUrl ) {
				SetFieldNotification( 'submit_image_setting', 'below' );
			}
		}
	}

	ToggleSubmitType( true );

    jQuery(document).trigger('gform_load_field_settings', [field, form]);

    gform.doAction('gform_post_load_field_settings', [field, form]);

    SetProductField(field);

    Placeholders.enable();
}

/**
 * Retrieves the settings to include for a field.
 *
 * @since 2.5
 *
 * @param {object} field The field being loaded.
 *
 * @return {string} A comma-deliniated string of the settings values.
 */
function getAllFieldSettings( field ) {
	var allSettings = fieldSettings[ field.type ];

	if ( field.inputType && field.type != 'post_category' ) {
		var additionalSettings = fieldSettings[ field.inputType ];

		if ( additionalSettings.length > 0 ) {
			allSettings += ", " + additionalSettings;
		}
	}

	var settingsArray = allSettings.split( ', ' );

	/**
	 * gform_editor_field_settings
	 *
	 * Modify the editor settings that are used for the current field, including those inherited from the inputType.
	 *
	 * @since 2.5
	 *
	 * @param {array}  settingsArray The current settings values for the field.
	 * @param {object} field         The field being modified.
	 *
	 * @return {array} The modified array of settings values.
	 */
	settingsArray = gform.applyFilters( 'gform_editor_field_settings', settingsArray, field );

	return settingsArray.join( ', ' );
}

function ToggleDateSettings(field){
    var isDateField = field["dateType"] == "datefield";
    var isDatePicker = field["dateType"] == "datepicker";
    var isDateDropDown = field["dateType"] == "datedropdown";

    jQuery('.placeholder_setting').toggle(isDatePicker);
    jQuery('.default_value_setting').toggle(isDatePicker);
    jQuery('.sub_label_placement_setting').toggle(isDateField);
    jQuery('.sub_labels_setting').toggle(isDateField);
    jQuery('.default_input_values_setting').toggle(isDateDropDown || isDateField);
    jQuery('.input_placeholders_setting').toggle(isDateDropDown || isDateField);

}

function SetUpAdvancedNameField(){
    field = GetSelectedField();
    jQuery('.name_format_setting').hide();
    jQuery('.name_setting').show();
    jQuery('.name_prefix_choices_setting').show();
    var nameFields = GetCustomizeInputsUI(field);
    jQuery('#field_name_fields_container').html(nameFields).show();

    var prefixInput = GetInput(field, field.id + '.2');
    var prefixChoices = GetInputChoices(prefixInput);
    jQuery('#field_prefix_choices').html(prefixChoices);

    ToggleNamePrefixUI(!prefixInput.isHidden);

	jQuery( '.name_setting .custom_inputs_setting' ).on( 'click', '.gform-field__toggle', function() {
		var inputId = jQuery( this ).data( 'input_id' );
		if ( inputId.toString().indexOf( ".2" ) >= 0 ) {
			var isActive = jQuery( this ).find( '.gform-field__toggle-input' ).is( ':checked' );
			ToggleNamePrefixUI( isActive );
		}
	} );

    jQuery('.default_value_setting').hide();
    jQuery('.default_input_values_setting').show();
    jQuery('.input_placeholders_setting').show();

    CreateDefaultValuesUI(field);
    CreatePlaceholdersUI(field);
    CreateAutocompleteUI(field);
    CreateInputNames(field);
}

function GetCopyValuesFieldsOptions(selectedFieldId, currentField){
    var options = [], label, field, option, currentType = GetInputType(currentField), selected;

    for(var i = 0; i < form.fields.length;i++){
        field = form.fields[i];
        if(field.id != currentField.id && GetInputType(field) == currentType && !field.enableCopyValuesOption){
            label = GetLabel(field);
            selected = selectedFieldId == field.id ?  'selected="selected"' : '';
            option = '<option value="' + field.id + '" ' + selected + '>' + label + '</option>';
            options.push(option);
        }
    }

    return options.join('');

}

function ToggleNamePrefixUI(isActive){
    jQuery('.name_prefix_choices_setting').toggle(isActive);
}


function TogglePageBreakSettings(){
    if(HasPageBreak()){
        jQuery("#gform_last_page_settings").show();
        jQuery("#gform_pagination").show();
    }
    else
    {
        jQuery("#gform_last_page_settings").hide();
        jQuery("#gform_pagination").hide();
    }
}

function SetDisableQuantity(isChecked){
    SetFieldProperty('disableQuantity', isChecked);
    if(isChecked)
        jQuery(".field_selected .ginput_quantity_label, .field_selected .ginput_quantity").hide();
    else
        jQuery(".field_selected .ginput_quantity_label, .field_selected .ginput_quantity").show();
}

function SetBasePrice(number){
    if(!number)
        number = 0;

    var currency = GetCurrentCurrency();
    var price = currency.toMoney(number);
    if(price == false)
        price = 0;

    jQuery("#field_base_price").val(price);

    SetFieldProperty('basePrice', price);
    jQuery(".field_selected .ginput_product_price, .field_selected .ginput_shipping_price").html(price);
    jQuery(".field_selected .ginput_amount").val(price);
}

function ChangeAddressType(){
    field = GetSelectedField();

    if(field["type"] != "address")
        return;
    var addressType = jQuery("#field_address_type").val();
    var countryInput = GetInput(field, field.id + ".6");
    var country = jQuery("#field_address_country_" + addressType).val();
    if(country == ''){
        countryInput.isHidden = false
    } else {
        countryInput.isHidden = true;
    }


    SetAddressType( false );
}

function SetAddressType( isInit ){
    field = GetSelectedField();

    if(field["type"] != "address")
        return;

    SetAddressProperties();
    jQuery(".gfield_address_type_container").hide();
    jQuery("#address_type_container_" + jQuery("#field_address_type").val()).show();
    CreatePlaceholdersUI(field);
    CreateAutocompleteUI(field);
}

function UpdateAddressFields(){
    var addressType = jQuery("#field_address_type").val();
    field = GetSelectedField();

    var address_fields_str = GetCustomizeInputsUI(field);
    jQuery("#field_address_fields_container").html(address_fields_str);

    //change zip label
    var zipInput = GetInput(field, field.id + ".5");
    var zip_label = jQuery("#field_address_zip_label_" + addressType).val();
    jQuery("#field_custom_input_default_label_" + field.id + "_5").text(zip_label);
    jQuery("#field_custom_input_label_" + field.id + "\\.5").attr("placeholder", zip_label);
    if(!zipInput.customLabel){
        jQuery(".field_selected #input_" + field["id"] + "_5_label").html(zip_label);
    }

    //change state label
    var stateInput = GetInput(field, field.id + ".4");
    var state_label = jQuery("#field_address_state_label_" + addressType).val();
    jQuery("#field_custom_input_default_label_" + field.id + "_4").text(state_label);
    jQuery("#field_custom_input_label_" + field.id + "\\.4").attr("placeholder", state_label);
    if(!stateInput.customLabel){
        jQuery(".field_selected #input_" + field["id"] + "_4_label").html(state_label);
    }

    // hide country drop down and setting.
    var isInternational = jQuery( "#field_address_country_" + addressType ).val() == "";
    var hideCountrySetting = ! isInternational;
    var hideCountryField = ! isInternational || ! jQuery( '#field_address_fields_container [id="gforms-editor-toggle-' + field.id + '.6"' ).is( ':checked' );

    // hide country setting if this address type applies to a specific country.
    if ( hideCountrySetting ) {
        jQuery('.field_custom_input_row_input_' + field.id + '_6').hide();
    } else {
        jQuery('.field_selected .field_custom_input_row_input_' + field.id + '_6').show();
    }

    // hide country field if this address type applies to a specific country or if show country is toggled off.
    if ( hideCountryField ){
        jQuery('.field_selected #input_' + field.id + '_6_container').hide();
    } else {
        //selects default country and displays drop down
        jQuery(".field_selected #input_" + field.id + "_6").val(jQuery("#field_address_default_country_" + addressType).val());
        jQuery(".field_selected #input_" + field.id + "_6_container").show();
    }

    var has_state_drop_down = jQuery("#field_address_has_states_" + addressType).val() != "";
    if(has_state_drop_down){
        jQuery(".field_selected .state_text").hide();
        var selected_state = jQuery("#field_address_default_state_" + addressType).val()
        var state_dropdown = jQuery(".field_selected .state_dropdown");
        state_dropdown.append(jQuery('<option></option>').val(selected_state).html(selected_state));
        state_dropdown.val(selected_state).show();
    }
    else{
        jQuery(".field_selected .state_dropdown").hide();
        jQuery(".field_selected .state_text").show();
    }
}

function SetAddressProperties(){
    field = GetSelectedField();

    var addressType = jQuery("#field_address_type").val();
    SetFieldProperty("addressType", addressType);
    SetFieldProperty("defaultState", jQuery("#field_address_default_state_" + addressType).val());
    SetFieldProperty("defaultProvince",""); //for backwards compatibility

    //Only save the hide country property for address types that have that option (ones with no country)
    var country = jQuery("#field_address_country_" + addressType).val();

    if(country == ""){
        country = jQuery("#field_address_default_country_" + addressType).val();
    }

    SetFieldProperty("defaultCountry",country);

    UpdateAddressFields();
}

function MaybeUpgradeNameField(field){

    if(typeof field.nameFormat == 'undefined' || field.nameFormat == '' || field.nameFormat == 'normal' || (field.nameFormat == 'simple' && !has_entry(field.id))){
        field = UpgradeNameField(field, true, true, true);
    }

    return field;
}

function UpgradeNameField(field, prefixHiddex, middleHidden, suffixHidden){

    field.nameFormat = 'advanced';
    field.inputs = MergeInputArrays(GetAdvancedNameFieldInputs(field, prefixHiddex, middleHidden, suffixHidden), field.inputs);

    RefreshSelectedFieldPreview(function(){
        SetUpAdvancedNameField();
    });

    return field;
}

function UpgradeDateField(field){
    if(field.type != 'date' && field.inputType != 'date' ){
        return field;
    }

    if(typeof field.dateType != 'undefined' && field.dateType != 'datepicker' && !field.inputs){
        field.inputs = GetDateFieldInputs(field);
    }

    return field;
}

function UpgradeTimeField(field){
    if(field.type != 'time' && field.inputType != 'time' ){
        return field;
    }

    if(!field.inputs){
        field.inputs = GetTimeFieldInputs(field);
    }

    return field;
}

function UpgradeEmailField(field){
    if(field.type != 'email' && field.inputType != 'email'){
        return field;
    }

    if(field.emailConfirmEnabled && !field.inputs){
        field.inputs = GetEmailFieldInputs(field);
		field.inputs[0].placeholder = field.placeholder
    }

    return field;
}

function UpgradePasswordField(field){
	if(field.type != 'password' && field.inputType != 'password'){
		return field;
	}

	if(!field.inputs){
		field.inputs = GetPasswordFieldInputs(field);
		field.inputs[0].placeholder = field.placeholder
	}

	return field;
}

function UpgradeAddressField(field){

    if(field.hideCountry){
        var countryInput = GetInput(field, field.id + ".6");
        countryInput.isHidden = true;
    }
    delete field.hideCountry;

    if(field.hideAddress2){
        var address2Input = GetInput(field, field.id + ".2");
        address2Input.isHidden = true;
    }
    delete field.hideAddress2;

    if(field.hideState){
        var stateInput = GetInput(field, field.id + ".4");
        stateInput.isHidden = true;
    }
    delete field.hideState;

    return field;
}

function UpgradeConsentField(field) {
    if(field.type !== 'consent'){
        return field;
    }

    if(field.choices[1] && field.choices[1]['value'] === "0"){
        field.choices.pop();
    }

    return field;
}

function TogglePasswordVisibility( isInit ){
	if ( jQuery( '#gfield_password_visibility_enabled' ).is( ":checked" ) ) {
		jQuery( '.gfield.field_selected .ginput_container_password span button' ).show();
	} else {
		jQuery( '.gfield.field_selected .ginput_container_password span button' ).hide();
	}
}

function TogglePasswordStrength( isInit ){

    if(jQuery("#gfield_password_strength_enabled").is(":checked")){
        jQuery("#gfield_min_strength_container").show();
    }
    else{
        jQuery("#gfield_min_strength_container").hide();
    }
}

function ToggleCategory( isInit ){

    if(jQuery("#gfield_category_all").is(":checked")){
        jQuery("#gfield_settings_category_container").hide();
         SetFieldProperty("displayAllCategories", true);
         SetFieldProperty("choices", new Array()); //reset selected categories
    }
    else{
        jQuery("#gfield_settings_category_container").show();
        SetFieldProperty("displayAllCategories", false);
    }
}

function SetCopyValuesOptionLabel(value){
    SetFieldProperty('copyValuesOptionLabel', value);
    jQuery('.field_selected .copy_values_option_label').html(value);
}

function SetCustomFieldTemplate(){
    var enabled = jQuery("#gfield_customfield_content_enabled").is(":checked");
    SetFieldProperty("customFieldTemplate", enabled ? jQuery("#field_customfield_content_template").val() : null);
    SetFieldProperty("customFieldTemplateEnabled", enabled );
}

function SetCategoryInitialItem(){
    var enabled = jQuery("#gfield_post_category_initial_item_enabled").is(":checked");
    SetFieldProperty("categoryInitialItem", enabled ? jQuery("#field_post_category_initial_item").val() : null);
    SetFieldProperty("categoryInitialItemEnabled", enabled );
}

function PopulateContentTemplate(fieldName){
    if(jQuery("#" + fieldName).val().length == 0){
        var field = GetSelectedField();
        jQuery("#" + fieldName).val("{" + field.label + ":" + field.id + "}");
    }
}

function TogglePostContentTemplate(isInit){
    if(jQuery("#gfield_post_content_enabled").is(":checked")){
        jQuery("#gfield_post_content_container").show();
        if(!isInit){
            PopulateContentTemplate("field_post_content_template");
        }
    }
    else{
        jQuery("#gfield_post_content_container").hide();
    }
}

function TogglePostTitleTemplate(isInit){
    if(jQuery("#gfield_post_title_enabled").is(":checked")){
        jQuery("#gfield_post_title_container").show();
        if(!isInit)
            PopulateContentTemplate("field_post_title_template");

    }
    else{
        jQuery("#gfield_post_title_container").hide();
    }
}

function ToggleCustomFieldTemplate(isInit){
    if(jQuery("#gfield_customfield_content_enabled").is(":checked")){
        jQuery("#gfield_customfield_content_container").show();
        if(!isInit){
            PopulateContentTemplate("field_customfield_content_template");
        }
    }
    else{
        jQuery("#gfield_customfield_content_container").hide();
    }
}

function ToggleInputName( isInit ){
    if(jQuery('#field_prepopulate').is(":checked")){
        jQuery('#field_input_name_container').show();
    }
    else{
        jQuery('#field_input_name_container').hide();
        jQuery("#field_input_name").val("");
    }

}

function SetFieldColumns(){

    SetFieldChoices();
}

function ToggleChoiceValue( isInit ){
    var field = GetSelectedField();
    var suffix = field.enablePrice ? "_and_price" : "";
    var container = jQuery('#gfield_settings_choices_container');

    //removing all classes
    container.removeClass("choice_with_price choice_with_value choice_with_value_and_price");

    var isShowValues = jQuery('#field_choice_values_enabled').is(":checked");
    if(isShowValues){
        container.addClass("choice_with_value" + suffix);
    }
    else if(field.enablePrice){
        container.addClass("choice_with_price");
    }
}

function ToggleInputChoiceValue($container, enabled){
    if(typeof enabled == 'undefined'){
        enabled = false;
    }
    var field = GetSelectedField();
    var inputId = $container.find('li').data('input_id');
    var input = GetInput(field, inputId);
    input.enableChoiceValue = enabled;
    //removing all classes
    $container.removeClass("choice_with_value");

    if(enabled){
        $container.addClass("choice_with_value");
    }
}

function ToggleCopyValuesActivated(isActivated){
    jQuery('.field_selected .copy_values_activated').prop('checked', isActivated);
    var field = GetSelectedField();
    jQuery('#input_'+ field.id).toggle(!isActivated);
}

function TogglePageButton(button_name, isInit){
    var isText = jQuery("#" + button_name + "_button_text").is(":checked");
    show_element = isText ? "#" + button_name + "_button_text_container" : "#" + button_name + "_button_image_container"
    hide_element = isText ? "#" + button_name + "_button_image_container"  : "#" + button_name + "_button_text_container";

    if(isInit){
        jQuery(hide_element).hide();
        jQuery(show_element).show();
    }
    else{
        jQuery(hide_element).hide();
        jQuery(show_element).fadeIn(800);
     }
}

function SetPageButton(button_name){
    field = GetSelectedField();
    var buttonType = jQuery("#" + button_name + "_button_image").is(":checked") ? "image" : "text";
    field[button_name + "Button"]["type"] = buttonType;
    if(buttonType == "image"){
        field[button_name + "Button"]["text"] = "";
        field[button_name + "Button"]["imageUrl"] = jQuery("#" + button_name + "_button_image_url").val();
    }
    else{
        field[button_name + "Button"]["text"] = jQuery("#" + button_name + "_button_text_input").val();
        field[button_name + "Button"]["imageUrl"] = "";
    }
}

function ToggleCustomField( isInit ){

    var isExisting = jQuery("#field_custom_existing").is(":checked");
    show_element = isExisting ? "#field_custom_field_name_select" : "#field_custom_field_name_text"
    hide_element = isExisting ? "#field_custom_field_name_text"  : "#field_custom_field_name_select";

    jQuery(hide_element).hide();
    jQuery(show_element).show();

}

function ToggleInputMask(isInit){

    if(jQuery("#field_input_mask").is(":checked")){
        jQuery("#gform_input_mask").show();
        jQuery(".maxlen_setting").hide();

        SetFieldProperty('inputMask', true);

        //setting max length to blank
        jQuery("#field_maxlen").val("");
        SetFieldProperty('maxLength', "");
    }
    else{
        jQuery("#gform_input_mask").hide();
        jQuery(".maxlen_setting").show();
        SetFieldProperty('inputMask', false);
        SetFieldProperty('inputMaskValue', '');
		SetFieldProperty('inputMaskIsCustom', false);
    }
}

function ToggleInputMaskOptions(isInit){

	var isStandard = jQuery('#field_mask_standard').is(':checked'),
		show_element = isStandard ? '#field_mask_select' : '#field_mask_text, .mask_text_description',
		hide_element = isStandard ? '#field_mask_text, .mask_text_description' : '#field_mask_select';

	jQuery(hide_element).val('').hide();
	jQuery(show_element).show();

	if (!isInit) {
		SetFieldProperty('inputMaskValue', '');
		SetFieldProperty('inputMaskIsCustom', !isStandard);
	}
}

function ToggleAutoresponder(){
    if(jQuery("#form_autoresponder_enabled").is(":checked"))
        jQuery("#form_autoresponder_container").show("slow");
    else
        jQuery("#form_autoresponder_container").hide("slow");
}

function ToggleMultiFile(isInit){

    if(jQuery("#field_multiple_files").prop("checked")){
        jQuery("#gform_multiple_files_options").show();
		var $uploadField = jQuery('.gform_fileupload_multifile');
		var pluploadSettings = $uploadField.data('settings');
		if ( pluploadSettings && typeof pluploadSettings.chunk_size != 'undefined' ) {
			jQuery('#gform_server_max_file_size_notice').hide();
		}
        SetFieldProperty('multipleFiles', true);
    }
    else{
        jQuery("#gform_multiple_files_options").hide();

        SetFieldProperty('multipleFiles', false);

        jQuery("#field_max_files").val("");
        SetFieldProperty('maxFiles', "");

    }

    if(!isInit){
        var field = GetSelectedField();
		StartChangeInputType("fileupload", field);

    }
}

function SetAutocompleteProperty( isInit, value ) {
	SetFieldProperty( 'enableAutocomplete' , value );
	ToggleAutocompleteAttribute( isInit );
}

function ToggleAutocompleteAttribute( isInit ) {

	if( jQuery( "#field_enable_autocomplete" ).is( ":checked" ) ) {
		jQuery( "#autocomplete_attribute_container" ).show();
	}
	else{
		jQuery( "#autocomplete_attribute_container" ).hide();
	}
}

function InitAutocompleteOptions( isInit ) {
	jQuery( '#field_enable_autocomplete' ).prop( "checked", field.enableAutocomplete ? true : false );
	ToggleAutocompleteAttribute( true) ;
}

function HasPostContentField(){
    for(var i=0; i<form.fields.length; i++){
        var type = form.fields[i].type;
        if(type == "post_content")
            return true;
    }
    return false;
}

function HasPostTitleField(){
    for(var i=0; i<form.fields.length; i++){
        var type = form.fields[i].type;
        if(type == "post_title")
            return true;
    }
    return false;
}

function HasCustomField(){
    for(var i=0; i<form.fields.length; i++){
        var type = form.fields[i].type;
        if(type == "post_custom_field")
            return true;
    }
    return false;
}

function HasPageBreak(){
    for(var i=0; i<form.fields.length; i++){
        var type = form.fields[i].type;
        if(type == "page")
            return true;
    }
    return false;
}

function SetNextButtonConditionalLogic(isChecked){
    var field = GetSelectedField();

    field.nextButton.conditionalLogic = isChecked ? new ConditionalLogic() : null;
}

/**
 * Sets the conditonal logic for the submit button.
 *
 * @since 2.6
 */
function SetSubmitConditionalLogic(isChecked) {
	form.button.conditionalLogic = isChecked ? new ConditionalLogic() : null;
}

function UpdateFormObject(){

	form["button"]["text"] = jQuery('#submit_text').val();
	form["button"]["width"] = jQuery("input:radio[name='submit_width']:checked").val();
	form["button"]["location"] = jQuery("input:radio[name='submit_location']:checked").val();
	form["button"]["imageUrl"] = jQuery('#submit_image').val();
	form["button"]["layoutGridColumnSpan"] = jQuery( '#field_submit' ).getGridColumnSpan();

    form.postContentTemplateEnabled = false;
    form.postTitleTemplateEnabled = false;
    form.postTitleTemplate = "";
    form.postContentTemplate = "";

    if( HasPageField() ) {
		SetSubmitLocation( 'bottom' );
		jQuery('#field_submit').attr( 'data-field-position', 'bottom' );
		jQuery('input[name="submit_location"][value="bottom"]').prop( 'checked', true );
	} else {
		jQuery( '.submit_location_setting' ).prev( '.gform-alert--notice' ).remove();
	}

    if(HasPostField()){
        form.postAuthor = jQuery('#field_post_author').val() ? jQuery('#field_post_author').val() : "";
        form.useCurrentUserAsAuthor = jQuery('#gfield_current_user_as_author').is(":checked");
        form.postCategory = jQuery('#field_post_category').val();
        form.postFormat = jQuery('#field_post_format').length != 0 ? jQuery('#field_post_format').val() : 0;
        form.postStatus = jQuery('#field_post_status').val();
    }

    if(jQuery("#gfield_post_content_enabled").is(":checked") && HasPostContentField()){
        form.postContentTemplateEnabled = true;
        form.postContentTemplate = jQuery("#field_post_content_template").val();
    }

    if(jQuery("#gfield_post_title_enabled").is(":checked")  && HasPostTitleField()){
        form.postTitleTemplateEnabled = true;
        form.postTitleTemplate = jQuery("#field_post_title_template").val();
    }

    if(jQuery("#gform_last_page_settings").is(":visible")){
        form.lastPageButton = new Button();
        form.lastPageButton.type = jQuery("#last_page_button_text").is(":checked") ? "text" : "image";
        if(form.lastPageButton.type == "image"){
            form.lastPageButton.text = "";
            form.lastPageButton.imageUrl = jQuery("#last_page_button_image_url").val();
        }
        else{
            form.lastPageButton.text = jQuery("#last_page_button_text_input").val();
            form.lastPageButton.imageUrl = "";
        }
    }
    else{
        form.lastPageButton = null;
    }

    if(jQuery("#gform_pagination").is(":visible")){
        form["pagination"] = new Object();
        var type = jQuery("input[name=\"pagination_type\"]:checked").val();
        form["pagination"]["type"] = type;

        var pageNames = jQuery(".gform_page_names input");
        form["pagination"]["pages"] = new Array();
        for(var i=0; i<pageNames.length; i++){
            form["pagination"]["pages"].push(jQuery(pageNames[i]).val());
        }

        if(type == "percentage"){
            form["pagination"]["style"] = jQuery("#percentage_style").val();
            form["pagination"]["backgroundColor"] = form["pagination"]["style"] == "custom" ? jQuery("#percentage_style_custom_bgcolor").val() : null;
            form["pagination"]["color"] = form["pagination"]["style"] == "custom" ? jQuery("#percentage_style_custom_color").val() : null;
            form["pagination"]["display_progressbar_on_confirmation"] = jQuery("#percentage_confirmation_display").is(":checked");
            form["pagination"]["progressbar_completion_text"] = jQuery("#percentage_confirmation_display").is(":checked") ? jQuery("#percentage_confirmation_page_name").val() : null;
        }
        else{
            form["pagination"]["backgroundColor"] = null;
            form["pagination"]["color"] = null;
            form["pagination"]["display_progressbar_on_confirmation"] = null;
            form["pagination"]["progressbar_completion_text"] = null;
        }

        form["firstPageCssClass"] = jQuery("#first_page_css_class").val();
    }
    else{
        form["pagination"] = null;
        form["firstPageCssClass"] = null;
    }

    SortFields();

    // allow users to update form with custom function before save
    if(window["gform_before_update"]){
        form = window["gform_before_update"](form);
        if(window.console)
            console.log('"gform_before_update" is deprecated since version 1.7! Use the "gform_pre_form_editor_save" filter instead.');
    }

    // new method for filtering the form object before save
    form = gform.applyFilters('gform_pre_form_editor_save', form);
    return form;

}

function SortFields(){
    var fields = new Array();
    jQuery(".gfield").each(function(){
		if( jQuery(this).hasClass('spacer') || 'gform_editor_submit_container' == jQuery(this).attr('data-field-class') ) {
			return;
		}
        id = this.id.substr(6);
        fields.push(GetFieldById(id));
    });
    form.fields = fields;
}

/**
 * Toggle settings and focus first element
 *
 * @param element
 */
function EditField( element ) {
	event.stopPropagation();
	// patch for safari when focus is returned here on esc key from settings
	if ( event.keyCode === 27 ) {
		return;
	}

	FieldClick( gform.tools.getClosest( element, '.gfield' ) );

	var settingsPane = gform.tools.getNodes( '.sidebar__panel--settings', false, document, true )[0];
	var focusableSettings = gform.tools.getFocusable( settingsPane );

	if ( focusableSettings[0]) {
		setTimeout( function() { focusableSettings[0].focus(); }, 50 );
	}
}

/**
* Mark a field for deletion upon save.
*
* @param element The field element being deleted.
*/
function DeleteField( element ) {
	event.stopPropagation();

	// Get field ID from element.
	var fieldId = jQuery( element )[0].id.split( '_' )[2];

	// Confirm that user is aware about entry data being deleted.
	if ( ! HasConditionalLogicDependency( fieldId ) && ! confirm( gf_vars.confirmationDeleteField ) ) {
		return;
	}

	// If conditional logic dependency is found, confirm that user is aware and wants to proceed.
	if ( HasConditionalLogicDependency( fieldId ) && ! confirm( gf_vars.conditionalLogicDependency ) ) {
		return;
	}

	// Initialize deleted field property.
	if ( ! form.deletedFields ) {
		form.deletedFields = [];
	}

	// Add field ID to form object.
	form.deletedFields.push( fieldId );

	// Loop through form fields.
	for ( var i = 0; i < form.fields.length; i++ ) {

		// If field ID matches the one to be deleted, delete it.
		if ( form.fields[i].id == fieldId ) {

			// Remove the field.
			form.fields.splice(i, 1);

			// Fade out field, then remove.
			jQuery( '#field_' + fieldId ).fadeOut( 'slow', function() {

				// Remove field element.
				jQuery( '#field_' + fieldId ).remove();

				if ( form['fields'].length === 0 ) {
					jQuery('#field_submit').remove();
				} else {
					if ( form['fields'][0].type === 'submit' ) {
						jQuery('#field_submit').remove();
						form['fields'].splice( 0, 1 );
					}
				}

				// Show no fields placeholder if there are no form fields.
				if ( form.fields.length === 0 ) {
					jQuery( '#no-fields' ).show();
				}

				/**
				 * Do something after the field has been removed from the DOM.
				 *
				 * @since 2.5
				 *
				 * @param object form    The current form object.
				 * @param int    fieldId The ID of the current field.
				 */
				gform.doAction( 'gform_after_field_removed', form, fieldId );

			} );

			// Hide field settings panel.
			HideSettings( 'field_settings' );

			break;

		}

	}

	// Activate the Add Fields tab now that the currently selectd field has been deleted.
	jQuery('.sidebar' ).tabs( 'option', 'active', 0 )

	// Toggle page break settings.
	TogglePageBreakSettings();

	// Run field deleted action.
	jQuery( document ).trigger( 'gform_field_deleted', [ form, fieldId ] );

}

/**
* Check if a field or choice has a field with conditional logic dependent upon it.
*
* If a field is being deleted, only a field ID is required. If a choice is being edited or deleted
* both the field ID and the value of the choice should be provided. This function will then loop
* through all form fields and each field's conditional logic rules to find if any field depends on
* the field being modified for conditional logic.
*
* Triggered when:
*   delete field        pass field ID
*   delete choice       pass field ID, value
*   edit choice         pass field ID, value
*
* @param fieldId the field ID that is being edited or deleted
* @param value Optional the value of the choice being edited or deleted
*
* @returns {Boolean}
*/
function HasConditionalLogicDependencyLegwork(fieldId, value) {

    // check form button conditional logic
    if(form.button && ObjectHasConditionalLogicDependency(form.button, fieldId, value) )
        return true;

    // check confirmations conditional logic
    for(i in form.confirmations) {

        if(!form.confirmations.hasOwnProperty(i))
            continue;

        if( ObjectHasConditionalLogicDependency(form.confirmations[i], fieldId, value) )
            return true;
    }

    // check notifications conditional logic
    for(i in form.notifications) {

        if(!form.notifications.hasOwnProperty(i))
            continue;

        if( ObjectHasConditionalLogicDependency(form.notifications[i], fieldId, value) )
            return true;
    }

    // check field conditional logic
    for(i in form.fields) {

        if(!form.fields.hasOwnProperty(i))
            continue;

        var field = form.fields[i];

        if( ObjectHasConditionalLogicDependency(field, fieldId, value) )
            return true;

        // if this is a page field, check the next button conditional logic as well
        if( GetInputType(field) == 'page' && ObjectHasConditionalLogicDependency(field.nextButton, fieldId, value) )
            return true;

    }

    return false;
}

/**
* Runs the check for conditional logic dependencies and then applies a filter to result.
*
* Couldn't find a tidier way of applying the filter in the original function so I made this
* caller function so the code remains effecient and user can override the result in cases
* of failure and success.
*
* @param fieldId
* @param value
*/
function HasConditionalLogicDependency(fieldId, value) {
    var result = HasConditionalLogicDependencyLegwork(fieldId, value);
    return gform.applyFilters('gform_has_conditional_logic_dependency', result, fieldId, value);
}

/**
* Determine if an object has a conditional logic rule dependent on the field and/or value provided.
*
* All GF objects (fields, buttons, confirmations, etc) that have conditional logic have it stored in a
* 'conditionaLogic' property. This function checks if this property exists and if so loops through all
* the rules until it finds a match. If not match is found, function returns false.
*
* @param object The GF Object that has conditional logic property (fields, buttons, confirmation, notifications, paging)
* @param fieldId The fieldId being modified and on which a dependency is being searched for
* @param value Optional The value of the choice being being modified or deleted
*
* @returns {Boolean}
*/
function ObjectHasConditionalLogicDependency(object, fieldId, value) {

    if(!object.conditionalLogic)
        return false;

    if(typeof value == 'undefined')
        var value = false;

    var rules = object.conditionalLogic.rules;

    for(i in rules) {

        if(! rules.hasOwnProperty(i))
            continue;

        var rule = rules[i];

        // if rule field ID does not match the field ID of the field being modified, continue
        if(rule.fieldId != fieldId)
            continue;

        // if value is provided and the rule value does not match provided value, continue
		if(value !== false && rule.value != value)
            continue;

        if (!value && !rule.value) {
            var ruleField = GetFieldById(fieldId);
            if (ruleField && ruleField.choices && ruleField.placeholder) {
                continue;
            }
        }

        return true;
    }

    return false;
}

function HasDependentRule(rules, fieldId, value) {

    if(typeof value == 'undefined')
        value = false;

    for(i in rules) {

        if(! rules.hasOwnProperty(i))
            continue;

        var rule = rules[i];

        // if rule field ID does not match the field ID of the field being modified, continue
        if(rule.fieldId != fieldId)
            continue;

        // if value is provided and the rule value does not match provided value, continue
        if(value !== false && rule.value != value)
            continue;

        return true;
    }

    return false;
}

function CheckChoiceConditionalLogicDependency(input) {

    var field = GetSelectedField();

    var previousValue = jQuery(input).data('previousValue'); // Get the value before checking. Fixes an issue in Chrome on Windows.
	if (previousValue == undefined){
		//set a value because undefined cannot be saved with jQuery data
		previousValue = '';
	}

    if(HasConditionalLogicDependency(field.id, previousValue)) {

        // confirm that the user wants to make the modification
        if(confirm(gf_vars.conditionalLogicDependencyChoiceEdit)) {
            return;
		}

        // if user does not want to make modification, replace with original value
		jQuery(input).val(previousValue).trigger('keyup');
		jQuery(input).data('previousValue', previousValue);

    }

}

function StartDuplicateField(element) {

    var sourcefieldId = jQuery(element)[0].id.split("_")[2];

	gform.doAction( 'gform_before_field_duplicated', sourcefieldId );

	for(fieldIndex in form.fields){

        if(! form.fields.hasOwnProperty(fieldIndex))
            continue;

        if(form.fields[fieldIndex].id == sourcefieldId) {

            // create a copy of the field
            var field = Copy(form.fields[fieldIndex]);
            field.id = GetNextFieldId();

            if(field.inputs != null) {

                for(inputIndex in field.inputs) {

                    if(!field.inputs.hasOwnProperty(inputIndex))
                        continue;

                    var id = field.inputs[inputIndex]['id'] + '',
						newId = id == sourcefieldId ? field.id : id.replace(/(\d+\.)/, field.id + '.');

                    field.inputs[inputIndex]['id'] = newId;

                }
            }

	        /**
	         * Modify the field that is being duplicated.
	         *
	         * @param object field The duplicated field.
	         * @param object form  The current form object.
	         *
	         * @since @todo
	         */
	        field = gform.applyFilters( 'gform_duplicate_field', field, form );
	        field = gform.applyFilters( 'gform_duplicate_field_{0}'.format( GetInputType( field ) ), field, form );

            form.fields.splice(fieldIndex, 0, field);
            DuplicateField(field, sourcefieldId);
            return;
        }
    }
}

function EndDuplicateField(field, fieldString, sourceFieldId) {

	gform.doAction( 'gform_field_duplicated', form, field, jQuery( fieldString ), sourceFieldId );

}

function GetFieldsByType(types){
    var fields = new Array();
    for(var i=0; i<form["fields"].length; i++){
        if(IndexOf(types, form["fields"][i]["type"]) >= 0)
            fields.push(form["fields"][i]);
    }
    return fields;
}

function GetNextFieldId(){
	var nextFieldId;
   	if ( typeof form.nextFieldId == 'undefined' ) {
		var max = 0;
		for(var i=0; i<form.fields.length; i++){
			if(parseFloat(form.fields[i].id) > max)
				max = parseFloat(form.fields[i].id);
		}

		if (form.deletedFields) {
			for (var i = 0; i < form.deletedFields.length; i++) {
				if (parseFloat(form.deletedFields[i]) > max)
					max = parseFloat(form.deletedFields[i]);
			}
		}
		nextFieldId = parseFloat(max) + 1;
	} else {
		nextFieldId = parseInt(form.nextFieldId);
	}

	form.nextFieldId = nextFieldId + 1;

    return nextFieldId;
}

function GetFirstField() {
	for ( var i = 0; i < form.fields.length; i++ ) {
		return form.fields[i].id;
	}
}

function EndAddField(field, fieldString, index){
    gf_vars['currentlyAddingField'] = false;

    // We just added a field. Let's hide the No Fields placeholder.
    jQuery( '#no-fields' ).hide();

    jQuery('#gform_adding_field_spinner').remove();

    //sets up DOM for new field
    if(typeof index != 'undefined'){
        form.fields.splice(index, 0, field);
        if (index === 0) {
            jQuery('#gform_fields').prepend(fieldString);
        } else {
            jQuery('#gform_fields').children().eq(index - 1).after(fieldString);
        }
    } else {
        if ( jQuery( '#field_submit' ).length ) {
			jQuery( fieldString ).insertBefore ( jQuery( '#field_submit' ) );
		} else {
			jQuery('#gform_fields').append(fieldString);
		}
        //creates new javascript field
        form.fields.push(field);
    }

    var newFieldElement = jQuery('#field_' + field.id);
    newFieldElement.animate({ backgroundColor: '#FFFBCC' }, 'fast', function(){jQuery(this).animate({backgroundColor: '#FFF'}, 'fast', function(){jQuery(this).css('background-color', '');})})

    //Unselects all fields
    jQuery('.selectable').removeClass('field_selected');

    //Closing editors
    HideSettings('field_settings');
    HideSettings('form_settings');
    HideSettings('last_page_settings');

    //Select current field
    newFieldElement.addClass('field_selected');

    //initializes new field with default data
    SetFieldSize(field.size);

	SetFieldEnhancedUI( field.enableEnhancedUI );

    TogglePageBreakSettings();

    InitializeFields();

    newFieldElement.removeClass('field_selected');

	jQuery(document).trigger('gform_field_added', [form, field]);
}

function StartChangeNameFormat(format){
    field = GetSelectedField();
    UpgradeNameField(field, false, true, false);
}

function StartChangeCaptchaType(captchaType){
    field = GetSelectedField();
    field["captchaType"] = captchaType;
    SetFieldProperty('captchaType', captchaType);
    StartChangeInputType(field["type"], field);
	ResetRecaptcha();
}

function ResetRecaptcha(){
	field = GetSelectedField();
    field['captchaLanguage'] = 'en';
    field['captchaTheme'] = 'light';
}

function StartChangeProductType(type) {
	field = GetSelectedField();

	if (type === 'radio' || type === 'select') {
		field.enablePrice = true;
	} else {
		field.enablePrice = null;
		field.choices = null;
	}

	if (type !== 'calculation') {
		field.enableCalculation = false;
		field.calculationFormula = '';
	}

	return StartChangeInputType(type, field);
}

function StartChangeDonationType(type){
    field = GetSelectedField();
    if(type != "donation")
        field["enablePrice"] = true;
    else
        field["enablePrice"] = null;

    return StartChangeInputType(type, field);
}

function StartChangeShippingType(type) {
	field = GetSelectedField();
	if (type !== 'singleshipping') {
		field.enablePrice = true;
	} else {
		field.enablePrice = null;
		field.choices = null;
	}

	return StartChangeInputType(type, field);
}

function StartChangePostCategoryType(type){

    if(type == 'dropdown') {

        jQuery('.post_category_initial_item_setting').hide();

    } else {

        jQuery('.post_category_initial_item_setting').show();

    }

    field = GetSelectedField();
    return StartChangeInputType(type, field);
}

function StartChangePostCustomFieldType( type ) {
	if ( jQuery.inArray( type, [ 'radio', 'select', 'checkbox', 'multiselect' ] ) === -1 ) {
		field.choices = null;
	}
	return StartChangeInputType(type, field);
}

function EndChangeInputType(params){
    var fieldId = params.id, fieldType = params.type, fieldString = params.fieldString;

    jQuery("#field_" + fieldId).html(fieldString);

    var field = GetFieldById(fieldId);

    //setting input type if different than field type
    field.inputType = field.type != fieldType ? fieldType : "";

    SetDefaultValues(field);

    SetFieldLabel(field.label);
	SetAriaLabel(field.label);
    SetFieldSize(field.size);
    SetFieldDefaultValue(field.defaultValue);
    SetFieldDescription(field.description);
    SetFieldCheckboxLabel(field.checkboxLabel);
    SetFieldRequired(field.isRequired);
    InitializeFields();
	jQuery('.field_settings').css('opacity', '1');
    ShowSettings(field);
}

function InitializeFields(){
    //Border on/off logic on mouse over
    jQuery('.selectable').hover(
      function () {
        jQuery(this).addClass('field_hover');
      },
      function () {
        jQuery(this).removeClass('field_hover');
      }
    ).focus(
		function () {
			if ( jQuery( this ).hasClass( 'field_selected' ) ) {
				return;
			}
			jQuery( '.field_hover' ).removeClass( 'field_hover' );
			jQuery( '.field_selected' ).removeClass( 'field_selected' );
			jQuery( this ).addClass( 'field_hover' );
			jQuery( this ).addClass( 'field_selected' );
		}
	).on( 'keypress', this, function ( event ) {
		var key = event.which;
		if ( key == 13 ) {
			jQuery( '#general_tab_toggle' ).focus();
		}
	} );

    jQuery('.field_delete_icon, .field_duplicate_icon').click(function(event){
        event.stopPropagation();
    });

    jQuery('.field_settings, #form_settings, #last_page_settings, #pagination_settings, .form_delete_icon, .all-merge-tags').click(function(event){

	    /**
	     * Fires when an element in the FormEditor is clicked that should have no effect.
	     *
	     * This action is useful if you need to perform an action using the click event without forcing propagation.
	     *
	     * @since 2.5
	     *
	     * @param {DomEvent} event The dom event.
	     */
    	gform.doAction( 'formEditorNullClick', event );

    	event.stopPropagation();
    });


}

function FieldClick( field ) {

	//disable click that happens right after dragging ends
	if ( gforms_dragging == field.id ) {
		gforms_dragging = 0;
		return;
	}

	// force focus to ensure onblur events fire for field setting inputs
	jQuery( 'input#gform_force_focus' ).focus();

	//unselects all fields
	jQuery( '.selectable' ).removeClass( 'field_selected' );

	//selects current field
	var $field = jQuery( field );
	$field.removeClass( 'field_hover' ).addClass( 'field_selected' );

	// Apply field class data to settings container in 2.5+.
	var $fieldSettingsContainer = jQuery( '#field_settings_container' );

	if ( $fieldSettingsContainer.length ) {
		var fieldClass = jQuery( field ).data( 'field-class' );
		var previousFieldClass = $fieldSettingsContainer.data( 'active-field-class' );

		$fieldSettingsContainer.removeClass( previousFieldClass );
		$fieldSettingsContainer.data( 'active-field-class', fieldClass );
		$fieldSettingsContainer.addClass( fieldClass );
	}

	ShowSettings( field );
}

function ShowSettings( element ) {
	if ( element.id === 'gform_last_page_settings' ) {
		//hide field and form pagination setting fields
		jQuery( '.field_setting' ).hide();
		jQuery( '.pagination_setting' ).hide();
		// Show last pagination setting fields
		jQuery( '.last_pagination_setting' ).show();
		var label = jQuery( '#gform_last_page_settings' ).data( 'title' );
		var description = jQuery( '#gform_last_page_settings' ).data( 'description' );
		var icon_classes = 'button-icon dashicons-media-text';
	} else if ( element.id === 'gform_pagination' ) {
		//hide field and last pagination setting fields
		fieldObject = typeof fieldObject !== 'undefined' ? fieldObject : GetFirstField();
		jQuery( '.field_setting' ).hide();
		jQuery( '.last_pagination_setting' ).hide();
		// Show form pagination setting fields
		jQuery( '.pagination_setting' ).show();
		jQuery("#gfield_post_category_initial_item_container").hide();
		jQuery("#gfield_min_strength_container").hide();
		InitPaginationOptions();
		var label = jQuery( '#gform_pagination' ).data( 'title' );
		var description = jQuery( '#gform_pagination' ).data( 'description' );
		var icon_classes = 'button-icon dashicons-media-text';
	} else if ( element.id === 'field_submit' ) {
		// Hide form pagination and last pagination setting fields
		jQuery( '.pagination_setting' ).hide();
		jQuery( '.last_pagination_setting' ).hide();
		// Load and show field setting fields
		LoadFieldSettings();
		fieldObject = GetSubmitField();
		var label = gf_vars.button;
		var description = gf_vars.buttonDescription;
		var icon_classes = 'gform-icon gform-icon--smart-button';
	} else {
		// Hide form pagination and last pagination setting fields
		jQuery( '.pagination_setting' ).hide();
		jQuery( '.last_pagination_setting' ).hide();
		// Load and show field setting fields
		LoadFieldSettings();
		fieldObject = GetSelectedField();
		var field_button = jQuery( '#add_fields button[data-type='+fieldObject.type+']' );
		var label = field_button.find( '.button-text' ).text();
		var description = field_button.data( 'description' );
		// If we have a custom icon img, get it
		var $button_icon = field_button.find( '.button-icon' );
		var icon_img = $button_icon.find( 'img' );
		var icon_classes = $button_icon.children().attr( 'class' );
	}
	// Show field icon and description in sidebar
	jQuery( '#nothing_selected' ).hide();
	jQuery( '#sidebar_field_label' )
		.removeClass( 'no-id' )
		.text( label )
		.attr( 'data-fieldId-label', gf_vars.idString )
		.attr( 'data-fieldId', fieldObject.id );
	if( 'submit' === fieldObject.type ) {
		jQuery( '#sidebar_field_label' ).addClass( 'no-id' );
	}
	jQuery( '#sidebar_field_text' ).text( description );
	// Reset icon classes
	jQuery( ' #sidebar_field_icon' ).attr( 'class', '' );
	jQuery( ' #sidebar_field_icon img' ).remove();
	if ( icon_img && icon_img.length ) {
		jQuery( '#sidebar_field_icon' ).append( '<img src="' + icon_img.attr( 'src' ) + '" />' );
	} else {
		// Get dashicon classes from button
		jQuery( '#sidebar_field_icon' ).addClass( icon_classes );
	}

	// Hide tabs that has no settings
	jQuery( '.panel-block-tabs__body--settings' ).each( function( index, tab ) {
		var tabId = jQuery( tab ).attr( 'id' );
		var visibleElements = jQuery( '#' + tabId + ' > li' ).filter( function() {
			return jQuery( this ).css( 'display' ) !== 'none';
		} );
		if ( visibleElements.length === 0 ) {
			jQuery( '#' + tabId + '_toggle' ).hide();
			jQuery( '#' + tabId ).hide();
		} else {
			jQuery( '#' + tabId + '_toggle' ).show();
		}
	} );

	jQuery('#sidebar_field_info').removeClass('panel-block--hidden');
	jQuery('#sidebar_field_info').addClass('panel-block--flex');
	jQuery('.field_settings').show();
	// Show field settings tab
	jQuery('.sidebar').tabs( 'option', 'active', 1 );

	var visibleChoicesSettings = gform.tools
		.getNodes( '[data-js="choices-ui-content"] > li', true, document, true )
		.filter( function( element ) {
			return window.getComputedStyle( element ).getPropertyValue( 'display' ) !== 'none';
		} );

	if ( ! visibleChoicesSettings.length ) {
		gform.tools.trigger( 'gform/flyout/close-all' );
	}

	gform.tools.trigger( 'gform/form_editor/setting_selected', document, false, element );
}

function TogglePercentageStyle( isInit ){

    if(jQuery("#percentage_style").val() == 'custom'){
        jQuery('.percentage_custom_container').show();
    }
    else{
        jQuery('.percentage_custom_container').hide();
    }
}

function TogglePercentageConfirmationText( isInit ){

    if(jQuery("#percentage_confirmation_display").is(":checked")){
        jQuery('.percentage_confirmation_page_name_setting').show();
    }
    else{
        jQuery('.percentage_confirmation_page_name_setting').hide();
    }
}

function CustomFieldExists(name){
    if(!name)
        return true;

    var options = jQuery("#field_custom_field_name_select option");
    for(var i=0; i<options.length; i++)
    {
        if(options[i].value == name)
            return true;
    }
    return false;
}

function IsStandardMask(value){
    if(!value)
        return true;

    var options = jQuery("#field_mask_select option");
    for(var i=0; i<options.length; i++)
    {
        if(options[i].value == value)
            return true;
    }
    return false;
}

function LoadFieldChoices(field){
    //loading ui
    jQuery('#field_choice_values_enabled').prop("checked", field.enableChoiceValue ? true : false);
    ToggleChoiceValue();
    var container_name = GetInputType(field) == "list" ? "field_columns" : "field_choices";
    jQuery("#" + container_name).html(GetFieldChoices(field));

    //loading bulk input
    LoadBulkChoices(field);

    jQuery(document).trigger('gform_load_field_choices', [field]);

    gform.doAction('gform_load_field_choices', [field]);
}

function LoadInputChoices($ul, input){

    //loading ui
    var $container = $ul.parent();
    $container.find('.field_input_choice_values_enabled').prop("checked", input.enableChoiceValue ? true : false);
    ToggleInputChoiceValue($container, input.enableChoiceValue);

    jQuery($ul).html(GetInputChoices(input));
}

function LoadBulkChoices(field){
    LoadCustomChoices();

    if(!field.choices)
        return;

    var choices = new Array();
    var choice;

    for(var i=0; i<field.choices.length; i++){
        choice = field.choices[i].text == field.choices[i].value ? field.choices[i].text : field.choices[i].text + "|" + field.choices[i].value;

        if(field.enablePrice && field.choices[i]["price"] != "")
            choice += "|:" + field.choices[i]["price"];

	    /**
	     * Filter each individual choice as it is loaded.
	     *
	     * This filter is generally used in combination with gform_insert_bulk_choices_choice, and is useful
	     * for generating unique text patterns for adding arbitrary data to a choice.
	     *
	     * @since 2.5
	     *
	     * @param {string} choice           The string representing the current choice as a text pattern.
	     * @param {Choice} field.choices[i] The Choice object representing this particular Choice data.
	     * @param {object} field            The current field being evaluated.
	     *
	     * @return {string} The updated text pattern, e.g. Label|Value|Meta|Other
	     */
	    choice = gform.applyFilters( 'gform_load_bulk_choices_choice', choice, field.choices[i], field );

        choices.push(choice);
    }

	/**
		 * Filter bulk loaded choices *after* they've been formatted for the bulk UI.
	 *
	 * This filter is useful if you would like to format the choices to provide additional parameters for choice-based settings.
	 *
	 * @since 2.3
	 *
	 * @param array bulkChoices The formatted choices.
	 * @param array choices     The choice objects from the current field.
	 */
	choices = gform.applyFilters( 'gform_choices_post_bulk_load', choices, field.choices );

    jQuery("#gfield_bulk_add_input").val(choices.join("\n"));
}

function DisplayCustomMessage(message){

    jQuery("#bulk_custom_message").html(message).slideDown();

    //slide up after 2 seconds
    setTimeout(
        function(){
            jQuery("#bulk_custom_message").slideUp();
        }, 2000
        );
}

function LoadCustomChoices(){

    jQuery(".choice_section_header, .bulk_custom_choice").remove();

    if(!IsEmpty(gform_custom_choices)){
        var str = "<li class='choice_section_header'>" + gf_vars.customChoices + "</li>";
        for(key in gform_custom_choices){

            if(!gform_custom_choices.hasOwnProperty(key))
                continue;

			var selectChoiceAction = 'SelectCustomChoice( jQuery(this).data("key") );';

			str += "<li class='bulk_custom_choice'><a href='javascript:void(0);' data-key='" + escapeAttr( key ) + "' onclick='" + selectChoiceAction + "' onkeypress='" + selectChoiceAction + "' class='bulk-choice bulk_custom_choice'>" + escapeHtml( key ) + "</a></li>";
        }
        str += "<li class='choice_section_header'>" + gf_vars.predefinedChoices + "</li>";
        jQuery("#bulk_items").prepend(str);
    }
}

function SelectCustomChoice( name ){

    jQuery("#gfield_bulk_add_input").val(gform_custom_choices[name].join("\n"));
    gform_selected_custom_choice = name;
    InitBulkCustomPanel();
}

function SelectPredefinedChoice(name){
    jQuery('#gfield_bulk_add_input').val(gform_predefined_choices[name].join('\n'));
    gform_selected_custom_choice = "";
    InitBulkCustomPanel();
}

function InsertBulkChoices(choices){
    field = GetSelectedField();
    field.choices = new Array();

    var enableValue = false;
    for(var i=0; i<choices.length; i++){
        text_price = choices[i].split("|:");

        text_value = text_price[0];
        price = "";
        if(text_price.length > 1){
            var currency = GetCurrentCurrency();
            price = currency.toMoney(text_price[1]);
        }

        text_value = text_value.split("|");

        if(text_value.length > 1)
            enableValue = true;

	    choice = new Choice(jQuery.trim(text_value[0]), jQuery.trim(text_value[text_value.length -1]), jQuery.trim(price));

	    /**
	     * Filter each individual Choice object as it is inserted into the UI.
	     *
	     * This filter is generally used in combination with gform_load_bulk_choices_choice, and is useful
	     * for parsing a unique text pattern (e.g., Label|Value|Other) and adding the additional data to
	     * the resulting Choice object.
	     *
	     * @since 2.5
	     *
	     * @param {Choice} choice        The Choice object representing this particular Choice data.
	     * @param {string} choice_string The string representing the current choice as a text pattern.
	     * @param {object} field         The current field being evaluated.
	     *
	     * @return {Choice} The updated Choice object containing any additional data needed.
	     */
	    choice = gform.applyFilters( 'gform_insert_bulk_choices_choice', choice, choices[i], field );

	    field.choices.push( choice );
    }

	/**
	 * Fires after bulk choices have been added to the field object and before the UI has been re-rendered.
	 *
	 * This action is useful if you need to alter other field settings based on the choices.
	 *
	 * @since 2.3
	 *
	 * @param array field The currently selected field object.
	 */
	gform.doAction( 'gform_bulk_insert_choices', field );

    if(enableValue){
        field["enableChoiceValue"] = true;
        jQuery('#field_choice_values_enabled').prop("checked", true);
        ToggleChoiceValue();
    }

    LoadFieldChoices(field);
    UpdateFieldChoices( GetInputType( field ) );
}

function InitBulkCustomPanel(){
    if(gform_selected_custom_choice.length == 0){
        CloseCustomChoicesPanel();
    }
    else{
        LoadCustomChoicesPanel();
    }
}

function LoadCustomChoicesPanel(isNew, speed){
    if(isNew){
        jQuery("#custom_choice_name").val("");
        jQuery("#bulk_save_button").html(gf_vars.save);
        jQuery("#bulk_cancel_link").show();
        jQuery("#bulk_delete_link").hide();
    }
    else{
        jQuery("#custom_choice_name").val(gform_selected_custom_choice);
        jQuery("#bulk_save_button").html(gf_vars.update);
        jQuery("#bulk_cancel_link").hide();
        jQuery("#bulk_delete_link").show();
    }

    jQuery("#bulk_save_as").hide();
    jQuery("#bulk_custom_edit").show();
}

function CloseCustomChoicesPanel(){

    jQuery("#bulk_save_as").show();
    jQuery("#bulk_custom_edit").hide();
}

function IsEmpty(array){
    var key;
    for (key in array) {
        if (array.hasOwnProperty(key))
            return false;
    }
    return true;
}

function SetFieldChoice(inputType, index){

    var text = jQuery("#" + inputType + "_choice_text_" + index).val();
    var value = jQuery("#" + inputType + "_choice_value_" + index).val();
    var price = jQuery("#" + inputType + "_choice_price_" + index).val();

    field = GetSelectedField();

    field.choices[index].text = text;
    field.choices[index].value = field.enableChoiceValue ? value : text;

    if(field.enablePrice){
        var currency = GetCurrentCurrency();
        var price = currency.toMoney(price);
        if(!price)
            price = "";

        field.choices[index]["price"] = price;
    }

    //set field selections
    jQuery("#field_choices :radio, #field_choices :checkbox").each(function(index){
        field.choices[index].isSelected = this.checked;
    });

    LoadBulkChoices(field);

    UpdateFieldChoices(GetInputType(field));
}

function SetInputChoice(inputId, index, value, text){
    var field = GetSelectedField();
    var input = GetInput(field, inputId);
    inputId = inputId.toString().replace('.', '_');

    input.choices[index].text = text;
    input.choices[index].value = input.enableChoiceValue ? value : text;

    //set field selections
    jQuery(".field-input-choice-" + inputId + ":radio, .field-input-choice-" + inputId + ":checkbox").each(function(index){
        input.choices[index].isSelected = this.checked;
    });

    UpdateInputChoices(input);
}

function UpdateFieldChoices(fieldType){
	var choices = '';
	var selector = '';
	var inputContainer = ( "1" === gf_legacy.is_legacy ) ? 'li' : 'div';
	var inputContainerClass;

	if(fieldType == "checkbox")
		field.inputs = new Array();

	var skip = 0;

	// Multiselect is functionally just a select input with a different attribute; adjust the type here.
	if ( fieldType === 'multiselect' ) {
		fieldType = 'select';
	}

	switch( fieldType ){
		case "select" :
			for(var i=0; i<field.choices.length; i++)
			{
				selected = field.choices[i].isSelected ? "selected='selected'" : "";
				var choiceValue = field.choices[i].value ? field.choices[i].value : field.choices[i].text;
				choices += "<option value='" + choiceValue.replace(/'/g, "&#039;") + "' " + selected + ">" + field.choices[i].text + "</option>";
			}
			break;

		case "checkbox" :
			for(var i=0; i<field.choices.length; i++)
			{
				//Skipping ids that are multiple of ten to avoid conflicts with other fields (i.e. 5.1 and 5.10)
				if((i + 1 + skip) % 10 == 0){
					skip++;
				}
				var field_number = field.id + '.' + (i + 1 + skip);
				field.inputs.push(new Input(field_number, field.choices[i].text));

				var id = 'choice_' + field.id + '_' + (i + 1);
				inputContainerClass = "gchoice g" + id;
				checked = field.choices[i].isSelected ? "checked" : "";
				if(i < 5)
					choices += "<" + inputContainer + " class='" + inputContainerClass + "'><input name='input_" + field.inputs[i].id + "' type='" + fieldType + "' " + checked + " value='" + field.choices[i].value + "' id='" + id +"' disabled='disabled'><label for='" + id + "'>" + field.choices[i].text + "</label></" + inputContainer + ">";
			}
			if(field.choices.length > 5)
				choices += "<" + inputContainer + " class='gchoice_total'>" + gf_vars["editToViewAll"].replace("%d", field.choices.length) + "</" + inputContainer + ">";

			if ( field.enableSelectAll ) {
				choices += '<button type="button" id="button_' + id + '_select_all" disabled="disabled">' + gf_vars["selectAll"] + '</button>';
			}
			break;

		case "radio" :
			for(var i=0; i<field.choices.length; i++)
			{
				var id = 'choice_' + field.id + '_' + (i + 1);
				inputContainerClass = "gchoice g" + id;
				checked = field.choices[i].isSelected ? "checked" : "";
				if(i < 5)
					choices += "<" + inputContainer + " class='" + inputContainerClass + "'><input name='input_" + field.id + "' type='" + fieldType + "' " + checked + " value='" + field.choices[i].value + "' id='" + id +"' disabled='disabled'><label for='" + id + "'>" + field.choices[i].text + "</label></" + inputContainer + ">";
			}

			choices += field.enableOtherChoice ? "<" + inputContainer + "><input type='" + fieldType + "' " + checked + " id='" + id +"' disabled='disabled'><input type='text' value='" + gf_vars.otherChoiceValue + "'  disabled='disabled' /></" + inputContainer + ">" : "";

			if(field.choices.length > 5)
				choices += "<" + inputContainer + " class='gchoice_total'>" + gf_vars["editToViewAll"].replace("%d", field.choices.length) + "</" + inputContainer + ">";

			break;

		case "list" :
			RefreshSelectedFieldPreview();
			break;
	}

	selector = '.gfield_' + fieldType;

	jQuery(".field_selected " + selector).html(choices);
}

function UpdateInputChoices(input){
    var choices = '';

    for(var i=0; i<input.choices.length; i++) {
        var selected = input.choices[i].isSelected ? "selected='selected'" : "";
        var choiceValue = input.choices[i].value ? input.choices[i].value : input.choices[i].text;
        choices += "<option value='" + choiceValue.replace(/'/g, "&#039;") + "' " + selected + ">" + input.choices[i].text + "</option>";
    }
    var inputId = input.id.toString().replace('.', '_');

    jQuery(".field_selected #input_" + inputId).html(choices);
}

function InsertFieldChoice( index ) {
	field = GetSelectedField();

	var inputType = GetInputType( field );
	var text = "";
	var value = "";
	var price = field[ "enablePrice" ] ? "0.00" : "";

	if ( inputType === 'list' ) {
		text = window.gf_vars.column + " " + (index + 1);
		value = window.gf_vars.column + " " + (index + 1);
	}

	var newChoice = new Choice( text, value, price );

	if ( window[ "gform_new_choice_" + field.type ] ) {
		newChoice = window[ "gform_new_choice_" + field.type ]( field, newChoice );
	}

	if ( typeof field.choices !== 'object' ) {
		field.choices = [];
	}

	field.choices.splice( index, 0, newChoice );

	LoadFieldChoices( field );
	UpdateFieldChoices( inputType );
}

function InsertInputChoice($ul, inputId, index){
    var field = GetSelectedField();
    var input = GetInput(field, inputId);

    var new_choice = new Choice("", "");

    input.choices.splice(index, 0, new_choice);

    LoadInputChoices($ul, input);
    UpdateInputChoices(input);
}

function DeleteFieldChoice(index){

    field = GetSelectedField();
    var value = jQuery('#' + GetInputType(field) + '_choice_value_' + index).val();

    if( HasConditionalLogicDependency(field.id, value) ) {
        if(!confirm(gf_vars.conditionalLogicDependencyChoice))
            return;
    }

    field.choices.splice(index, 1);
    LoadFieldChoices(field);
    UpdateFieldChoices(GetInputType(field));
}

function DeleteInputChoice($ul, inputId, index){
    var field = GetSelectedField();
    var input = GetInput(field, inputId);

    input.choices.splice(index, 1);

    LoadInputChoices($ul, input);
    UpdateInputChoices(input);
}

function MoveFieldChoice(fromIndex, toIndex){
    field = GetSelectedField();
    var choice = field.choices[fromIndex];

    //deleting from old position
    field.choices.splice(fromIndex, 1);

    //inserting into new position
    field.choices.splice(toIndex, 0, choice);

    LoadFieldChoices(field);
    UpdateFieldChoices(GetInputType(field));
}

function MoveInputChoice($ul, inputId, fromIndex, toIndex){
    var field = GetSelectedField();
    var input = GetInput(field, inputId);
    var choice = input.choices[fromIndex];

    //deleting from old position
    input.choices.splice(fromIndex, 1);

    //inserting into new position
    input.choices.splice(toIndex, 0, choice);

    LoadInputChoices($ul, input);
    UpdateInputChoices(input);
}

function GetFieldType(fieldId){
    return fieldId.substr(0, fieldId.lastIndexOf("_"));
}

function GetSelectedField() {
	var $field = jQuery( '.field_selected' );
	if( $field.length <= 0 ) {
		return false;
	}
    var id = $field[0].id.substr( 6 );

    return GetFieldById( id );
}

function SetPasswordProperty(isChecked){
    SetFieldProperty("enablePasswordInput", isChecked);
}

function ToggleDateCalendar( isInit ){

    var dateType = jQuery("#field_date_input_type").val();
    if(dateType == "datefield" || dateType == "datedropdown"){
        jQuery("#date_picker_container").hide();
        SetCalendarIconType("none");
    }
    else{
        jQuery("#date_picker_container").show();
    }
}

function ToggleCalendarIconUrl( isInit ){

    if(jQuery("#gsetting_icon_custom").is(":checked")){
        jQuery("#gfield_icon_url_container").show();
    }
    else{
        jQuery("#gfield_icon_url_container").hide();
        jQuery("#gfield_calendar_icon_url").val("");
        SetFieldProperty('calendarIconUrl', '');
    }
}

function SetTimeFormat(format){
    SetFieldProperty('timeFormat', format);
    LoadTimeInputs();
}

function LoadTimeInputs(){
    var field = GetSelectedField();
    if(field.type != 'time' && field.inputType != 'time'){
        return;
    }
    var format = jQuery("#field_time_format").val();


    if(format == "24"){
        jQuery('#input_default_value_row_input_' + field.id +'_3').hide();
        jQuery(".field_selected .gfield_time_ampm").hide();
    } else {
        jQuery('#input_default_value_row_input_' + field.id +'_3').show();
        jQuery(".field_selected .gfield_time_ampm").show();
    }
	jQuery('#input_placeholder_row_input_' + field.id +'_3').hide(); // No support for placeholder.
	// AM/PM Sub label is hidden in the time field class after `gform_post_load_field_settings` is fired.
}

/**
 * Set date format for a date field.
 *
 * @since unknown
 * @since 2.5     Updated for the layout editor.
 *
 * @param format
 * @constructor
 */
function SetDateFormat( format ) {
	SetFieldProperty( 'dateFormat', format );

	var field = GetSelectedField();
	if ( field.dateType === 'datepicker' ) {
		var formatLabel = jQuery( '#field_date_format option:selected' ).text();

		if ( field.placeholder === '' ) {
			jQuery( '.field_selected input[name="ginput_datepicker"]' )
				.attr( 'placeholder', formatLabel );
		}
	}

	LoadDateInputs();
}

function LoadDateInputs(){
    var type = jQuery("#field_date_input_type").val();
    var format = jQuery("#field_date_format").val();

    //setting up field positions
    var position = format ? format.substr(0,3) : "mdy";

    if(type == "datefield"){
        switch(position){
            case "ymd" :
                jQuery(".field_selected #gfield_input_date_month").remove().insertBefore(".field_selected #gfield_input_date_day");
                jQuery(".field_selected #gfield_input_date_year").remove().insertBefore(".field_selected #gfield_input_date_month");
            break;

            case "mdy" :
                jQuery(".field_selected #gfield_input_date_day").remove().insertBefore(".field_selected #gfield_input_date_year");
                jQuery(".field_selected #gfield_input_date_month").remove().insertBefore(".field_selected #gfield_input_date_day");
            break;

            case "dmy" :
                jQuery(".field_selected #gfield_input_date_month").remove().insertBefore(".field_selected #gfield_input_date_year");
                jQuery(".field_selected #gfield_input_date_day").remove().insertBefore(".field_selected #gfield_input_date_month");
            break;
        }

        jQuery(".field_selected [id^='gfield_input_date']").show();
        jQuery(".field_selected [id^='gfield_dropdown_date']").hide();
        jQuery(".field_selected #gfield_input_datepicker").hide();
        jQuery(".field_selected #gfield_input_datepicker_icon").hide();
    }
    else if(type == "datedropdown"){

        switch(position){
            case "ymd" :
                jQuery(".field_selected #gfield_dropdown_date_month").remove().insertBefore(".field_selected #gfield_dropdown_date_day");
                jQuery(".field_selected #gfield_dropdown_date_year").remove().insertBefore(".field_selected #gfield_dropdown_date_month");
            break;

            case "mdy" :
                jQuery(".field_selected #gfield_dropdown_date_day").remove().insertBefore(".field_selected #gfield_dropdown_date_year");
                jQuery(".field_selected #gfield_dropdown_date_month").remove().insertBefore(".field_selected #gfield_dropdown_date_day");
            break;

            case "dmy" :
                jQuery(".field_selected #gfield_dropdown_date_month").remove().insertBefore(".field_selected #gfield_dropdown_date_year");
                jQuery(".field_selected #gfield_dropdown_date_day").remove().insertBefore(".field_selected #gfield_dropdown_date_month");
            break;
        }

        jQuery(".field_selected [id^='gfield_dropdown_date']").css("display", "inline");
        jQuery(".field_selected [id^='gfield_input_date']").hide();
        jQuery(".field_selected #gfield_input_datepicker").hide();
        jQuery(".field_selected #gfield_input_datepicker_icon").hide();
    }
    else{
        jQuery(".field_selected [id^='gfield_input_date']").hide();
        jQuery(".field_selected [id^='gfield_dropdown_date']").hide();
        jQuery(".field_selected #gfield_input_datepicker").show();

        //Displaying or hiding the calendar icon
        if(jQuery("#gsetting_icon_calendar").is(":checked"))
            jQuery(".field_selected #gfield_input_datepicker_icon").show();
        else
            jQuery(".field_selected #gfield_input_datepicker_icon").hide();
    }


}

function SetCalendarIconType(iconType, isInit){
    field = GetSelectedField();
    if(GetInputType(field) != "date")
        return;

    if(iconType == undefined)
        iconType = "none";

    if(iconType == "none")
        jQuery("#gsetting_icon_none").prop("checked", true);
    else if(iconType == "calendar")
        jQuery("#gsetting_icon_calendar").prop("checked", true);
    else if(iconType == "custom")
        jQuery("#gsetting_icon_custom").prop("checked", true);

    SetFieldProperty('calendarIconType', iconType);
    ToggleCalendarIconUrl( isInit );
    LoadDateInputs();
}

function SetDateInputType(type){
    field = GetSelectedField();
    if(GetInputType(field) != "date")
        return;

	if ( type === 'datepicker' ) {
		SetFieldAccessibilityWarning( 'date_input_type_setting', 'above' );
	} else {
		resetAllFieldAccessibilityWarnings();
	}

    field.dateType = type;
    field.inputs = GetDateFieldInputs(field);

    CreateDefaultValuesUI(field);
    CreatePlaceholdersUI(field);
    CreateInputLabelsUI(field);
    ToggleDateSettings(field);

    ResetDefaultInputValues(field);

    ToggleDateCalendar();
    LoadDateInputs();
}

function SetPostImageMeta(){
    var displayAlt = jQuery('#gfield_display_alt').is(":checked");
    var displayTitle = jQuery('#gfield_display_title').is(":checked");
    var displayCaption = jQuery('#gfield_display_caption').is(":checked");
    var displayDescription = jQuery('#gfield_display_description').is(":checked");
    var displayLabel = (displayAlt || displayTitle || displayCaption || displayDescription);

    //setting property
    SetFieldProperty('displayAlt', displayAlt);
    SetFieldProperty('displayTitle', displayTitle);
    SetFieldProperty('displayCaption', displayCaption);
    SetFieldProperty('displayDescription', displayDescription);

    //updating UI
    jQuery('.field_selected .ginput_post_image_alt').css("display", displayAlt ? "block" : "none");
    jQuery('.field_selected .ginput_post_image_title').css("display", displayTitle ? "block" : "none");
    jQuery('.field_selected .ginput_post_image_caption').css("display", displayCaption ? "block" : "none");
    jQuery('.field_selected .ginput_post_image_description').css("display", displayDescription ? "block" : "none");
    jQuery('.field_selected .ginput_post_image_file').css("display", displayLabel ? "block" : "none");
}

function SetFeaturedImage() {

    var isChecked = jQuery('#gfield_featured_image').is(':checked');

    if(isChecked) {

        for(i in form.fields) {

            if(!form.fields.hasOwnProperty(i))
                continue;

            form.fields[i].postFeaturedImage = false;
        }

        SetFieldProperty('postFeaturedImage', true);
    }
    else{
        SetFieldProperty('postFeaturedImage', false);
    }
}

function SetFieldProperty(name, value){
    if(value == undefined)
        value = "";

    GetSelectedField()[name] = value;
}

function SetInputName(value, inputId){
    var field = GetSelectedField();

    if(value)
        value = value.trim();

    if(!inputId){
        field["inputName"] = value;
    }
    else{
        for(var i=0; i<field["inputs"].length; i++){
            if(field["inputs"][i]["id"] == inputId){
                field["inputs"][i]["name"] = value;
                return;
            }
        }
    }
}

function SetInputDefaultValue(value, inputId){
    var field = GetSelectedField(), $select, elementId, ele;

    if(value)
        value = value.trim();

    for(var i=0; i<field["inputs"].length; i++){
        if(field["inputs"][i]["id"] == inputId){
            field["inputs"][i]["defaultValue"] = value;
            jQuery('[name="input_' + inputId + '"], #input_' + inputId.toString().replace('.', '_')).each(function(){
                var type = this.nodeName;
                if( type == 'INPUT'){
                    jQuery(this).val(value);
                } else {
                    $select = jQuery(this);
                    value = value.toLowerCase();
                    $select
                        .val('')
                        .children()
                        .each(function () {
                            if(this.value.toLowerCase() == value){
                                $select.val(this.value);
                                return false;
                            }
                        });
                }
            });
            return;
        }
    }
}

function SetInputPlaceholder(value, inputId){
    var field = GetSelectedField(), ele, elementId;

    if(value)
        value = value.trim();

    for(var i=0; i<field["inputs"].length; i++){
        if(field["inputs"][i]["id"] == inputId){
            field["inputs"][i]["placeholder"] = value;
            jQuery('[name="input_' + inputId + '"], #input_' + inputId.toString().replace('.', '_')).each(function(){
                var type = this.nodeName;
                if( type == 'INPUT'){
                    jQuery(this).prop("placeholder", value);
                } else if (type == 'SELECT'){
                    jQuery(this).find('option[value=""]').text(value);
                }
            });
            return;
        }
    }
}

function ResetInputPlaceholders(field){
    if(!field){
        field = GetSelectedField()
    }
    if(!field.inputs){
        return
    }
    jQuery(field.inputs).each(function(){
        var placeholder = typeof this.placeholder != 'undefined' ? this.placeholder : '';
        SetInputPlaceholder(placeholder, this.id);
    })
}

function SetInputAutocomplete( value, inputId ){
	var field = GetSelectedField(), ele, elementId;

	if( value )
		value = value.trim();

	for( var i=0; i<field["inputs"].length; i++ ){
		if( field["inputs"][i]["id"] == inputId ) {
			field["inputs"][i]["autocompleteAttribute"] = value;
			jQuery( '[name="input_' + inputId + '"], #input_' + inputId.toString().replace( '.', '_' ) ).each( function() {
				field["inputs"][i]["autocompleteAttribute"] = value;
			} );
			return;
		}
	}
}

function ResetDefaultInputValues(field){
    if(!field){
        field = GetSelectedField()
    }
    if(!field.inputs){
        return
    }
    jQuery(field.inputs).each(function(){
        var defaultValue = typeof this.defaultValue != 'undefined' ? this.defaultValue : '';
        SetInputDefaultValue(defaultValue, this.id);
    })
}

/**
 * Set the custom label of a input.
 *
 * @since unknown
 * @since 2.5     Updated to handle screen-reader-text for date and time inputs.
 *
 * @param {string} value     The value of the custom label.
 * @param {string} [inputId] The input ID.
 */
function SetInputCustomLabel( value, inputId ) {
	var field = GetSelectedField(), elementID, label, input;

	if ( value )
		value = value.trim();

	for ( var i = 0; i < field[ "inputs" ].length; i++ ) {
		input = field[ "inputs" ][ i ];
		if ( input.id == inputId ) {
			if ( value == '' ) {
				delete input.customLabel;
				label = typeof input.defaultLabel != 'undefined' ? input.defaultLabel : input.label;
			} else {
				input.customLabel = value;
				label = value;
			}

			elementID = 'input_' + field.inputs[ i ].id;
			elementID = elementID.replace( '.', '_' );
			elementID = '.ginput_container label[for=' + elementID + "]";
			jQuery( elementID ).text( label );

			// Toggle the screen-reader-text class based on if the customLabel is set.
			if ( field.type === 'date' || field.type === 'time' ) {
				jQuery( elementID ).toggleClass( 'screen-reader-text', ! input.hasOwnProperty( 'customLabel' ) );
			}

			return;
		}
	}
}

function SetInputHidden(isHidden, inputId){
    var field = GetSelectedField();

    for(var i=0; i<field["inputs"].length; i++){
        if(field["inputs"][i]["id"] == inputId){
            field["inputs"][i]["isHidden"] = isHidden;
            inputId = inputId.toString().replace('.','_');
            jQuery("#input_" + inputId + "_container").toggle(!isHidden);
            return;
        }
    }
}


function SetSelectedCategories(){
    var field = GetSelectedField();
    field["choices"] = new Array();

    jQuery(".gfield_category_checkbox").each(function(){
        if(this.checked)
            field["choices"].push(new Choice(this.name, this.value));
    });

    field["choices"].sort(function(a, b){return ( a["text"].toLowerCase() > b["text"].toLowerCase() );});
}

function SetFieldLabel(label){
    var requiredElement = jQuery(".field_selected .gfield_required")[0];
    jQuery(".field_selected .gfield_label, .field_selected .gsection_title").text(label).append(requiredElement);
    SetFieldProperty("label", label);
}

/**
 * Set the Aria Label for a field in the editor.
 *
 * @since 2.5.
 *
 * @param {string} label The field label
 */
function SetAriaLabel(label){
	var fieldId   = jQuery( ".field_selected" )[0].id.split( '_' )[1];
	var field     = GetFieldById( fieldId );
	var ariaLabel = window.gf_vars.fieldLabelAriaLabel.replace('{field_label}', label).replace('{field_type}', field.type);
	jQuery( ".field_selected .gfield-edit" ).attr( 'aria-label', ariaLabel );
}

function SetCaptchaTheme(theme, thumbnailUrl){
    jQuery(".field_selected .gfield_captcha").attr("src", thumbnailUrl);
    SetFieldProperty("captchaTheme", theme);
}


function SetCaptchaSize(size){
    var type = jQuery("#field_captcha_type").val();
    SetFieldProperty("simpleCaptchaSize", size);
    RedrawCaptcha();
    jQuery(".field_selected .gfield_captcha_input_container").removeClass(type + "_small").removeClass(type + "_medium").removeClass(type + "_large").addClass(type + "_" + size);
}

function SetCaptchaFontColor(color){
    SetFieldProperty("simpleCaptchaFontColor", color);
    RedrawCaptcha();
}

function SetCaptchaBackgroundColor(color){
    SetFieldProperty("simpleCaptchaBackgroundColor", color);
    RedrawCaptcha();
}

function RedrawCaptcha(){
    var captchaType = jQuery("#field_captcha_type").val();

    if(captchaType == "math"){
        url_1 = GetCaptchaUrl(1);
        url_2 = GetCaptchaUrl(2);
        url_3 = GetCaptchaUrl(3);
        jQuery(".field_selected .gfield_captcha:eq(0)").attr("src", url_1);
        jQuery(".field_selected .gfield_captcha:eq(1)").attr("src", url_2);
        jQuery(".field_selected .gfield_captcha:eq(2)").attr("src", url_3);
    }
    else{
        url = GetCaptchaUrl();
        jQuery(".field_selected .gfield_captcha").attr("src", url);
    }
}

function SetFieldEnhancedUI( checked ) {
	SetFieldProperty( 'enableEnhancedUI', checked ? 1 : 0 );

	if ( checked ) {
		SetFieldAccessibilityWarning( 'enable_enhanced_ui_setting', 'below' );
	} else {
		resetAllFieldAccessibilityWarnings();
	}
}

function SetFieldSize(size){
    jQuery(".field_selected .small, .field_selected .medium, .field_selected .large").removeClass("small").removeClass("medium").removeClass("large").addClass(size);
    SetFieldProperty("size", size);
}

function SetFieldLabelPlacement(labelPlacement){
    var labelPlacementClass = labelPlacement ? labelPlacement : form.labelPlacement;
    SetFieldProperty("labelPlacement", labelPlacement);
    jQuery(".field_selected").removeClass("top_label").removeClass("right_label").removeClass("left_label").removeClass("hidden_label").addClass(labelPlacementClass);

    if((field.labelPlacement == 'left_label' || field.labelPlacement == 'right_label' || (field.labelPlacement == '' && form.labelPlacement != 'top_label'))){
        jQuery('#field_description_placement').val('');
        SetFieldProperty("descriptionPlacement", '');
        jQuery('#field_description_placement_container').hide('slow');
    } else {
        jQuery('#field_description_placement_container').show('slow');
    }

	if ( field.labelPlacement == 'hidden_label' ) {
		SetFieldAccessibilityWarning( 'label_placement_setting', 'above' );
	} else {
		resetAllFieldAccessibilityWarnings();
	}

    SetFieldProperty("labelPlacement", labelPlacement);
	SetFieldRequired(field.isRequired);
    RefreshSelectedFieldPreview();
}

function SetFieldDescriptionPlacement(descriptionPlacement){
    var isDescriptionAbove = descriptionPlacement == 'above' || (descriptionPlacement == '' && form.descriptionPlacement == 'above)');
    SetFieldProperty("descriptionPlacement", descriptionPlacement);
    RefreshSelectedFieldPreview(function(){
        if(isDescriptionAbove){
            jQuery(".field_selected").addClass("description_above");
        } else {
            jQuery(".field_selected").removeClass("description_above");
        }
    });
}

function SetFieldSubLabelPlacement( subLabelPlacement ) {
	SetFieldProperty( "subLabelPlacement", subLabelPlacement );

	RefreshSelectedFieldPreview( function() {
		if ( "above" === subLabelPlacement ) {
			jQuery( ".field_selected" ).addClass( "field_sublabel_above" ).removeClass( "field_sublabel_below" );
		} else {
			jQuery( ".field_selected" ).addClass( "field_sublabel_below" ).removeClass( "field_sublabel_above" );
		}
	} );
}

function SetFieldVisibility( visibility, handleInputs, isInit ) {

    if (!isInit && visibility == 'administrative' && HasConditionalLogicDependency(field.id)) {
        if( ! confirm( gf_vars.conditionalLogicDependencyAdminOnly ) ) {
            return false;
        }
    }

    var isWhitelisted = false;
    for( var i = 0; i < gf_vars.visibilityOptions.length; i++ ) {
        if( gf_vars.visibilityOptions[i].value == visibility ) {
            isWhitelisted = true;
            break;
        }
    }

    if( ! isWhitelisted ) {
        visibility = 'visible';
    }

    SetFieldProperty( 'visibility', visibility );

    if( handleInputs ) {
        var $inputs = jQuery( 'input[name="field_visibility"]' );
        $inputs.prop( 'checked', false );
        $inputs.filter( '[value="' + visibility + '"]' ).prop( 'checked', true );
    }

}

function SetFieldDefaultValue(defaultValue){

    jQuery(".field_selected > div > input:visible, .field_selected > div > textarea:visible, .field_selected > div > select:visible").val(defaultValue);

    SetFieldProperty('defaultValue', defaultValue);
}

function SetFieldPlaceholder(placeholder){

	jQuery(".field_selected > div > input:visible, .field_selected > div > textarea:visible, .field_selected > div > select:visible").each(function(){
		var type = this.nodeName;
		var $this = jQuery(this);
		if(type == 'INPUT' || type == 'TEXTAREA'){
			jQuery(this).prop("placeholder", placeholder);
		} else if (type == 'SELECT'){
			var $option = $this.find('option[value=""]');
			if($option.length>0){
				if(placeholder.length > 0){
					$option.text(placeholder);
				} else {
					$option.remove();
				}

			} else {
				$this.prepend('<option value="">' + placeholder + '</option>');
				$this.val('');
			}
		}
	});

    SetFieldProperty('placeholder', placeholder);
}

function SetFieldDescription(description){
    if(description == undefined)
        description = "";

    SetFieldProperty('description', description);
}

function SetFieldCheckboxLabel(text){
    if(text == undefined)
        text = "";

    SetFieldProperty('checkboxLabel', text);
}

function SetPasswordStrength(isEnabled){
    if(isEnabled){
        jQuery(".field_selected .gfield_password_strength").show();
    }
    else{
        jQuery(".field_selected .gfield_password_strength").hide();

        //resetting min strength
        jQuery("#gfield_min_strength").val("");
        SetFieldProperty('minPasswordStrength', "");
    }

    SetFieldProperty('passwordStrengthEnabled', isEnabled);
}

function ToggleEmailSettings(field){
    var isConfirmEnabled = typeof field.emailConfirmEnabled != 'undefined' && field.emailConfirmEnabled == true;
    jQuery('.placeholder_setting').toggle(!isConfirmEnabled);
    jQuery('.default_value_setting').toggle(!isConfirmEnabled);
    jQuery('.sub_label_placement_setting').toggle(isConfirmEnabled);
    jQuery('.sub_labels_setting').toggle(isConfirmEnabled);
    jQuery('.default_input_values_setting').toggle(isConfirmEnabled);
    jQuery('.input_placeholders_setting').toggle(isConfirmEnabled);
}

function SetEmailConfirmation(isEnabled){
    var field = GetSelectedField();
    if(isEnabled){
        jQuery(".field_selected .ginput_single_email").hide();
        jQuery(".field_selected .ginput_confirm_email").show();
    }
    else{
        jQuery(".field_selected .ginput_confirm_email").hide();
        jQuery(".field_selected .ginput_single_email").show();
    }

    field['emailConfirmEnabled'] = isEnabled;
    field.inputs = GetEmailFieldInputs(field);
    CreateDefaultValuesUI(field);
    CreatePlaceholdersUI(field);
    CreateAutocompleteUI(field);
    CreateCustomizeInputsUI(field);
    CreateInputLabelsUI(field);


    ToggleEmailSettings(field);

}


function SetCardType(elem, value) {

    var cards = GetSelectedField()['creditCards'] ? GetSelectedField()['creditCards'] : new Array();

    if(jQuery(elem).is(':checked')) {

        if(jQuery.inArray(value, cards) == -1) {
            jQuery('.gform_card_icon_' + value).fadeIn();
            cards[cards.length] = value;
        }

    } else {

        var index = jQuery.inArray(value, cards);

        if(index != -1) {
            jQuery('.gform_card_icon_' + value).fadeOut();
            cards.splice(index, 1);
        }

    }

    SetFieldProperty('creditCards', cards);
}

function SetFieldRequired( isRequired ) {
	var required = gform_form_strings.requiredIndicator;
	var requiredSelector = '.field_selected .gfield_required';
	var appendRequired = false;

	if ( field.type === 'consent' ) {
		jQuery( requiredSelector ).remove();
		if ( isRequired ) {
			appendRequired = true;
		}
	} else if ( jQuery( requiredSelector ).length > 0 ) {
		if ( isRequired ) {
			jQuery( requiredSelector ).html( required );
		} else {
			jQuery( requiredSelector ).remove();
		}
	} else if ( isRequired ) {
		appendRequired = true;
	}

	if ( appendRequired ) {
		var labelSelector = field.type === 'consent' && field.labelPlacement === 'hidden_label' ? '.gfield_consent_label' : '.gfield_label';
		jQuery( '.field_selected ' + labelSelector ).append( '<span class="gfield_required">' + required + '</span>' );
	}

	SetFieldProperty( 'isRequired', isRequired );
}

function SetMaxLength(input) {

    var patt = GetMaxLengthPattern();
    var cleanValue = '';
    var characters = input.value.split('');

    for(i in characters) {

        if(!characters.hasOwnProperty(i))
            continue;

        if( !patt.test(characters[i]) )
            cleanValue += characters[i];
    }

    input.value = cleanValue;
    SetFieldProperty('maxLength', cleanValue);

}

function GetMaxLengthPattern() {
    return /[a-zA-Z\-!@#$%^&*();'":_+=<,>.~`?\/|\[\]\{\}\\]/;
}

/**
* Validate any keypress events based on a provided RegExp.
*
* Function retrieves the character code from the keypress event and tests it against provided pattern.
* Optionally specify 'matchPositive' argument to false in order to return true if the character is NOT
* in the provided pattern.
*
* @param event The JS keypress event.
* @param patt RegExp to test keypress character against.
* @param matchPositive Defaults to true. Whether to return true if the character is found or NOT found in the pattern.
*/
function ValidateKeyPress(event, patt, matchPositive) {

    var matchPositive = typeof matchPositive == 'undefined' ? true : matchPositive;
    var char = event['which'] ? event.which : event.keyCode;
    var isMatch = patt.test(String.fromCharCode(char));

    if(event.ctrlKey)
        return true;

    return matchPositive ? isMatch : !isMatch;
}

function IndexOf(ary, item){
    for(var i=0; i<ary.length; i++)
        if(ary[i] == item)
            return i;

    return -1;
}

function ToggleCalculationOptions(isEnabled, field) {

    if(isEnabled) {

        jQuery('#calculation_options').show();
        if(field.type != 'product')
            jQuery('li.range_setting').hide();

    } else {

        jQuery('#calculation_options').hide();
        if(field.type != 'product')
            jQuery('li.range_setting').show();

        SetFieldProperty('calculationFormula', '');
        SetFieldProperty('calculationRounding', '');

    }

    SetFieldProperty('enableCalculation', isEnabled);
}

function FormulaContentCallback() {
    SetFieldProperty('calculationFormula', jQuery('#field_calculation_formula').val().trim());
}

function SetupUnsavedChangesWarning() {
	// check if form is in legacy mode
	var legacyHtml = window.gf_legacy && window.gf_legacy.is_legacy === '1';
    // apply system changes to the form, unsaved notification should only apply for user-made changes
    UpdateFormObject();

    // store a json copy of original form to determine if user-made changes were made
    gforms_original_json = jQuery.toJSON(form);

    window.onbeforeunload = function(){
        UpdateFormObject();
	    var original = JSON.parse( JSON.stringify( JSON.parse( window.gforms_original_json ) ) );
	    var current = JSON.parse( JSON.stringify( window.form ) );
		if ( legacyHtml ) {
			original.fields.forEach( function( field, i ) {
				delete original.fields[ i ].layoutGroupId;
			} );
			current.fields.forEach( function( field, i ) {
				delete current.fields[ i ].layoutGroupId;
			} );
		}
        if ( JSON.stringify( original ) !== JSON.stringify( current ) && ! gf_vars.isFormTrash ) {
            return "You have unsaved changes.";
        }
    }

}

function ToggleRichTextEditor( isEnabled ) {

    var field    = GetSelectedField(),
        $input   = jQuery( '#input_' + field.id ),
        $preview = jQuery( '#input_' + field.id + '_rte_preview' );

    if( isEnabled ) {
        $input.hide();
        $preview.show();
    } else {
        $preview.hide();
        $input.show();
    }

    SetFieldProperty( 'useRichTextEditor', isEnabled );

}

function SetHTMLMargins( value ) {
    var field      = GetSelectedField(),
        $container = jQuery( '#field_' + field.id );

    $container.toggleClass( 'gfield_html_formatted' );
    SetFieldProperty('disableMargins', value );
}

function SetSubmitLocation( location ) {

	if( 'inline' === location ) {
		// Find the last group id.
		var lastGroup = jQuery( '#field_submit' ).prev().attr( 'data-groupid' );

		// Move the submit button to the group.
		jQuery( '#field_submit' ).setGroupId( lastGroup ).resizeGroup( lastGroup );

		// Remove any spacers from the group.
		jQuery( '#field_submit' ).next( '.spacer' ).remove();

		// Assign the correct position property.
		jQuery( '*[data-field-class="gform_editor_submit_container"]' ).data( 'field-position', 'inline' );
	} else {
		// Move the submit button out of the group and make it full-width.
		var groupID = jQuery( '#field_submit' ).attr( 'data-groupid' );
		jQuery( '#field_submit' )
			.removeAttr( 'data-groupid' )
			.addClass( 'gfield--width-full' )
			.setGridColumnSpan( 12 )
			.resizeGroup( groupID );

		// Assign the correct position property.
		jQuery( '*[data-field-class="gform_editor_submit_container"]' ).data( 'field-position', 'bottom' );
	}

}

function SetSubmitWidth( width ) {
	if( 'full' === width ) {
		jQuery( '#field_submit .gform-button' ).addClass( 'gform-button--width-full' );
	} else {
		jQuery( '#field_submit .gform-button' ).removeClass( 'gform-button--width-full' );
	}
}

function ToggleSubmitType( isInit ) {
	var field = GetSelectedField();
	if ( field.type !== 'submit' ) {
		return;
	}

	var type = jQuery( 'input[name=submit_type]:checked' ).val();
	if( ! isInit ) {
		form.button.type = type;
	}

	var $formSubmitButton        = jQuery( '#gform_submit_button_' + form.id );
	var $submitImageSetting      = jQuery( '#submit_image' );
	var $submitImageSettingValue = $submitImageSetting.val();
	var $submitTextSetting       = jQuery( '#submit_text' );
	var $submitTextSettingValue  = $submitTextSetting.val();

	if( 'text' === type ) {
		ResetFieldAccessibilityWarning( 'submit_type_setting' );
		ResetFieldNotice( 'submit_image_setting' );
		jQuery( '.submit_text_setting' ).show();
		jQuery( '.submit_image_setting' ).hide();
	}

	if( 'image' === type ) {
		ResetFieldAccessibilityWarning( 'submit_type_setting' );
		SetFieldAccessibilityWarning( 'submit_type_setting', 'below' );
		if ( ! $submitImageSettingValue ) {
			SetFieldNotification( 'submit_image_setting', 'below' );
		}
		jQuery( '.submit_text_setting' ).hide();
		jQuery( '.submit_image_setting' ).show();
	}

	if( 'text' === type || ( 'image' === type && ! $submitImageSettingValue ) ) {
		var text = $submitTextSettingValue ? $submitTextSettingValue : gform_form_strings.defaultSubmit;
		$formSubmitButton.attr( 'type', 'submit' ).attr( 'value', text ).removeClass( 'gform_image_button' );
		$submitTextSetting.val( text );
	}

	if( 'image' === type && $submitImageSettingValue ) {
		ResetFieldNotice( 'submit_image_setting' );
		var src = $submitImageSettingValue ? $submitImageSettingValue : '';
		$formSubmitButton.attr( 'type', 'image' ).attr( 'src', src ).removeAttr( 'value' ).addClass( 'gform_image_button' );
		$submitImageSetting.val( src );
	}
}

//------------------------------------------------------------------------------------------------------------------------
//Color Picker
function iColorShow(mouseX, mouseY, id, callback){
    jQuery("#iColorPicker").css({'top': (mouseY - 150) +"px",'left':mouseX +"px",'position':'absolute'}).fadeIn("fast");
    jQuery("#iColorPickerBg").css({'position':'absolute','top':0,'left':0,'width':'100%','height':'100%'}).fadeIn("fast");
    var def=jQuery("#"+id).val();
    jQuery('#colorPreview span').text(def);
    jQuery('#colorPreview').css('background',def);
    jQuery('#color').val(def);
    var hxs=jQuery('#iColorPicker');
    for(i=0;i<hxs.length;i++){
        var tbl=document.getElementById('hexSection'+i);
        var tblChilds=tbl.childNodes;
        for(j=0;j<tblChilds.length;j++){
            var tblCells=tblChilds[j].childNodes;
            for(k=0;k<tblCells.length;k++){
                jQuery(tblChilds[j].childNodes[k]).unbind().mouseover(
                    function(a){var aaa="#"+jQuery(this).attr('hx');jQuery('#colorPreview').css('background',aaa);jQuery('#colorPreview span').text(aaa)}
                ).click(function(){
                    var aaa="#"+jQuery(this).attr('hx');
                    jQuery("#"+id).val(aaa);
                    jQuery("#chip_"+id).css("background-color",aaa);
                    jQuery("#iColorPickerBg").hide();
                    jQuery("#iColorPicker").fadeOut();
                    if(callback)
                        window[callback](aaa);
                    jQuery(this)})
            }
        }
    }
}
this.iColorPicker=function(){
    jQuery("input.iColorPicker").each(function(i){if(i==0){jQuery(document.createElement("div")).attr("id","iColorPicker").css('display','none').html('<table class="pickerTable" id="pickerTable0"><thead id="hexSection0"><tr><td style="background:#f00;" hx="f00"></td><td style="background:#ff0" hx="ff0"></td><td style="background:#0f0" hx="0f0"></td><td style="background:#0ff" hx="0ff"></td><td style="background:#00f" hx="00f"></td><td style="background:#f0f" hx="f0f"></td><td style="background:#fff" hx="fff"></td><td style="background:#ebebeb" hx="ebebeb"></td><td style="background:#e1e1e1" hx="e1e1e1"></td><td style="background:#d7d7d7" hx="d7d7d7"></td><td style="background:#cccccc" hx="cccccc"></td><td style="background:#c2c2c2" hx="c2c2c2"></td><td style="background:#b7b7b7" hx="b7b7b7"></td><td style="background:#acacac" hx="acacac"></td><td style="background:#a0a0a0" hx="a0a0a0"></td><td style="background:#959595" hx="959595"></td></tr><tr><td style="background:#ee1d24" hx="ee1d24"></td><td style="background:#fff100" hx="fff100"></td><td style="background:#00a650" hx="00a650"></td><td style="background:#00aeef" hx="00aeef"></td><td style="background:#2f3192" hx="2f3192"></td><td style="background:#ed008c" hx="ed008c"></td><td style="background:#898989" hx="898989"></td><td style="background:#7d7d7d" hx="7d7d7d"></td><td style="background:#707070" hx="707070"></td><td style="background:#626262" hx="626262"></td><td style="background:#555" hx="555"></td><td style="background:#464646" hx="464646"></td><td style="background:#363636" hx="363636"></td><td style="background:#262626" hx="262626"></td><td style="background:#111" hx="111"></td><td style="background:#000" hx="000"></td></tr><tr><td style="background:#f7977a" hx="f7977a"></td><td style="background:#fbad82" hx="fbad82"></td><td style="background:#fdc68c" hx="fdc68c"></td><td style="background:#fff799" hx="fff799"></td><td style="background:#c6df9c" hx="c6df9c"></td><td style="background:#a4d49d" hx="a4d49d"></td><td style="background:#81ca9d" hx="81ca9d"></td><td style="background:#7bcdc9" hx="7bcdc9"></td><td style="background:#6ccff7" hx="6ccff7"></td><td style="background:#7ca6d8" hx="7ca6d8"></td><td style="background:#8293ca" hx="8293ca"></td><td style="background:#8881be" hx="8881be"></td><td style="background:#a286bd" hx="a286bd"></td><td style="background:#bc8cbf" hx="bc8cbf"></td><td style="background:#f49bc1" hx="f49bc1"></td><td style="background:#f5999d" hx="f5999d"></td></tr><tr><td style="background:#f16c4d" hx="f16c4d"></td><td style="background:#f68e54" hx="f68e54"></td><td style="background:#fbaf5a" hx="fbaf5a"></td><td style="background:#fff467" hx="fff467"></td><td style="background:#acd372" hx="acd372"></td><td style="background:#7dc473" hx="7dc473"></td><td style="background:#39b778" hx="39b778"></td><td style="background:#16bcb4" hx="16bcb4"></td><td style="background:#00bff3" hx="00bff3"></td><td style="background:#438ccb" hx="438ccb"></td><td style="background:#5573b7" hx="5573b7"></td><td style="background:#5e5ca7" hx="5e5ca7"></td><td style="background:#855fa8" hx="855fa8"></td><td style="background:#a763a9" hx="a763a9"></td><td style="background:#ef6ea8" hx="ef6ea8"></td><td style="background:#f16d7e" hx="f16d7e"></td></tr><tr><td style="background:#ee1d24" hx="ee1d24"></td><td style="background:#f16522" hx="f16522"></td><td style="background:#f7941d" hx="f7941d"></td><td style="background:#fff100" hx="fff100"></td><td style="background:#8fc63d" hx="8fc63d"></td><td style="background:#37b44a" hx="37b44a"></td><td style="background:#00a650" hx="00a650"></td><td style="background:#00a99e" hx="00a99e"></td><td style="background:#00aeef" hx="00aeef"></td><td style="background:#0072bc" hx="0072bc"></td><td style="background:#0054a5" hx="0054a5"></td><td style="background:#2f3192" hx="2f3192"></td><td style="background:#652c91" hx="652c91"></td><td style="background:#91278f" hx="91278f"></td><td style="background:#ed008c" hx="ed008c"></td><td style="background:#ee105a" hx="ee105a"></td></tr><tr><td style="background:#9d0a0f" hx="9d0a0f"></td><td style="background:#a1410d" hx="a1410d"></td><td style="background:#a36209" hx="a36209"></td><td style="background:#aba000" hx="aba000"></td><td style="background:#588528" hx="588528"></td><td style="background:#197b30" hx="197b30"></td><td style="background:#007236" hx="007236"></td><td style="background:#00736a" hx="00736a"></td><td style="background:#0076a4" hx="0076a4"></td><td style="background:#004a80" hx="004a80"></td><td style="background:#003370" hx="003370"></td><td style="background:#1d1363" hx="1d1363"></td><td style="background:#450e61" hx="450e61"></td><td style="background:#62055f" hx="62055f"></td><td style="background:#9e005c" hx="9e005c"></td><td style="background:#9d0039" hx="9d0039"></td></tr><tr><td style="background:#790000" hx="790000"></td><td style="background:#7b3000" hx="7b3000"></td><td style="background:#7c4900" hx="7c4900"></td><td style="background:#827a00" hx="827a00"></td><td style="background:#3e6617" hx="3e6617"></td><td style="background:#045f20" hx="045f20"></td><td style="background:#005824" hx="005824"></td><td style="background:#005951" hx="005951"></td><td style="background:#005b7e" hx="005b7e"></td><td style="background:#003562" hx="003562"></td><td style="background:#002056" hx="002056"></td><td style="background:#0c004b" hx="0c004b"></td><td style="background:#30004a" hx="30004a"></td><td style="background:#4b0048" hx="4b0048"></td><td style="background:#7a0045" hx="7a0045"></td><td style="background:#7a0026" hx="7a0026"></td></tr></thead><tbody><tr><td style="border:1px solid #000;background:#fff;cursor:pointer;height:60px;-moz-background-clip:-moz-initial;-moz-background-origin:-moz-initial;-moz-background-inline-policy:-moz-initial;" colspan="16" align="center" id="colorPreview"><span style="color:#000;border:1px solid rgb(0, 0, 0);padding:5px;background-color:#fff;font:11px Arial, Helvetica, sans-serif;"></span></td></tr></tbody></table><style>#iColorPicker input{margin:2px}</style>').appendTo("body");jQuery(document.createElement("div")).attr("id","iColorPickerBg").click(function(){jQuery("#iColorPickerBg").hide();jQuery("#iColorPicker").fadeOut()}).appendTo("body");jQuery('table.pickerTable td').css({'width':'12px','height':'14px','border':'1px solid #000','cursor':'pointer'});jQuery('#iColorPicker table.pickerTable').css({'border-collapse':'collapse'});jQuery('#iColorPicker').css({'border':'1px solid #ccc','background':'#333','padding':'5px','color':'#fff','z-index':9999})}
    jQuery('#colorPreview').css({'height':'50px'});
    })
};

jQuery(function(){iColorPicker()});

function SetColorPickerColor(field_name, color, callback){
    var chip = jQuery('#chip_' + field_name);
    chip.css("background-color", color);
    if(callback)
        window[callback](color);
}

jQuery( document ).mouseup( function( e ) {
	var container = jQuery( "#iColorPicker" );
	if ( ! container.is( e.target ) && container.has( e.target ).length === 0 ) {
		jQuery( "#iColorPickerBg" ).hide();
		jQuery( "#iColorPicker" ).fadeOut();
	}
} );

function SetFieldChoices(){
    var field = GetSelectedField();
    for(var i=0; i<field.choices.length; i++){
        SetFieldChoice(GetInputType(field), i);
    }
}

function SetInputChoices($ul){
    var field = GetSelectedField(), $this, value, text, inputId;
    $ul.find('li').each(function(i){
        $this = jQuery(this);
        inputId = $this.data('input_id');
        value = $this.find('.field-choice-value').val();
        text = $this.find('.field-choice-text').val();
        SetInputChoice(inputId, i, value, text);
    });
}

function MergeInputArrays(inputs1, inputs2){
    var inputA, inputB;
    for(var i=0; i<inputs1.length; ++i) {
        inputA = inputs1[i];
        inputB = GetInput({inputs: inputs2},inputA.id);
        if(inputB){
            inputs1[i] = jQuery.extend(inputA, inputB);
        }
    }
    return inputs1;
}

/**
 * Perform Field Search.
 *
 * Performs a search for a field by the given search term.
 *
 * @since 2.4
 *
 * @param {string} element The search input element.
 *
 * @return void
 */
function FieldSearch( element ) {
	var search = jQuery( element ).val().toLowerCase();
	if ( search == '' ) {
		jQuery( '.add-buttons button' ).parent().css( 'display', 'block' );
		ResetFieldAccordions();
	} else {
		ShowAllFieldAccordions();
	}
	jQuery( '.add-buttons' ).each( function( index, group ) {
		SearchWithinFieldGroup( group, search );
	} )
}

/**
 * Add Clear Button to Search Input.
 *
 * Adds the classes to generate the clear button for the field search input.
 *
 * @since 2.5
 *
 * @param {string} element The search input element.
 *
 * @return void
 */
function addClearButton( element ) {
	var text = jQuery( element ).val();
	if ( text === '' ) {
		jQuery( '.search-button' ).removeClass( 'clearable' );
		jQuery( '.search-button span' ).removeClass( 'clear-button' );
	} else {
		jQuery( '.search-button' ).addClass( 'clearable' );
		jQuery( '.search-button span' ).addClass( 'clear-button' );
	}
}

/**
 * Clears the search input.
 *
 * Clears the search input when the clear button is clicked.
 *
 * @since 2.5
 *
 * @param {string} element The clear button element.
 *
 * @return void
 */
function clearInput( element ) {
	jQuery( element ).parent().children( 'input' ).val( '' );
	jQuery( element ).removeClass( 'clear-button' );
	FieldSearch( element );
}

/**
 * Reset Field Accordions.
 *
 * Resets the collapsed state of Field Accordions so that only the first one is open.
 *
 * @since 2.5
 *
 * @return void
 */
function ResetFieldAccordions() {
	jQuery( '#add_fields_menu .panel-block-tabs__wrapper' ).accordion( 'option', { active: false } );
	jQuery( '#add_fields_menu .panel-block-tabs__wrapper' ).first().accordion( 'option', { active: 0 } );
}

/**
 * Show All Field Accordions.
 *
 * Sets the accordion state to "open" for all Field Accordions.
 *
 * @since 2.5
 *
 * @return void
 */
function ShowAllFieldAccordions() {
	jQuery( '#add_fields_menu .panel-block-tabs__wrapper' ).accordion( 'option', { active: 0 } );
}

/**
 * Search Within Field Group
 *
 * Performs a simple string match against all the Field buttons within the given group, and hides any
 * which do not match.
 *
 * @since 2.5
 *
 * @param {string} group  The field group to search within.
 * @param {string} search The search term to match against.
 *
 * @return void
 */
function SearchWithinFieldGroup( group, search ) {
	var results = false;
	jQuery( group ).find( 'button' ).each( function( index, button ) {
		if ( jQuery( button ).val().toLowerCase().indexOf( search ) == -1 ) {
			jQuery( button ).parent().css( 'display', 'none' );
		} else {
			jQuery( button ).parent().css( 'display', 'block' );
			results = true;
		}
	} );

	var resultsDisplay = results ? 'none' : 'block';

	jQuery( group ).parent().find( '.gf-field-group__no-results' ).css( 'display', resultsDisplay );
}

/**
* Quick jQuery plugin that allows a variable to be passed which determins whether to
* instantly hide the element or slideUp instead.
*/
jQuery.fn.gfSlide = function(direction) {

    var isVisible = jQuery('.field_settings').is(':visible');

    if(direction == 'up') {
        if(!isVisible) {
            this.hide();
        } else {
            this.slideUp();
        }
    } else {
        if(!isVisible) {
            this.show();
        } else {
            this.slideDown();
        }
    }

    return this;
};

/**
 * Form Editor conditional logic should not allow adminOnly fields to be selectable. Also exclude the current field from being
 * set in conditional logic for itself.
 */
gform.addFilter( 'gform_is_conditional_logic_field', function( isConditionalLogicField, field ) {

    if( field.visibility == 'administrative' ) {
        isConditionalLogicField = false;
    } else if( field.id == GetSelectedField().id ) {
        isConditionalLogicField = false;
    }

    return isConditionalLogicField;
} );

/**
 * Validates the calculation formula.
 *
 * @since 2.4.6.8 Moved from form_detail.php and added filter.
 * @since 1.8
 *
 * @param formula The formula to be validated.
 *
 * @return boolean
 */
function IsValidFormula(formula) {
	if (formula == '')  {
		return true;
	}

	var patt = /{([^}]+)}/i,
		exprPatt = /^[0-9 -/*\(\)]+$/i,
		expr = formula.replace(/(\r\n|\n|\r)/gm, ''),
		match,
		result = false;

	while (match = patt.exec(expr)) {
		expr = expr.replace(match[0], 1);
	}

	if (exprPatt.test(expr)) {
		try {
			var r = eval(expr);
			result = !isNaN(parseFloat(r)) && isFinite(r);
		} catch (e) {
			result = false;
		}
	}

	/**
	 * Allow the validation result to be overridden.
	 *
	 * @since 2.4.6.8
	 *
	 * @param result The validation result.
	 * @param formula The calculation formula being validated.
	 */
	return gform.applyFilters( 'gform_is_valid_formula_form_editor', result, formula );
}

/**
 * Reset the field notice alert for a field setting.
 *
 * @since 2.6
 *
 * @param string [fieldSetting] The field setting class.
 */
function ResetFieldNotice( fieldSetting ) {
	if ( typeof fieldSetting !== 'undefined' ) {
		jQuery( '.' + fieldSetting )
			.nextAll( '.gform-alert--notice' ).remove()
			.prevAll( '.gform-alert--notice' ).remove();
	}
}

/**
 * Reset the field notice alerts for all field settings.
 *
 * @since 2.6
 */
function resetAllFieldNotices() {
	if ( jQuery('.editor-sidebar').find('.gform-alert--notice').length ) {
		jQuery('.editor-sidebar').find('.gform-alert--notice').remove();
	}
}

/**
 * Reset the field accessibility warning for a field setting.
 *
 * @param string [fieldSetting] The field setting class.
 */
function ResetFieldAccessibilityWarning( fieldSetting ) {
	if ( typeof fieldSetting !== 'undefined' ) {
		jQuery( '.' + fieldSetting )
			.nextAll( '.gform-alert--accessibility' ).remove()
			.prevAll( '.gform-alert--accessibility' ).remove();
	}
}

/**
 * Reset the field accessibility warnings for all field settings.
 *
 * @since 2.5.8
 */
function resetAllFieldAccessibilityWarnings() {
	if ( jQuery('.editor-sidebar').find('.gform-alert--accessibility').length ) {
		jQuery('.editor-sidebar').find('.gform-alert--accessibility').remove();
	}
}

/**
 * Set the field error for a field settings.
 *
 * We add the field setting to the "errors" field property and display the error
 * message next to the setting.
 *
 * @since 2.5
 *
 * @param {string} fieldSetting The field setting class name.
 * @param {string} position     The position to put the warning, can be 'above' or 'below'.
 * @param {string} [message]    The message to be set in the warning.
 */
function setFieldError( fieldSetting, position, message ) {
	var field = GetSelectedField();

	// Make sure this field can have errors.
	if ( field.type == 'page' ||
		field.type == 'section' ||
		field.type == 'html' ) {
		return;
	}

	var errorProperties = [ fieldSetting ];

	// Extra rules for the label setting.
	if ( fieldSetting === 'label_setting' ) {
		var fieldPlaceholder = field.hasOwnProperty( 'placeholder' ) ? field.placeholder : '';
		var fieldDescription = field.hasOwnProperty( 'description' ) ? field.description : '';

		if ( fieldPlaceholder !== '' || fieldDescription !== '' ) {
			SetFieldAccessibilityWarning( 'label_setting', 'below' );
			resetFieldError( 'label_setting' );

			return;
		} else {
			ResetFieldAccessibilityWarning( 'label_setting' );
		}
	}

	// Set up error property list to the "errors" property.
	if ( field.hasOwnProperty( 'errors' ) && ! field.errors.includes( fieldSetting ) ) {
		errorProperties = errorProperties.concat( field.errors );
	}
	SetFieldProperty( 'errors', errorProperties );

	// Get the error message.
	if ( message === undefined ) {
		message = getFieldErrorMessage( fieldSetting );
	}

	var errorDiv = '<div class="gform-alert gform-alert--error gform-alert--inline">';
		errorDiv += '<span class="gform-alert__icon gform-icon gform-icon--circle-error-fine" aria-hidden="true"></span>';
		errorDiv += '<div class="gform-alert__message-wrap">' + message + '</div>';
		errorDiv += '</div>';

	// Display the error message.
	var fieldSetting = jQuery( '.' + fieldSetting );
	fieldSetting.addClass( 'error' );
	if ( position === 'above' ) {
		fieldSetting.prevAll( '.gform-alert--error' ).remove();
		fieldSetting.before( errorDiv );
	} else {
		fieldSetting.nextAll( '.gform-alert--error' ).remove();
		fieldSetting.after( errorDiv );
	}
}

/**
 * Reset the field error for a field setting.
 *
 * @since 2.5
 *
 * @param {string} [fieldSetting] The field setting class name.
 */
function resetFieldError( fieldSetting ) {
	var field = GetSelectedField();
	var errorProperties = field.hasOwnProperty( 'errors' ) ? field.errors : [];

	if ( typeof fieldSetting !== 'undefined' ) {
		jQuery( '.' + fieldSetting )
			.nextAll( '.gform-alert--error' ).remove()
			.prevAll( '.gform-alert--error' ).remove();

		jQuery( '.' + fieldSetting ).removeClass( 'error' );

		var index = errorProperties.indexOf( fieldSetting );
		// Delete the field property from the errors.
		if ( index > -1 ) {
			if ( errorProperties.length > 1 ) {
				delete errorProperties[ index ];
			} else {
				errorProperties = [];
			}
		}
	}

	SetFieldProperty( 'errors', errorProperties );
}

/**
 * Reset the field errors for all field settings.
 *
 * @since 2.5.8
 */
function resetAllFieldErrors() {
	if ( ! jQuery( '.field_setting' ).hasClass( 'error' ) ) {
		return;
	}

	jQuery('.editor-sidebar .gform-alert--error').remove();
	jQuery('.field_setting').filter('.error').removeClass( 'error' );

	if ( form.fields.length > 0 ) {
		form.fields.forEach( function( field ) {
			if( field.hasOwnProperty( 'errors' ) && field.errors.length > 0 ) {
				field.errors = [];
			}
		} );
	}
}

/**
 * Check if a given field or the selected field has errors.
 *
 * @since 2.5
 *
 * @param {object} [field] The field object.
 *
 * @return {boolean}
 */
function fieldHasError( field ) {
	if ( typeof field === 'undefined' ) {
		field = GetSelectedField();
	}

	if ( field.hasOwnProperty( 'errors' ) && field.errors.length > 0 ) {
		return true;
	}

	return false;
}
