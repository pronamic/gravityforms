<?php

// If Gravity Forms isn't loaded, bail.
if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class GF_Field_Time
 *
 * Handles Time fields.
 *
 * @since Unknown
 * @uses  GF_Field
 */
class GF_Field_Time extends GF_Field {

	/**
	 * Sets the field type to be used in the field framework.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @var string $type The type of field this is.
	 */
	public $type = 'time';

	/**
	 * Sets the title of the field to be used in the form editor.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFCommon::get_field_type_title()
	 * @used-by GFAddOn::get_field_map_choices()
	 * @used-by GFAddOn::prepare_field_select_field()
	 * @used-by GFAddOn::settings_field_map_select()
	 * @used-by GF_Field::get_form_editor_button()
	 *
	 * @return string The field title.
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Time', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to submit a time as hours and minutes.', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--time';
	}

	/**
	 * Defines the field editor settings that are available for this field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormDetail::inline_scripts()
	 *
	 * @return array Contains the settings available within the field editor.
	 */
	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'sub_labels_setting',
			'label_placement_setting',
			'sub_label_placement_setting',
			'admin_label_setting',
			'time_format_setting',
			'rules_setting',
			'visibility_setting',
			'duplicate_setting',
			'default_input_values_setting',
			'input_placeholders_setting',
			'description_setting',
			'css_class_setting',
		);
	}

	/**
	 * Defines the IDs of required inputs.
	 *
	 * @since 2.5
	 *
	 * @return string[]
	 */
	public function get_required_inputs_ids() {
		return array( '1', '2' );
	}

	/**
	 * Validates the field inputs.
	 *
	 * @since 1.9
	 * @since 2.5.6 Updated to use set_required_error().
	 * @access public
	 *
	 * @used-by GFFormDisplay::validate()
	 * @uses    GF_Field_Time::$failed_validation
	 * @uses    GF_Field_Time::$validation_message
	 * @uses    GF_Field_Time::$timeFormat
	 * @uses    GF_Field_Time::$errorMessage
	 *
	 * @param array|string $value The field value or values to validate.
	 * @param array        $form  The Form Object.
	 *
	 * @return void
	 */
	public function validate( $value, $form ) {
		// Create variable values if time came in one field.
		if ( ! is_array( $value ) && ! empty( $value ) ) {
			preg_match( '/^(\d*):(\d*) ?(.*)$/', $value, $matches );
			$value    = array();
			$value[0] = $matches[1];
			$value[1] = $matches[2];
		}

		if ( is_array( $value ) && $this->isRequired ) {
			$this->set_required_error( $value, true );
		}

		$hour   = rgar( $value, 0 );
		$minute = rgar( $value, 1 );

		if ( empty( $hour ) && empty( $minute ) ) {
			return;
		}

		$is_valid_format = is_numeric( $hour ) && is_numeric( $minute );

		$min_hour   = $this->timeFormat == '24' ? 0 : 1;
		$max_hour   = $this->timeFormat == '24' ? 24 : 12;
		$max_minute = $hour >= 24 ? 0 : 59;

		if ( ! $is_valid_format || $hour < $min_hour || $hour > $max_hour || $minute < 0 || $minute > $max_minute ) {
			$this->failed_validation  = true;
			$this->validation_message = empty( $this->errorMessage ) ? esc_html__( 'Please enter a valid time.', 'gravityforms' ) : $this->errorMessage;
		}
	}

	/**
	 * Updates the value to use the input ids as the keys before it's used to generate the complex validation message.
	 *
	 * @since 2.6.5
	 *
	 * @param array $value The value to be prepared.
	 *
	 * @return array
	 */
	public function prepare_complex_validation_value( $value ) {
		return array(
			"{$this->id}.1" => rgar( $value, 0 ),
			"{$this->id}.2" => rgar( $value, 1 ),
			"{$this->id}.3" => rgar( $value, 2 ),
		);
	}

	/**
	 * Returns the HTML tag for the field container.
	 *
	 * @since 2.5
	 *
	 * @param array $form The current Form object.
	 *
	 * @return string
	 */
	public function get_field_container_tag( $form ) {

		if ( GFCommon::is_legacy_markup_enabled( $form ) ) {
			return parent::get_field_container_tag( $form );
		}

		return 'fieldset';

	}

	/**
	 * Defines how the Time field input is shown.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFCommon::get_field_input()
	 * @uses    GF_Field::is_entry_detail()
	 * @uses    GF_Field::is_form_editor()
	 * @uses    GF_Field_Time::$subLabelPlacement
	 * @uses    GFFormsModel::get_input()
	 * @uses    GF_Field::get_input_placeholder_attribute()
	 * @uses    GF_Field::get_tabindex()
	 * @uses    GFFormsModel::is_html5_enabled()
	 *
	 * @param array      $form  The Form Object.
	 * @param string     $value The field default value. Defaults to empty string.
	 * @param array|null $entry The Entry Object, if available. Defaults to null.
	 *
	 * @return string The field HTML markup.
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {

		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$form_id  = absint( $form['id'] );
		$id       = intval( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$form_sub_label_placement  = rgar( $form, 'subLabelPlacement' );
		$field_sub_label_placement = $this->subLabelPlacement;
		$colon_pmam_placement      = empty( $field_sub_label_placement ) || $field_sub_label_placement == 'hidden_label' ? 'below' : $field_sub_label_placement;
		$is_sub_label_above        = $field_sub_label_placement == 'above' || ( empty( $field_sub_label_placement ) && $form_sub_label_placement == 'above' );

		$disabled_text = $is_form_editor ? "disabled='disabled'" : '';

		$hour = $minute = $am_selected = $pm_selected = '';

		if ( ! is_array( $value ) && ! empty( $value ) ) {
			preg_match( '/^(\d*):(\d*) ?(.*)$/', $value, $matches );
			$hour        = esc_attr( $matches[1] );
			$minute      = esc_attr( $matches[2] );
			$the_rest    = strtolower( rgar( $matches, 3 ) );
			$am_selected = strpos( $the_rest, 'am' ) > -1 ? "selected='selected'" : '';
			$pm_selected = strpos( $the_rest, 'pm' ) > -1  ? "selected='selected'" : '';
		} elseif ( is_array( $value ) ) {
			$value       = array_values( $value );
			$hour        = esc_attr( $value[0] );
			$minute      = esc_attr( $value[1] );
			$am_selected = strtolower( rgar( $value, 2 ) ) == 'am' ? "selected='selected'" : '';
			$pm_selected = strtolower( rgar( $value, 2 ) ) == 'pm' ? "selected='selected'" : '';
		}

		$hour_input   = GFFormsModel::get_input( $this, $this->id . '.1' );
		$minute_input = GFFormsModel::get_input( $this, $this->id . '.2' );

		$hour_placeholder_attribute   = $this->get_input_placeholder_attribute( $hour_input ) ? $this->get_input_placeholder_attribute( $hour_input ) : "placeholder='" . esc_attr__( 'HH', 'gravityforms' ) . "'";
		$minute_placeholder_attribute = $this->get_input_placeholder_attribute( $minute_input ) ? $this->get_input_placeholder_attribute( $minute_input ) : "placeholder='" . esc_attr( _x( 'MM', 'Abbreviation: Minutes', 'gravityforms' ) ) . "'";

		$hour_tabindex   = $this->get_tabindex();
		$minute_tabindex = $this->get_tabindex();
		$ampm_tabindex   = $this->get_tabindex();

		$is_html5   = RGFormsModel::is_html5_enabled();
		$input_type = $is_html5 ? 'number' : 'text';

		$max_hour = $this->timeFormat == '24' ? 24 : 12;
		$hour_html5_attributes   = $is_html5 ? "min='0' max='{$max_hour}' step='1'" : '';
		$minute_html5_attributes = $is_html5 ? "min='0' max='59' step='1'" : '';

		$clear_multi_div_open = GFCommon::is_legacy_markup_enabled( $form ) ? '<div class="clear-multi">' : '';
		$clear_multi_div_close = GFCommon::is_legacy_markup_enabled( $form ) ? '</div>' : '';

		$output_shim = $is_sub_label_above && GFCommon::is_legacy_markup_enabled( $form );

		$ampm_field_style = $is_form_editor && $this->timeFormat == '24' ? "style='display:none;'" : '';
		if ( $is_form_editor || $this->timeFormat != '24' ) {
			$am_text = esc_html__( 'AM', 'gravityforms' );
			$pm_text = esc_html__( 'PM', 'gravityforms' );
			$ampm_field = "<div class='gfield_time_ampm ginput_container ginput_container_time {$colon_pmam_placement}' {$ampm_field_style}>
                                " . ( $output_shim ? "<div class='gfield_time_ampm_shim' aria-hidden='true'>&nbsp;</div>" : "" ) . "
                                <select name='input_{$id}[]' id='{$field_id}_3' $ampm_tabindex {$disabled_text}>
                                    <option value='am' {$am_selected}>{$am_text}</option>
                                    <option value='pm' {$pm_selected}>{$pm_text}</option>
                                </select> 
                                <label class='am_pm_label screen-reader-text' for='{$field_id}_3'>" . esc_html__( 'AM/PM', 'gravityforms' ) . "</label>                                
                           </div>";
		} else {
			$ampm_field = '';
		}

		$hour_label_class = $minute_label_class = '';

		if ( rgar( $hour_input, 'customLabel' ) !== '' ) {
			$hour_label = esc_html( $hour_input['customLabel'] );
		} else if ( rgar( $hour_input, 'placeholder' ) ) {
			$hour_label = esc_html__( 'HH', 'gravityforms' );
		} else {
			$hour_label       = esc_html__( 'Hours', 'gravityforms' );
			$hour_label_class = " screen-reader-text";
		}

		if ( rgar( $minute_input, 'customLabel' ) !== '' ) {
			$minute_label = esc_html( $minute_input['customLabel'] );
		} else if ( rgar( $minute_input, 'placeholder' ) ) {
			$minute_label = esc_html( _x( 'MM', 'Abbreviation: Minutes', 'gravityforms' ) );
		} else {
			$minute_label       = esc_html__( 'Minutes', 'gravityforms' );
			$minute_label_class = " screen-reader-text";
		}

		if ( $field_sub_label_placement === 'hidden_label' ) {
			$hour_label_class   = ' hidden_sub_label screen-reader-text';
			$minute_label_class = ' hidden_sub_label screen-reader-text';
		}

		$input_values = array(
			$this->id . '.1' => $hour,
			$this->id . '.2' => $minute,
		);

		$hour_aria_attributes   = $this->get_aria_attributes( $input_values, '1' );
		$minute_aria_attributes = $this->get_aria_attributes( $input_values, '2' );
		$aria_describedby       = $this->get_aria_describedby();

		$legacy_markup_colon = GFCommon::is_legacy_markup_enabled( $form ) ? '<i>:</i>' : '';
		$new_markup_colon    = GFCommon::is_legacy_markup_enabled( $form ) ? '' : '<div class="' . $colon_pmam_placement . ' hour_minute_colon">:</div>';

		if ( $is_sub_label_above ) {
			$markup = "{$clear_multi_div_open}
                        <div class='gfield_time_hour ginput_container ginput_container_time' id='{$field_id}'>
                            <label class='hour_label{$hour_label_class}' for='{$field_id}_1'>{$hour_label}</label>
                            <input type='{$input_type}' maxlength='2' name='input_{$id}[]' id='{$field_id}_1' value='{$hour}' {$hour_tabindex} {$hour_html5_attributes} {$disabled_text} {$hour_placeholder_attribute} {$hour_aria_attributes} {$aria_describedby}/> {$legacy_markup_colon}
                        </div>
                        {$new_markup_colon}
                        <div class='gfield_time_minute ginput_container ginput_container_time'>
                            <label class='minute_label{$minute_label_class}' for='{$field_id}_2'>{$minute_label}</label>
                            <input type='{$input_type}' maxlength='2' name='input_{$id}[]' id='{$field_id}_2' value='{$minute}' {$minute_tabindex} {$minute_html5_attributes} {$disabled_text} {$minute_placeholder_attribute} {$minute_aria_attributes}/>
                        </div>
                        {$ampm_field}
                    {$clear_multi_div_close}";
		} else {
			$markup = "{$clear_multi_div_open}
                        <div class='gfield_time_hour ginput_container ginput_container_time' id='{$field_id}'>
                            <input type='{$input_type}' maxlength='2' name='input_{$id}[]' id='{$field_id}_1' value='{$hour}' {$hour_tabindex} {$hour_html5_attributes} {$disabled_text} {$hour_placeholder_attribute} {$hour_aria_attributes} {$aria_describedby}/> {$legacy_markup_colon}
                            <label class='hour_label{$hour_label_class}' for='{$field_id}_1'>{$hour_label}</label>
                        </div>
                        {$new_markup_colon}
                        <div class='gfield_time_minute ginput_container ginput_container_time'>
                            <input type='{$input_type}' maxlength='2' name='input_{$id}[]' id='{$field_id}_2' value='{$minute}' {$minute_tabindex} {$minute_html5_attributes} {$disabled_text} {$minute_placeholder_attribute} {$minute_aria_attributes}/>
                            <label class='minute_label{$minute_label_class}' for='{$field_id}_2'>{$minute_label}</label>
                        </div>
                        {$ampm_field}
                    {$clear_multi_div_close}";
		}


		return sprintf( '<div class="ginput_complex">%s</div>', $markup );
	}

	/**
	 * Whether this field expects an array during submission.
	 *
	 * @since 2.4
	 *
	 * @return bool
	 */
	public function is_value_submission_array() {
		return true;
	}

	/**
	 * Determines if any of the submission values are empty.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormDisplay::is_empty()
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return bool True if empty. False otherwise.
	 */
	public function is_value_submission_empty( $form_id ) {
		$value = rgpost( 'input_' . $this->id );

		if ( ! is_array( $value ) ) {
			return strlen( trim( $value ) ) <= 0;
		}

		// Ignoring the AM/PM value; it's always set for embedded forms.
		unset( $value[2] );

		return GFCommon::is_empty_array( $value );
	}

	/**
	 * Determines whether the given value is considered empty for this field.
	 *
	 * @since  2.4
	 *
	 * @param string|array $value The value.
	 *
	 * @return bool True if empty. False otherwise.
	 */
	public function is_value_empty( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $input ) {
				if ( strlen( trim( $input ) ) <= 0 ) {
					return true;
				}
			}

			return false;
		} else {
			return strlen( trim( $value ) ) <= 0;
		}
	}

	/**
	 * Prepares the field value to be saved after an entry is submitted.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormsModel::prepare_value()
	 *
	 * @param string $value      The value to prepare.
	 * @param array  $form       The Form Object. Not used.
	 * @param string $input_name The name of the input. Not used.
	 * @param int    $lead_id    The entry ID. Not used.
	 * @param array  $lead       The Entry Object. Not used.
	 *
	 * @return array|string      The field value, prepared and stripped of tags.
	 */
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {

		if ( empty( $value ) && ! is_array( $value ) ) {
			return '';
		}

		// If $value is a default value and also an array, it will be an associative array; to be safe, let's convert all array $value to numeric.
		if ( is_array( $value ) ) {
			$value = array_values( $value );
		}

		if ( ! is_array( $value ) && ! empty( $value ) ) {
			preg_match( '/^(\d*):(\d*) ?(.*)$/', $value, $matches );
			$value    = array();
			$value[0] = $matches[1];
			$value[1] = $matches[2];
			$value[2] = rgar( $matches, 3 );
		}

		$hour   = wp_strip_all_tags( $value[0] );
		$minute = wp_strip_all_tags( $value[1] );
		$ampm   = wp_strip_all_tags( rgar( $value, 2 ) );
		if ( ! empty( $ampm ) ) {
			$ampm = " $ampm";
		}

		if ( ! ( rgblank( $hour ) && rgblank( $minute ) ) ) {
			$value = sprintf( '%02d:%02d%s', $hour, $minute, $ampm );
		} else {
			$value = '';
		}

		return $value;
	}

	/**
	 * Returns a JS script to be rendered in the front end of the form.
	 *
	 * @param array $form The Form Object
	 *
	 * @return string Returns a JS script to be processed in the front end.
	 */
	public function get_form_inline_script_on_page_render( $form ) {

		//Only return merge tag script if form supports JS merge tags
		if ( ! GFFormDisplay::has_js_merge_tag( $form ) ) {
			return '';
		}

		return "gform.addFilter( 'gform_value_merge_tag_{$form['id']}_{$this->id}', function( value, input, modifier ) { if( modifier === 'label' ) { return false; } var ampm = input.length == 3 ? ' ' + jQuery(input[2]).val() : ''; return jQuery(input[0]).val() + ':' + jQuery(input[1]).val() + ' ' + ampm; } );";

	}

	/**
	 * Returns the scripts to be included for this field type in the form editor.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function get_form_editor_inline_script_on_page_render() {
		// No support for custom sub AM/PM sub label.
		return "gform.addAction( 'gform_post_load_field_settings' , function( [ field, form ] ) { if( GetInputType( field ) === 'time' ) { jQuery('.field_custom_input_row_input_' + field.id + '_3').hide(); } } );";
	}

	/**
	 * Overrides GF_Field to prevent the standard input ID from being used.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return null
	 */
	public function get_entry_inputs() {
		return null;
	}


	/**
	 * Removes the "for" attribute in the field label. Inputs are only allowed one label (a11y) and the inputs already have labels.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @param array $form The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_first_input_id( $form ) {
		return '';
	}

	/**
	 * Sanitizes settings for the Time field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormDetail::add_field()
	 * @used-by GFFormsModel::sanitize_settings()
	 * @uses    GF_Field::sanitize_settings
	 * @uses    GF_Field_Time::$timeFormat
	 *
	 * @return void
	 */
	public function sanitize_settings() {
		parent::sanitize_settings();
		if ( ! $this->timeFormat || ! in_array( $this->timeFormat, array( 12, 24 ) ) ) {
			$this->timeFormat = '12';
		}
	}

}

// Register the Time field with the field framework.
GF_Fields::register( new GF_Field_Time() );
