<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class GF_Field_Address extends GF_Field {

	public $type = 'address';

	/**
	 * Indicates if this field supports state validation.
	 *
	 * @since 3.0
	 *
	 * @var bool
	 */
	protected $_supports_state_validation = true;

	/**
	 * Whether this field allows links/URLs in the value.
	 *
	 * @since 3.1.0
	 *
	 * @var bool
	 */
	public $noURLs = true;

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'admin_label_setting',
			'label_placement_setting',
			'sub_label_placement_setting',
			'default_input_values_setting',
			'input_placeholders_setting',
			'address_setting',
			'rules_setting',
			'copy_values_option',
			'description_setting',
			'visibility_setting',
			'css_class_setting',
			'autocomplete_setting',
		);
	}

	public function is_conditional_logic_supported() {
		return true;
	}

	public function get_form_editor_field_title() {
		return esc_attr__( 'Address', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to enter a physical address.', 'gravityforms' );
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
		return 'gform-icon--place';
	}

	/**
	 * Defines the IDs of required inputs.
	 *
	 * @since 2.5
	 *
	 * @return string[]
	 */
	public function get_required_inputs_ids() {
		return array( '1', '3', '4', '5', '6' );
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
	 * Validates the address field inputs.
	 *
	 * @since 1.9
	 * @since 2.6.5 Updated to use set_required_error().
	 *
	 * @param string|array $value The field value from get_value_submission().
	 * @param array        $form  The Form Object currently being processed.
	 *
	 * @return void
	 */
	public function validate( $value, $form ) {
		if ( ! $this->isRequired ) {
			return;
		}

		$copy_values_option_activated = $this->enableCopyValuesOption && rgpost( 'input_' . $this->id . '_copy_values_activated' );
		if ( $copy_values_option_activated ) {
			// Validation will occur in the source field.
			return;
		}

		$this->set_required_error( $value, true );
	}

	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		$value                                         = parent::get_value_submission( $field_values, $get_from_post_global_var );
		$value[ $this->id . '_copy_values_activated' ] = (bool) rgpost( 'input_' . $this->id . '_copy_values_activated' );

		return $value;
	}

	public function get_field_input( $form, $value = '', $entry = null ) {

		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin        = $is_entry_detail || $is_form_editor;

		$form_id  = absint( $form['id'] );
		$id       = intval( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$form_id  = ( $is_entry_detail || $is_form_editor ) && empty( $form_id ) ? rgget( 'id' ) : $form_id;

		$disabled_text      = $is_form_editor ? "disabled='disabled'" : '';
		$class_suffix       = $is_entry_detail ? '_admin' : '';

		$is_sub_label_above = $this->is_sub_label_above( $form );

		$sub_label_class = $this->subLabelPlacement == 'hidden_label' ? "hidden_sub_label screen-reader-text" : '';

		$street_value  = '';
		$street2_value = '';
		$city_value    = '';
		$state_value   = '';
		$zip_value     = '';
		$country_value = '';

		if ( is_array( $value ) ) {
			$street_value  = esc_attr( rgget( $this->id . '.1', $value ) );
			$street2_value = esc_attr( rgget( $this->id . '.2', $value ) );
			$city_value    = esc_attr( rgget( $this->id . '.3', $value ) );
			$state_value   = esc_attr( rgget( $this->id . '.4', $value ) );
			$zip_value     = esc_attr( rgget( $this->id . '.5', $value ) );
			$country_value = esc_attr( rgget( $this->id . '.6', $value ) );
		}

		// Inputs.
		$address_street_field_input  = GFFormsModel::get_input( $this, $this->id . '.1' );
		$address_street2_field_input = GFFormsModel::get_input( $this, $this->id . '.2' );
		$address_city_field_input    = GFFormsModel::get_input( $this, $this->id . '.3' );
		$address_state_field_input   = GFFormsModel::get_input( $this, $this->id . '.4' );
		$address_zip_field_input     = GFFormsModel::get_input( $this, $this->id . '.5' );
		$address_country_field_input = GFFormsModel::get_input( $this, $this->id . '.6' );

		// Placeholders.
		$street_placeholder_attribute  = GFCommon::get_input_placeholder_attribute( $address_street_field_input );
		$street2_placeholder_attribute = GFCommon::get_input_placeholder_attribute( $address_street2_field_input );
		$city_placeholder_attribute    = GFCommon::get_input_placeholder_attribute( $address_city_field_input );
		$zip_placeholder_attribute     = GFCommon::get_input_placeholder_attribute( $address_zip_field_input );

		$address_type = $this->get_selected_address_type();

		$state_label  = empty( $address_type['state_label'] ) ? esc_html__( 'State', 'gravityforms' ) : $address_type['state_label'];
		$zip_label    = empty( $address_type['zip_label'] ) ? esc_html__( 'Zip Code', 'gravityforms' ) : $address_type['zip_label'];
		$hide_country = ! empty( $address_type['country'] ) || $this->hideCountry || rgar( $address_country_field_input, 'isHidden' );

		if ( empty( $country_value ) ) {
			$country_value = $this->defaultCountry;
		}

		if ( empty( $state_value ) ) {
			$state_value = $this->defaultState;
		}

		$country_placeholder = GFCommon::get_input_placeholder_value( $address_country_field_input );
		$country_list        = $this->get_country_dropdown( $country_value, $country_placeholder );

		// Changing css classes based on field format to ensure proper display.
		$address_display_format = apply_filters( 'gform_address_display_format', 'default', $this );
		$city_location          = $address_display_format == 'zip_before_city' ? 'right' : 'left';
		$zip_location           = $address_display_format != 'zip_before_city' && ( $this->hideState || rgar( $address_state_field_input, 'isHidden' ) ) ? 'right' : 'left'; // support for $this->hideState legacy property
		$state_location         = $address_display_format == 'zip_before_city' ? 'left' : 'right';
		$country_location       = $this->hideState || rgar( $address_state_field_input, 'isHidden' ) ? 'left' : 'right'; // support for $this->hideState legacy property

		// Labels.
		$address_street_sub_label  = rgar( $address_street_field_input, 'customLabel' ) != '' ? $address_street_field_input['customLabel'] : esc_html__( 'Street Address', 'gravityforms' );
		$address_street_sub_label  = gf_apply_filters( array( 'gform_address_street', $form_id, $this->id ), $address_street_sub_label, $form_id );
		$address_street2_sub_label = rgar( $address_street2_field_input, 'customLabel' ) != '' ? $address_street2_field_input['customLabel'] : esc_html__( 'Address Line 2', 'gravityforms' );
		$address_street2_sub_label = gf_apply_filters( array( 'gform_address_street2', $form_id, $this->id ), $address_street2_sub_label, $form_id );
		$address_zip_sub_label     = rgar( $address_zip_field_input, 'customLabel' ) != '' ? $address_zip_field_input['customLabel'] : $zip_label;
		$address_zip_sub_label     = gf_apply_filters( array( 'gform_address_zip', $form_id, $this->id ), $address_zip_sub_label, $form_id );
		$address_city_sub_label    = rgar( $address_city_field_input, 'customLabel' ) != '' ? $address_city_field_input['customLabel'] : esc_html__( 'City', 'gravityforms' );
		$address_city_sub_label    = gf_apply_filters( array( 'gform_address_city', $form_id, $this->id ), $address_city_sub_label, $form_id );
		$address_state_sub_label   = rgar( $address_state_field_input, 'customLabel' ) != '' ? $address_state_field_input['customLabel'] : $state_label;
		$address_state_sub_label   = gf_apply_filters( array( 'gform_address_state', $form_id, $this->id ), $address_state_sub_label, $form_id );
		$address_country_sub_label = rgar( $address_country_field_input, 'customLabel' ) != '' ? $address_country_field_input['customLabel'] : esc_html__( 'Country', 'gravityforms' );
		$address_country_sub_label = gf_apply_filters( array( 'gform_address_country', $form_id, $this->id ), $address_country_sub_label, $form_id );

		// Autocomplete attributes.
		$address_street_autocomplete  = $this->enableAutocomplete ? $this->get_input_autocomplete_attribute( $address_street_field_input ) : '';
		$address_street2_autocomplete = $this->enableAutocomplete ? $this->get_input_autocomplete_attribute( $address_street2_field_input ) : '';
		$address_city_autocomplete    = $this->enableAutocomplete ? $this->get_input_autocomplete_attribute( $address_city_field_input ) : '';
		$address_zip_autocomplete     = $this->enableAutocomplete ? $this->get_input_autocomplete_attribute( $address_zip_field_input ) : '';
		$address_country_autocomplete = $this->enableAutocomplete ? $this->get_input_autocomplete_attribute( $address_country_field_input ) : '';

		// Aria attributes.
		$street_aria_attributes  = $this->get_aria_attributes( $value, '1' );
		$street2_aria_attributes = $this->get_aria_attributes( $value, '2' );
		$city_aria_attributes    = $this->get_aria_attributes( $value, '3' );
		$zip_aria_attributes     = $this->get_aria_attributes( $value, '5' );
		$country_aria_attributes = $this->get_aria_attributes( $value, '6' );

		// Address field.
		$street_address = '';
		$tabindex       = $this->get_tabindex();
		$style          = ( $is_admin && rgar( $address_street_field_input, 'isHidden' ) ) ? "style='display:none;'" : '';

		if ( $is_admin || ! rgar( $address_street_field_input, 'isHidden' ) ) {
			if ( $is_sub_label_above ) {
				$street_address = " <span class='ginput_full{$class_suffix} address_line_1 ginput_address_line_1 gform-grid-col' id='{$field_id}_1_container' {$style}>
                                        <label for='{$field_id}_1' id='{$field_id}_1_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_street_sub_label}</label>
                                        <input type='text' name='input_{$id}.1' id='{$field_id}_1' value='{$street_value}' {$tabindex} {$disabled_text} {$street_placeholder_attribute} {$street_aria_attributes} {$address_street_autocomplete} {$this->maybe_add_aria_describedby( $address_street_field_input, $field_id, $this['formId'] )}/>
                                   </span>";
			} else {
				$street_address = " <span class='ginput_full{$class_suffix} address_line_1 ginput_address_line_1 gform-grid-col' id='{$field_id}_1_container' {$style}>
                                        <input type='text' name='input_{$id}.1' id='{$field_id}_1' value='{$street_value}' {$tabindex} {$disabled_text} {$street_placeholder_attribute} {$street_aria_attributes} {$address_street_autocomplete} {$this->maybe_add_aria_describedby( $address_street_field_input, $field_id, $this['formId'] )}/>
                                        <label for='{$field_id}_1' id='{$field_id}_1_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_street_sub_label}</label>
                                    </span>";
			}
		}

		// Address line 2 field.
		$street_address2 = '';
		$style           = ( $is_admin && ( $this->hideAddress2 || rgar( $address_street2_field_input, 'isHidden' ) ) ) ? "style='display:none;'" : ''; // support for $this->hideAddress2 legacy property
		if ( $is_admin || ( ! $this->hideAddress2 && ! rgar( $address_street2_field_input, 'isHidden' ) ) ) {
			$tabindex = $this->get_tabindex();
			if ( $is_sub_label_above ) {
				$street_address2 = "<span class='ginput_full{$class_suffix} address_line_2 ginput_address_line_2 gform-grid-col' id='{$field_id}_2_container' {$style}>
                                        <label for='{$field_id}_2' id='{$field_id}_2_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_street2_sub_label}</label>
                                        <input type='text' name='input_{$id}.2' id='{$field_id}_2' value='{$street2_value}' {$tabindex} {$disabled_text} {$street2_placeholder_attribute} {$address_street2_autocomplete} {$street2_aria_attributes} {$this->maybe_add_aria_describedby( $address_street2_field_input, $field_id, $this['formId'] )}/>
                                    </span>";
			} else {
				$street_address2 = "<span class='ginput_full{$class_suffix} address_line_2 ginput_address_line_2 gform-grid-col' id='{$field_id}_2_container' {$style}>
                                        <input type='text' name='input_{$id}.2' id='{$field_id}_2' value='{$street2_value}' {$tabindex} {$disabled_text} {$street2_placeholder_attribute} {$address_street2_autocomplete} {$street2_aria_attributes} {$this->maybe_add_aria_describedby( $address_street2_field_input, $field_id, $this['formId'] )}/>
                                        <label for='{$field_id}_2' id='{$field_id}_2_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_street2_sub_label}</label>
                                    </span>";
			}
		}

		if ( $address_display_format == 'zip_before_city' ) {
			// Zip field.
			$zip      = '';
			$tabindex = $this->get_tabindex();
			$style    = ( $is_admin && rgar( $address_zip_field_input, 'isHidden' ) ) ? "style='display:none;'" : '';
			if ( $is_admin || ! rgar( $address_zip_field_input, 'isHidden' ) ) {
				if ( $is_sub_label_above ) {
					$zip = "<span class='ginput_{$zip_location}{$class_suffix} address_zip ginput_address_zip gform-grid-col' id='{$field_id}_5_container' {$style}>
                                    <label for='{$field_id}_5' id='{$field_id}_5_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_zip_sub_label}</label>
                                    <input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$zip_value}' {$tabindex} {$disabled_text} {$zip_placeholder_attribute} {$zip_aria_attributes} {$address_zip_autocomplete} {$this->maybe_add_aria_describedby( $address_zip_field_input, $field_id, $this['formId'] )}/>
                                </span>";
				} else {
					$zip = "<span class='ginput_{$zip_location}{$class_suffix} address_zip ginput_address_zip gform-grid-col' id='{$field_id}_5_container' {$style}>
                                    <input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$zip_value}' {$tabindex} {$disabled_text} {$zip_placeholder_attribute} {$zip_aria_attributes} {$address_zip_autocomplete} {$this->maybe_add_aria_describedby( $address_zip_field_input, $field_id, $this['formId'] )}/>
                                    <label for='{$field_id}_5' id='{$field_id}_5_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_zip_sub_label}</label>
                                </span>";
				}
			}

			// City field.
			$city     = '';
			$tabindex = $this->get_tabindex();
			$style    = ( $is_admin && rgar( $address_city_field_input, 'isHidden' ) ) ? "style='display:none;'" : '';
			if ( $is_admin || ! rgar( $address_city_field_input, 'isHidden' ) ) {
				if ( $is_sub_label_above ) {
					$city = "<span class='ginput_{$city_location}{$class_suffix} address_city ginput_address_city gform-grid-col' id='{$field_id}_3_container' {$style}>
                                    <label for='{$field_id}_3' id='{$field_id}_3_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_city_sub_label}</label>
                                    <input type='text' name='input_{$id}.3' id='{$field_id}_3' value='{$city_value}' {$tabindex} {$disabled_text} {$city_placeholder_attribute} {$city_aria_attributes} {$address_city_autocomplete} {$this->maybe_add_aria_describedby( $address_city_field_input, $field_id, $this['formId'] )}/>
                                 </span>";
				} else {
					$city = "<span class='ginput_{$city_location}{$class_suffix} address_city ginput_address_city gform-grid-col' id='{$field_id}_3_container' {$style}>
                                    <input type='text' name='input_{$id}.3' id='{$field_id}_3' value='{$city_value}' {$tabindex} {$disabled_text} {$city_placeholder_attribute} {$city_aria_attributes} {$address_city_autocomplete} {$this->maybe_add_aria_describedby( $address_city_field_input, $field_id, $this['formId'] )}/>
                                    <label for='{$field_id}_3' id='{$field_id}_3_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_city_sub_label}</label>
                                 </span>";
				}
			}

			// State field.
			$style = ( $is_admin && ( $this->hideState || rgar( $address_state_field_input, 'isHidden' ) ) ) ? "style='display:none;'" : ''; // support for $this->hideState legacy property
			if ( $is_admin || ( ! $this->hideState && ! rgar( $address_state_field_input, 'isHidden' ) ) ) {
				$aria_attributes = $this->get_aria_attributes( $value, '4' );
				$state_field = $this->get_state_field( $id, $field_id, $state_value, $disabled_text, $form_id, $aria_attributes, $address_state_field_input );
				if ( $is_sub_label_above ) {
					$state = "<span class='ginput_{$state_location}{$class_suffix} address_state ginput_address_state gform-grid-col' id='{$field_id}_4_container' {$style}>
                                           <label for='{$field_id}_4' id='{$field_id}_4_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_state_sub_label}</label>
                                           $state_field
                                      </span>";
				} else {
					$state = "<span class='ginput_{$state_location}{$class_suffix} address_state ginput_address_state gform-grid-col' id='{$field_id}_4_container' {$style}>
                                           $state_field
                                           <label for='{$field_id}_4' id='{$field_id}_4_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_state_sub_label} </label>
                                      </span>";
				}
			} else {
				$state = sprintf( "<input type='hidden' class='gform_hidden' name='input_%d.4' id='%s_4' value='%s'/>", $id, $field_id, $state_value );
			}
		} else {

			// City field.
			$city     = '';
			$tabindex = $this->get_tabindex();
			$style    = ( $is_admin && rgar( $address_city_field_input, 'isHidden' ) ) ? "style='display:none;'" : '';
			if ( $is_admin || ! rgar( $address_city_field_input, 'isHidden' ) ) {
				if ( $is_sub_label_above ) {
					$city = "<span class='ginput_{$city_location}{$class_suffix} address_city ginput_address_city gform-grid-col' id='{$field_id}_3_container' {$style}>
                                    <label for='{$field_id}_3' id='{$field_id}_3_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_city_sub_label}</label>
                                    <input type='text' name='input_{$id}.3' id='{$field_id}_3' value='{$city_value}' {$tabindex} {$disabled_text} {$city_placeholder_attribute} {$city_aria_attributes} {$address_city_autocomplete} {$this->maybe_add_aria_describedby( $address_city_field_input, $field_id, $this['formId'] )}/>
                                 </span>";
				} else {
					$city = "<span class='ginput_{$city_location}{$class_suffix} address_city ginput_address_city gform-grid-col' id='{$field_id}_3_container' {$style}>
                                    <input type='text' name='input_{$id}.3' id='{$field_id}_3' value='{$city_value}' {$tabindex} {$disabled_text} {$city_placeholder_attribute} {$city_aria_attributes} {$address_city_autocomplete} {$this->maybe_add_aria_describedby( $address_city_field_input, $field_id, $this['formId'] )}/>
                                    <label for='{$field_id}_3' id='{$field_id}_3_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_city_sub_label}</label>
                                 </span>";
				}
			}

			// State field.
			$style = ( $is_admin && ( $this->hideState || rgar( $address_state_field_input, 'isHidden' ) ) ) ? "style='display:none;'" : ''; // support for $this->hideState legacy property
			if ( $is_admin || ( ! $this->hideState && ! rgar( $address_state_field_input, 'isHidden' ) ) ) {
				$aria_attributes = $this->get_aria_attributes( $value, '4' );
				$state_field = $this->get_state_field( $id, $field_id, $state_value, $disabled_text, $form_id, $aria_attributes, $address_state_field_input );
				if ( $is_sub_label_above ) {
					$state = "<span class='ginput_{$state_location}{$class_suffix} address_state ginput_address_state gform-grid-col' id='{$field_id}_4_container' {$style}>
                                        <label for='{$field_id}_4' id='{$field_id}_4_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>$address_state_sub_label</label>
                                        $state_field
                                      </span>";
				} else {
					$state = "<span class='ginput_{$state_location}{$class_suffix} address_state ginput_address_state gform-grid-col' id='{$field_id}_4_container' {$style}>
                                        $state_field
                                        <label for='{$field_id}_4' id='{$field_id}_4_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>$address_state_sub_label</label>
                                      </span>";
				}
			} else {
				$state = sprintf( "<input type='hidden' class='gform_hidden' name='input_%d.4' id='%s_4' value='%s'/>", $id, $field_id, $state_value );
			}

			// Zip field.
			$zip      = '';
			$tabindex = GFCommon::get_tabindex();
			$style    = ( $is_admin && rgar( $address_zip_field_input, 'isHidden' ) ) ? "style='display:none;'" : '';
			if ( $is_admin || ! rgar( $address_zip_field_input, 'isHidden' ) ) {
				if ( $is_sub_label_above ) {
					$zip = "<span class='ginput_{$zip_location}{$class_suffix} address_zip ginput_address_zip gform-grid-col' id='{$field_id}_5_container' {$style}>
                                    <label for='{$field_id}_5' id='{$field_id}_5_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_zip_sub_label}</label>
                                    <input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$zip_value}' {$tabindex} {$disabled_text} {$zip_placeholder_attribute} {$zip_aria_attributes} {$address_zip_autocomplete} {$this->maybe_add_aria_describedby( $address_zip_field_input, $field_id, $this['formId'] )}/>
                                </span>";
				} else {
					$zip = "<span class='ginput_{$zip_location}{$class_suffix} address_zip ginput_address_zip gform-grid-col' id='{$field_id}_5_container' {$style}>
                                    <input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$zip_value}' {$tabindex} {$disabled_text} {$zip_placeholder_attribute} {$zip_aria_attributes} {$address_zip_autocomplete} {$this->maybe_add_aria_describedby( $address_zip_field_input, $field_id, $this['formId'] )}/>
                                    <label for='{$field_id}_5' id='{$field_id}_5_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_zip_sub_label}</label>
                                </span>";
				}
			}
		}

		if ( $is_admin || ! $hide_country ) {
			$style    = $hide_country ? "style='display:none;'" : '';
			$tabindex = $this->get_tabindex();
			if ( $is_sub_label_above ) {
				$country = "<span class='ginput_{$country_location}{$class_suffix} address_country ginput_address_country gform-grid-col' id='{$field_id}_6_container' {$style}>
                                        <label for='{$field_id}_6' id='{$field_id}_6_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_country_sub_label}</label>
                                        <select name='input_{$id}.6' id='{$field_id}_6' {$tabindex} {$disabled_text} {$country_aria_attributes} {$address_country_autocomplete} {$this->maybe_add_aria_describedby( $address_country_field_input, $field_id, $this['formId'] )}>{$country_list} </select>
                                    </span>";
			} else {
				$country = "<span class='ginput_{$country_location}{$class_suffix} address_country ginput_address_country gform-grid-col' id='{$field_id}_6_container' {$style}>
                                        <select name='input_{$id}.6' id='{$field_id}_6' {$tabindex} {$disabled_text} {$country_aria_attributes} {$address_country_autocomplete} {$this->maybe_add_aria_describedby( $address_country_field_input, $field_id, $this['formId'] )}>{$country_list}</select>
                                        <label for='{$field_id}_6' id='{$field_id}_6_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_country_sub_label}</label>
                                    </span>";
			}
		} else {
			$country = sprintf( "<input type='hidden' class='gform_hidden' name='input_%d.6' id='%s_6' value='%s' {$this->maybe_add_aria_describedby( $address_country_field_input, $field_id, $this['formId'] )}/>", $id, $field_id, $country_value );
		}

		$inputs = $address_display_format == 'zip_before_city' ? $street_address . $street_address2 . $zip . $city . $state . $country : $street_address . $street_address2 . $city . $state . $zip . $country;

		$copy_values_option = '';
		$input_style        = '';
		if ( ( $this->enableCopyValuesOption || $is_form_editor ) && ! $is_entry_detail ) {
			$copy_values_label      = esc_html( $this->copyValuesOptionLabel );
			$copy_values_style      = $is_form_editor && ! $this->enableCopyValuesOption ? "style='display:none;'" : '';
			$copy_values_is_checked = isset( $value[$this->id . '_copy_values_activated'] ) ? $value[$this->id . '_copy_values_activated'] == true : $this->copyValuesOptionDefault == true;
			$copy_values_checked    = checked( true, $copy_values_is_checked, false );
			$copy_values_option     = "<div id='{$field_id}_copy_values_option_container' class='copy_values_option_container' {$copy_values_style}>
                                        <input type='checkbox' id='{$field_id}_copy_values_activated' class='copy_values_activated' value='1' data-source_field_id='" . absint( $this->copyValuesOptionField ) . "' name='input_{$id}_copy_values_activated' {$disabled_text} {$copy_values_checked}/>
                                        <label for='{$field_id}_copy_values_activated' id='{$field_id}_copy_values_option_label' class='copy_values_option_label inline gform-field-label gform-field-label--type-inline'>{$copy_values_label}</label>
                                    </div>";
			if ( $copy_values_is_checked ) {
				$input_style = "style='display:none;'";
			}
		}

		$css_class = $this->get_css_class();

		return "    {$copy_values_option}
                    <div class='ginput_complex{$class_suffix} ginput_container {$css_class} gform-grid-row' id='$field_id' {$input_style}>
                        {$inputs}
                    <div class='gf_clear gf_clear_complex'></div>
                </div>";
	}

	public function get_css_class() {

		$address_street_field_input  = GFFormsModel::get_input( $this, $this->id . '.1' );
		$address_street2_field_input = GFFormsModel::get_input( $this, $this->id . '.2' );
		$address_city_field_input    = GFFormsModel::get_input( $this, $this->id . '.3' );
		$address_state_field_input   = GFFormsModel::get_input( $this, $this->id . '.4' );
		$address_zip_field_input     = GFFormsModel::get_input( $this, $this->id . '.5' );
		$address_country_field_input = GFFormsModel::get_input( $this, $this->id . '.6' );

		$css_class = '';
		if ( ! rgar( $address_street_field_input, 'isHidden' ) ) {
			$css_class .= 'has_street ';
		}
		if ( ! rgar( $address_street2_field_input, 'isHidden' ) ) {
			$css_class .= 'has_street2 ';
		}
		if ( ! rgar( $address_city_field_input, 'isHidden' ) ) {
			$css_class .= 'has_city ';
		}
		if ( ! rgar( $address_state_field_input, 'isHidden' ) ) {
			$css_class .= 'has_state ';
		}
		if ( ! rgar( $address_zip_field_input, 'isHidden' ) ) {
			$css_class .= 'has_zip ';
		}
		if ( ! rgar( $address_country_field_input, 'isHidden' ) ) {
			$css_class .= 'has_country ';
		}

		$css_class .= 'ginput_container_address';

		return trim( $css_class );
	}

	public function get_address_types( $form_id ) {
		$addressTypes = array(
			'international' => array(
				'label'       => esc_html__( 'International', 'gravityforms' ),
				'zip_label'   => gf_apply_filters( array(
					'gform_address_zip',
					$form_id,
				), esc_html__( 'ZIP / Postal Code', 'gravityforms' ), $form_id ),
				'state_label' => gf_apply_filters( array(
					'gform_address_state',
					$form_id,
				), esc_html__( 'State / Province / Region', 'gravityforms' ), $form_id ),
			),
			'us'            => array(
				'label'       => esc_html__( 'United States', 'gravityforms' ),
				'zip_label'   => gf_apply_filters( array(
					'gform_address_zip',
					$form_id,
				), esc_html__( 'ZIP Code', 'gravityforms' ), $form_id ),
				'state_label' => gf_apply_filters( array(
					'gform_address_state',
					$form_id,
				), esc_html__( 'State', 'gravityforms' ), $form_id ),
				'country'     => 'United States',
				'states'      => array_merge( array( '' ), $this->get_us_states() ),
			),
			'canadian'      => array(
				'label'       => esc_html__( 'Canadian', 'gravityforms' ),
				'zip_label'   => gf_apply_filters( array(
					'gform_address_zip',
					$form_id,
				), esc_html__( 'Postal Code', 'gravityforms' ), $form_id ),
				'state_label' => gf_apply_filters( array(
					'gform_address_state',
					$form_id,
				), esc_html__( 'Province', 'gravityforms' ), $form_id ),
				'country'     => 'Canada',
				'states'      => array_merge( array( '' ), $this->get_canadian_provinces() ),
			),
		);

		/**
		 * Filters the address types available.
		 *
		 * @since Unknown
		 *
		 * @param array $addressTypes Contains the details for existing address types.
		 * @param int   $form_id      The form ID.
		 */
		return gf_apply_filters( array( 'gform_address_types', $form_id ), $addressTypes, $form_id );
	}

	/**
	 * Retrieve the default address type for this field.
	 *
	 * @param int $form_id The current form ID.
	 *
	 * @return string
	 */
	public function get_default_address_type( $form_id ) {
		$default_address_type = 'international';

		/**
		 * Allow the default address type to be overridden.
		 *
		 * @since 2.0.4
		 *
		 * @param string $default_address_type The default address type of international.
		 */
		return gf_apply_filters( array( 'gform_default_address_type', $form_id ), $default_address_type, $form_id );
	}

	/**
	 * Returns the properties of the selected address type.
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	public function get_selected_address_type() {
		$form_id       = absint( $this->formId );
		$types         = $this->get_address_types( $form_id );
		$default_type  = $this->get_default_address_type( $form_id );
		$selected_type = empty( $this->addressType ) ? $default_type : $this->addressType;
		$type          = rgar( $types, $selected_type, array() );

		return ( empty( $type ) && $default_type !== $selected_type ) ? rgar( $types, $default_type, array() ) : $type;
	}

	/**
	 * Generates state field markup.
	 *
	 * @since unknown
	 * @since 2.5                       added new params $$aria_attributes.
	 *
	 * @param integer $id               Input id.
	 * @param integer $field_id         Field id.
	 * @param string  $state_value      State value.
	 * @param string  $disabled_text    Disabled attribute.
	 * @param integer $form_id          Current form id being processed.
	 * @param string  $aria_attributes  Aria attributes values.
	 *
	 * @return string
	 */
	public function get_state_field( $id, $field_id, $state_value, $disabled_text, $form_id, $aria_attributes = '', $address_state_field_input = '' ) {

		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin        = $is_entry_detail || $is_form_editor;

		$state_dropdown_class = $state_text_class = $state_style = $text_style = $state_field_id = '';

		if ( empty( $state_value ) ) {
			$state_value = $this->defaultState;

			// For backwards compatibility (Canadian address type used to store the default state into the defaultProvince property).
			if ( $this->addressType == 'canadian' && ! empty( $this->defaultProvince ) ) {
				$state_value = $this->defaultProvince;
			}
		}

		$address_type        = $this->get_selected_address_type();
		$has_state_drop_down = isset( $address_type['states'] ) && is_array( $address_type['states'] );

		if ( $is_admin && rgget('view') != 'entry' ) {
			$state_dropdown_class = "class='state_dropdown'";
			$state_text_class     = "class='state_text'";
			$state_style          = ! $has_state_drop_down ? "style='display:none;'" : '';
			$text_style           = $has_state_drop_down ? "style='display:none;'" : '';
			$state_field_id       = '';
		} else {
			// ID only displayed on front end.
			$state_field_id = "id='" . $field_id . "_4'";
		}

		$tabindex           = $this->get_tabindex();
		$state_input        = GFFormsModel::get_input( $this, $this->id . '.4' );
		$state_placeholder  = GFCommon::get_input_placeholder_value( $state_input );
		$state_autocomplete = $this->enableAutocomplete ? $this->get_input_autocomplete_attribute( $state_input ) : '';
		$states             = empty( $address_type['states'] ) ? array() : $address_type['states'];
		$state_dropdown     = sprintf( "<select name='input_%d.4' %s {$tabindex} %s {$state_dropdown_class} {$state_style} {$aria_attributes} {$state_autocomplete} {$this->maybe_add_aria_describedby( $address_state_field_input, $field_id, $this['formId'] )}>%s</select>", $id, $state_field_id, $disabled_text, $this->get_state_dropdown( $states, $state_value, $state_placeholder ) );

		$tabindex                    = $this->get_tabindex();
		$state_placeholder_attribute = GFCommon::get_input_placeholder_attribute( $state_input );
		$state_text                  = sprintf( "<input type='text' name='input_%d.4' %s value='%s' {$tabindex} %s {$state_text_class} {$text_style} {$state_placeholder_attribute} {$aria_attributes} {$state_autocomplete} {$this->maybe_add_aria_describedby( $address_state_field_input, $field_id, $this['formId'] )}/>", $id, $state_field_id, $state_value, $disabled_text );

		if ( $is_admin && rgget('view') != 'entry' ) {
			return $state_dropdown . $state_text;
		} elseif ( $has_state_drop_down ) {
			return $state_dropdown;
		} else {
			return $state_text;
		}
	}

	/**
	 * Returns a list of countries.
	 *
	 * @since Unknown
	 * @since 2.4     Updated to use ISO 3166-1 list of countries.
	 * @since 2.4.20  Updated to use GF_Field_Address::get_default_countries() and to sort the countries.
	 * @since 3.0.3   Updated to use the country codes as the keys.
	 *
	 * @return array
	 */
	public function get_countries() {

		$countries = $this->get_default_countries();

		if ( class_exists( 'Collator' ) ) {
			$collator = new Collator( get_user_locale() );
			$collator->asort( $countries );
		} else {
			asort( $countries );
		}

		/**
		 * A list of countries displayed in the Address field country drop down.
		 *
		 * @since Unknown
		 *
		 * @param array $countries ISO 3166-1 list of countries.
		 */
		return apply_filters( 'gform_countries', $countries );

	}

	/**
	 * Returns the default array of countries using the ISO 3166-1 alpha-2 code as the key to the country name.
	 *
	 * @since 2.4.20
	 * @since 3.0.3  Added static caching.
	 *
	 * @return array
	 */
	public function get_default_countries() {
		static $countries;

		if ( ! empty( $countries ) ) {
			return $countries;
		}

		$countries = array(
			'AF' => __( 'Afghanistan', 'gravityforms' ),
			'AX' => __( 'Åland Islands', 'gravityforms' ),
			'AL' => __( 'Albania', 'gravityforms' ),
			'DZ' => __( 'Algeria', 'gravityforms' ),
			'AS' => __( 'American Samoa', 'gravityforms' ),
			'AD' => __( 'Andorra', 'gravityforms' ),
			'AO' => __( 'Angola', 'gravityforms' ),
			'AI' => __( 'Anguilla', 'gravityforms' ),
			'AQ' => __( 'Antarctica', 'gravityforms' ),
			'AG' => __( 'Antigua and Barbuda', 'gravityforms' ),
			'AR' => __( 'Argentina', 'gravityforms' ),
			'AM' => __( 'Armenia', 'gravityforms' ),
			'AW' => __( 'Aruba', 'gravityforms' ),
			'AU' => __( 'Australia', 'gravityforms' ),
			'AT' => __( 'Austria', 'gravityforms' ),
			'AZ' => __( 'Azerbaijan', 'gravityforms' ),
			'BS' => __( 'Bahamas', 'gravityforms' ),
			'BH' => __( 'Bahrain', 'gravityforms' ),
			'BD' => __( 'Bangladesh', 'gravityforms' ),
			'BB' => __( 'Barbados', 'gravityforms' ),
			'BY' => __( 'Belarus', 'gravityforms' ),
			'BE' => __( 'Belgium', 'gravityforms' ),
			'BZ' => __( 'Belize', 'gravityforms' ),
			'BJ' => __( 'Benin', 'gravityforms' ),
			'BM' => __( 'Bermuda', 'gravityforms' ),
			'BT' => __( 'Bhutan', 'gravityforms' ),
			'BO' => __( 'Bolivia', 'gravityforms' ),
			'BQ' => __( 'Bonaire, Sint Eustatius and Saba', 'gravityforms' ),
			'BA' => __( 'Bosnia and Herzegovina', 'gravityforms' ),
			'BW' => __( 'Botswana', 'gravityforms' ),
			'BV' => __( 'Bouvet Island', 'gravityforms' ),
			'BR' => __( 'Brazil', 'gravityforms' ),
			'IO' => __( 'British Indian Ocean Territory', 'gravityforms' ),
			'BN' => __( 'Brunei Darussalam', 'gravityforms' ),
			'BG' => __( 'Bulgaria', 'gravityforms' ),
			'BF' => __( 'Burkina Faso', 'gravityforms' ),
			'BI' => __( 'Burundi', 'gravityforms' ),
			'CV' => __( 'Cabo Verde', 'gravityforms' ),
			'KH' => __( 'Cambodia', 'gravityforms' ),
			'CM' => __( 'Cameroon', 'gravityforms' ),
			'CA' => __( 'Canada', 'gravityforms' ),
			'KY' => __( 'Cayman Islands', 'gravityforms' ),
			'CF' => __( 'Central African Republic', 'gravityforms' ),
			'TD' => __( 'Chad', 'gravityforms' ),
			'CL' => __( 'Chile', 'gravityforms' ),
			'CN' => __( 'China', 'gravityforms' ),
			'CX' => __( 'Christmas Island', 'gravityforms' ),
			'CC' => __( 'Cocos Islands', 'gravityforms' ),
			'CO' => __( 'Colombia', 'gravityforms' ),
			'KM' => __( 'Comoros', 'gravityforms' ),
			'CD' => __( 'Congo, Democratic Republic of the', 'gravityforms' ),
			'CG' => __( 'Congo', 'gravityforms' ),
			'CK' => __( 'Cook Islands', 'gravityforms' ),
			'CR' => __( 'Costa Rica', 'gravityforms' ),
			'CI' => __( "Côte d'Ivoire", 'gravityforms' ),
			'HR' => __( 'Croatia', 'gravityforms' ),
			'CU' => __( 'Cuba', 'gravityforms' ),
			'CW' => __( 'Curaçao', 'gravityforms' ),
			'CY' => __( 'Cyprus', 'gravityforms' ),
			'CZ' => __( 'Czechia', 'gravityforms' ),
			'DK' => __( 'Denmark', 'gravityforms' ),
			'DJ' => __( 'Djibouti', 'gravityforms' ),
			'DM' => __( 'Dominica', 'gravityforms' ),
			'DO' => __( 'Dominican Republic', 'gravityforms' ),
			'EC' => __( 'Ecuador', 'gravityforms' ),
			'EG' => __( 'Egypt', 'gravityforms' ),
			'SV' => __( 'El Salvador', 'gravityforms' ),
			'GQ' => __( 'Equatorial Guinea', 'gravityforms' ),
			'ER' => __( 'Eritrea', 'gravityforms' ),
			'EE' => __( 'Estonia', 'gravityforms' ),
			'SZ' => __( 'Eswatini', 'gravityforms' ),
			'ET' => __( 'Ethiopia', 'gravityforms' ),
			'FK' => __( 'Falkland Islands', 'gravityforms' ),
			'FO' => __( 'Faroe Islands', 'gravityforms' ),
			'FJ' => __( 'Fiji', 'gravityforms' ),
			'FI' => __( 'Finland', 'gravityforms' ),
			'FR' => __( 'France', 'gravityforms' ),
			'GF' => __( 'French Guiana', 'gravityforms' ),
			'PF' => __( 'French Polynesia', 'gravityforms' ),
			'TF' => __( 'French Southern Territories', 'gravityforms' ),
			'GA' => __( 'Gabon', 'gravityforms' ),
			'GM' => __( 'Gambia', 'gravityforms' ),
			'GE' => _x( 'Georgia', 'Country', 'gravityforms' ),
			'DE' => __( 'Germany', 'gravityforms' ),
			'GH' => __( 'Ghana', 'gravityforms' ),
			'GI' => __( 'Gibraltar', 'gravityforms' ),
			'GR' => __( 'Greece', 'gravityforms' ),
			'GL' => __( 'Greenland', 'gravityforms' ),
			'GD' => __( 'Grenada', 'gravityforms' ),
			'GP' => __( 'Guadeloupe', 'gravityforms' ),
			'GU' => __( 'Guam', 'gravityforms' ),
			'GT' => __( 'Guatemala', 'gravityforms' ),
			'GG' => __( 'Guernsey', 'gravityforms' ),
			'GN' => __( 'Guinea', 'gravityforms' ),
			'GW' => __( 'Guinea-Bissau', 'gravityforms' ),
			'GY' => __( 'Guyana', 'gravityforms' ),
			'HT' => __( 'Haiti', 'gravityforms' ),
			'HM' => __( 'Heard Island and McDonald Islands', 'gravityforms' ),
			'VA' => __( 'Holy See', 'gravityforms' ),
			'HN' => __( 'Honduras', 'gravityforms' ),
			'HK' => __( 'Hong Kong', 'gravityforms' ),
			'HU' => __( 'Hungary', 'gravityforms' ),
			'IS' => __( 'Iceland', 'gravityforms' ),
			'IN' => __( 'India', 'gravityforms' ),
			'ID' => __( 'Indonesia', 'gravityforms' ),
			'IR' => __( 'Iran', 'gravityforms' ),
			'IQ' => __( 'Iraq', 'gravityforms' ),
			'IE' => __( 'Ireland', 'gravityforms' ),
			'IM' => __( 'Isle of Man', 'gravityforms' ),
			'IL' => __( 'Israel', 'gravityforms' ),
			'IT' => __( 'Italy', 'gravityforms' ),
			'JM' => __( 'Jamaica', 'gravityforms' ),
			'JP' => __( 'Japan', 'gravityforms' ),
			'JE' => __( 'Jersey', 'gravityforms' ),
			'JO' => __( 'Jordan', 'gravityforms' ),
			'KZ' => __( 'Kazakhstan', 'gravityforms' ),
			'KE' => __( 'Kenya', 'gravityforms' ),
			'KI' => __( 'Kiribati', 'gravityforms' ),
			'KP' => __( "Korea, Democratic People's Republic of", 'gravityforms' ),
			'KR' => __( 'Korea, Republic of', 'gravityforms' ),
			'KW' => __( 'Kuwait', 'gravityforms' ),
			'KG' => __( 'Kyrgyzstan', 'gravityforms' ),
			'LA' => __( "Lao People's Democratic Republic", 'gravityforms' ),
			'LV' => __( 'Latvia', 'gravityforms' ),
			'LB' => __( 'Lebanon', 'gravityforms' ),
			'LS' => __( 'Lesotho', 'gravityforms' ),
			'LR' => __( 'Liberia', 'gravityforms' ),
			'LY' => __( 'Libya', 'gravityforms' ),
			'LI' => __( 'Liechtenstein', 'gravityforms' ),
			'LT' => __( 'Lithuania', 'gravityforms' ),
			'LU' => __( 'Luxembourg', 'gravityforms' ),
			'MO' => __( 'Macao', 'gravityforms' ),
			'MG' => __( 'Madagascar', 'gravityforms' ),
			'MW' => __( 'Malawi', 'gravityforms' ),
			'MY' => __( 'Malaysia', 'gravityforms' ),
			'MV' => __( 'Maldives', 'gravityforms' ),
			'ML' => __( 'Mali', 'gravityforms' ),
			'MT' => __( 'Malta', 'gravityforms' ),
			'MH' => __( 'Marshall Islands', 'gravityforms' ),
			'MQ' => __( 'Martinique', 'gravityforms' ),
			'MR' => __( 'Mauritania', 'gravityforms' ),
			'MU' => __( 'Mauritius', 'gravityforms' ),
			'YT' => __( 'Mayotte', 'gravityforms' ),
			'MX' => __( 'Mexico', 'gravityforms' ),
			'FM' => __( 'Micronesia', 'gravityforms' ),
			'MD' => __( 'Moldova', 'gravityforms' ),
			'MC' => __( 'Monaco', 'gravityforms' ),
			'MN' => __( 'Mongolia', 'gravityforms' ),
			'ME' => __( 'Montenegro', 'gravityforms' ),
			'MS' => __( 'Montserrat', 'gravityforms' ),
			'MA' => __( 'Morocco', 'gravityforms' ),
			'MZ' => __( 'Mozambique', 'gravityforms' ),
			'MM' => __( 'Myanmar', 'gravityforms' ),
			'NA' => __( 'Namibia', 'gravityforms' ),
			'NR' => __( 'Nauru', 'gravityforms' ),
			'NP' => __( 'Nepal', 'gravityforms' ),
			'NL' => __( 'Netherlands', 'gravityforms' ),
			'NC' => __( 'New Caledonia', 'gravityforms' ),
			'NZ' => __( 'New Zealand', 'gravityforms' ),
			'NI' => __( 'Nicaragua', 'gravityforms' ),
			'NE' => __( 'Niger', 'gravityforms' ),
			'NG' => __( 'Nigeria', 'gravityforms' ),
			'NU' => __( 'Niue', 'gravityforms' ),
			'NF' => __( 'Norfolk Island', 'gravityforms' ),
			'MK' => __( 'North Macedonia', 'gravityforms' ),
			'MP' => __( 'Northern Mariana Islands', 'gravityforms' ),
			'NO' => __( 'Norway', 'gravityforms' ),
			'OM' => __( 'Oman', 'gravityforms' ),
			'PK' => __( 'Pakistan', 'gravityforms' ),
			'PW' => __( 'Palau', 'gravityforms' ),
			'PS' => __( 'Palestine, State of', 'gravityforms' ),
			'PA' => __( 'Panama', 'gravityforms' ),
			'PG' => __( 'Papua New Guinea', 'gravityforms' ),
			'PY' => __( 'Paraguay', 'gravityforms' ),
			'PE' => __( 'Peru', 'gravityforms' ),
			'PH' => __( 'Philippines', 'gravityforms' ),
			'PN' => __( 'Pitcairn', 'gravityforms' ),
			'PL' => __( 'Poland', 'gravityforms' ),
			'PT' => __( 'Portugal', 'gravityforms' ),
			'PR' => __( 'Puerto Rico', 'gravityforms' ),
			'QA' => __( 'Qatar', 'gravityforms' ),
			'RE' => __( 'Réunion', 'gravityforms' ),
			'RO' => __( 'Romania', 'gravityforms' ),
			'RU' => __( 'Russian Federation', 'gravityforms' ),
			'RW' => __( 'Rwanda', 'gravityforms' ),
			'BL' => __( 'Saint Barthélemy', 'gravityforms' ),
			'SH' => __( 'Saint Helena, Ascension and Tristan da Cunha', 'gravityforms' ),
			'KN' => __( 'Saint Kitts and Nevis', 'gravityforms' ),
			'LC' => __( 'Saint Lucia', 'gravityforms' ),
			'MF' => __( 'Saint Martin', 'gravityforms' ),
			'PM' => __( 'Saint Pierre and Miquelon', 'gravityforms' ),
			'VC' => __( 'Saint Vincent and the Grenadines', 'gravityforms' ),
			'WS' => __( 'Samoa', 'gravityforms' ),
			'SM' => __( 'San Marino', 'gravityforms' ),
			'ST' => __( 'Sao Tome and Principe', 'gravityforms' ),
			'SA' => __( 'Saudi Arabia', 'gravityforms' ),
			'SN' => __( 'Senegal', 'gravityforms' ),
			'RS' => __( 'Serbia', 'gravityforms' ),
			'SC' => __( 'Seychelles', 'gravityforms' ),
			'SL' => __( 'Sierra Leone', 'gravityforms' ),
			'SG' => __( 'Singapore', 'gravityforms' ),
			'SX' => __( 'Sint Maarten', 'gravityforms' ),
			'SK' => __( 'Slovakia', 'gravityforms' ),
			'SI' => __( 'Slovenia', 'gravityforms' ),
			'SB' => __( 'Solomon Islands', 'gravityforms' ),
			'SO' => __( 'Somalia', 'gravityforms' ),
			'ZA' => __( 'South Africa', 'gravityforms' ),
			'GS' => _x( 'South Georgia and the South Sandwich Islands', 'Country', 'gravityforms' ),
			'SS' => __( 'South Sudan', 'gravityforms' ),
			'ES' => __( 'Spain', 'gravityforms' ),
			'LK' => __( 'Sri Lanka', 'gravityforms' ),
			'SD' => __( 'Sudan', 'gravityforms' ),
			'SR' => __( 'Suriname', 'gravityforms' ),
			'SJ' => __( 'Svalbard and Jan Mayen', 'gravityforms' ),
			'SE' => __( 'Sweden', 'gravityforms' ),
			'CH' => __( 'Switzerland', 'gravityforms' ),
			'SY' => __( 'Syria Arab Republic', 'gravityforms' ),
			'TW' => __( 'Taiwan', 'gravityforms' ),
			'TJ' => __( 'Tajikistan', 'gravityforms' ),
			'TZ' => __( 'Tanzania, the United Republic of', 'gravityforms' ),
			'TH' => __( 'Thailand', 'gravityforms' ),
			'TL' => __( 'Timor-Leste', 'gravityforms' ),
			'TG' => __( 'Togo', 'gravityforms' ),
			'TK' => __( 'Tokelau', 'gravityforms' ),
			'TO' => __( 'Tonga', 'gravityforms' ),
			'TT' => __( 'Trinidad and Tobago', 'gravityforms' ),
			'TN' => __( 'Tunisia', 'gravityforms' ),
			'TR' => __( 'Türkiye', 'gravityforms' ),
			'TM' => __( 'Turkmenistan', 'gravityforms' ),
			'TC' => __( 'Turks and Caicos Islands', 'gravityforms' ),
			'TV' => __( 'Tuvalu', 'gravityforms' ),
			'UG' => __( 'Uganda', 'gravityforms' ),
			'UA' => __( 'Ukraine', 'gravityforms' ),
			'AE' => __( 'United Arab Emirates', 'gravityforms' ),
			'GB' => __( 'United Kingdom', 'gravityforms' ),
			'US' => __( 'United States', 'gravityforms' ),
			'UY' => __( 'Uruguay', 'gravityforms' ),
			'UM' => __( 'US Minor Outlying Islands', 'gravityforms' ),
			'UZ' => __( 'Uzbekistan', 'gravityforms' ),
			'VU' => __( 'Vanuatu', 'gravityforms' ),
			'VE' => __( 'Venezuela', 'gravityforms' ),
			'VN' => __( 'Viet Nam', 'gravityforms' ),
			'VG' => __( 'Virgin Islands, British', 'gravityforms' ),
			'VI' => __( 'Virgin Islands, U.S.', 'gravityforms' ),
			'WF' => __( 'Wallis and Futuna', 'gravityforms' ),
			'EH' => __( 'Western Sahara', 'gravityforms' ),
			'YE' => __( 'Yemen', 'gravityforms' ),
			'ZM' => __( 'Zambia', 'gravityforms' ),
			'ZW' => __( 'Zimbabwe', 'gravityforms' ),
		);

		return $countries;
	}

	/**
	 * Returns the ISO 3166-1 alpha-2 code for the supplied country name.
	 *
	 * @since Unknown
	 * @since 3.0.3 Added the optional $bypass_is_code_check param.
	 *
	 * @param string $country_name         The country name.
	 * @param bool   $bypass_is_code_check Whether to bypass the check for $country_name already being an ISO 3166-1 alpha-2 code. Defaults to false.
	 *
	 * @return string
	 */
	public function get_country_code( $country_name, $bypass_is_code_check = false ) {
		if ( empty( $country_name ) ) {
			return '';
		}

		if ( ! $bypass_is_code_check && $this->is_country_code( $country_name ) ) {
			return $country_name;
		}

		$codes = $this->get_country_codes();

		return rgar( $codes, GFCommon::safe_strtoupper( $country_name ), '' );
	}

	/**
	 * Returns the default countries array updated to use the uppercase country name as the key to the ISO 3166-1 alpha-2 code.
	 *
	 * @since Unknown
	 * @since 2.4     Updated to use ISO 3166-1 list of countries.
	 * @since 2.4.20  Updated to use GF_Field_Address::get_default_countries().
	 *
	 * @return array
	 */
	public function get_country_codes() {
		static $countries;
		if ( empty( $countries ) ) {
			$countries = array_flip( array_map( array( 'GFCommon', 'safe_strtoupper' ), $this->get_default_countries() ) );
		}

		return $countries;
	}

	/**
	 * Checks if the given value is an ISO 3166-1 alpha-2 country code that exists in our default list of countries.
	 *
	 * @since 3.0.3
	 *
	 * @param string $value The value to check.
	 *
	 * @return bool
	 */
	public function is_country_code( $value ) {
		return ! empty( $value ) && array_key_exists( $value, $this->get_default_countries() );
	}

	/**
	 * Returns the country name for the given ISO 3166-1 alpha-2 country code.
	 *
	 * @since 3.0.3
	 *
	 * @param string $country_code The ISO 3166-1 alpha-2 country code.
	 *
	 * @return string
	 */
	public function get_country_name( $country_code ) {
		if ( empty( $country_code ) ) {
			return '';
		}

		return rgar( $this->get_default_countries(), $country_code, '' );
	}

	/**
	 * Returns the array of US states and territories.
	 *
	 * @since Unknown
	 *
	 * @return array The array of US states.
	 */
	public function get_us_states() {
		$states = array_values( $this->get_default_us_states() );

		/**
		 * Filters the US states array.
		 *
		 * @since Unknown
		 *
		 * @param array $states The array of US states.
		 */
		return gf_apply_filters( array( 'gform_us_states' ), $states );
	}

	/**
	 * Returns the default array of US states using the 2-character state codes as the keys.
	 *
	 * @since 3.0.3
	 *
	 * @return array
	 */
	public function get_default_us_states() {
		static $states;

		if ( ! empty( $states ) ) {
			return $states;
		}

		$states = array(
			'AL' => __( 'Alabama', 'gravityforms' ),
			'AK' => __( 'Alaska', 'gravityforms' ),
			'AS' => __( 'American Samoa', 'gravityforms' ),
			'AZ' => __( 'Arizona', 'gravityforms' ),
			'AR' => __( 'Arkansas', 'gravityforms' ),
			'CA' => __( 'California', 'gravityforms' ),
			'CO' => __( 'Colorado', 'gravityforms' ),
			'CT' => __( 'Connecticut', 'gravityforms' ),
			'DE' => __( 'Delaware', 'gravityforms' ),
			'DC' => __( 'District of Columbia', 'gravityforms' ),
			'FL' => __( 'Florida', 'gravityforms' ),
			'GA' => _x( 'Georgia', 'US State', 'gravityforms' ),
			'GU' => __( 'Guam', 'gravityforms' ),
			'HI' => __( 'Hawaii', 'gravityforms' ),
			'ID' => __( 'Idaho', 'gravityforms' ),
			'IL' => __( 'Illinois', 'gravityforms' ),
			'IN' => __( 'Indiana', 'gravityforms' ),
			'IA' => __( 'Iowa', 'gravityforms' ),
			'KS' => __( 'Kansas', 'gravityforms' ),
			'KY' => __( 'Kentucky', 'gravityforms' ),
			'LA' => __( 'Louisiana', 'gravityforms' ),
			'ME' => __( 'Maine', 'gravityforms' ),
			'MD' => __( 'Maryland', 'gravityforms' ),
			'MA' => __( 'Massachusetts', 'gravityforms' ),
			'MI' => __( 'Michigan', 'gravityforms' ),
			'MN' => __( 'Minnesota', 'gravityforms' ),
			'MS' => __( 'Mississippi', 'gravityforms' ),
			'MO' => __( 'Missouri', 'gravityforms' ),
			'MT' => __( 'Montana', 'gravityforms' ),
			'NE' => __( 'Nebraska', 'gravityforms' ),
			'NV' => __( 'Nevada', 'gravityforms' ),
			'NH' => __( 'New Hampshire', 'gravityforms' ),
			'NJ' => __( 'New Jersey', 'gravityforms' ),
			'NM' => __( 'New Mexico', 'gravityforms' ),
			'NY' => __( 'New York', 'gravityforms' ),
			'NC' => __( 'North Carolina', 'gravityforms' ),
			'ND' => __( 'North Dakota', 'gravityforms' ),
			'MP' => __( 'Northern Mariana Islands', 'gravityforms' ),
			'OH' => __( 'Ohio', 'gravityforms' ),
			'OK' => __( 'Oklahoma', 'gravityforms' ),
			'OR' => __( 'Oregon', 'gravityforms' ),
			'PA' => __( 'Pennsylvania', 'gravityforms' ),
			'PR' => __( 'Puerto Rico', 'gravityforms' ),
			'RI' => __( 'Rhode Island', 'gravityforms' ),
			'SC' => __( 'South Carolina', 'gravityforms' ),
			'SD' => __( 'South Dakota', 'gravityforms' ),
			'TN' => __( 'Tennessee', 'gravityforms' ),
			'TX' => __( 'Texas', 'gravityforms' ),
			'UT' => __( 'Utah', 'gravityforms' ),
			'VI' => __( 'U.S. Virgin Islands', 'gravityforms' ),
			'VT' => __( 'Vermont', 'gravityforms' ),
			'VA' => __( 'Virginia', 'gravityforms' ),
			'WA' => __( 'Washington', 'gravityforms' ),
			'WV' => __( 'West Virginia', 'gravityforms' ),
			'WI' => __( 'Wisconsin', 'gravityforms' ),
			'WY' => __( 'Wyoming', 'gravityforms' ),
			'AA' => __( 'Armed Forces Americas', 'gravityforms' ),
			'AE' => __( 'Armed Forces Europe', 'gravityforms' ),
			'AP' => __( 'Armed Forces Pacific', 'gravityforms' ),
		);

		return $states;
	}

	/**
	 * Returns the two-letter US state code from the state name provided.
	 *
	 * @since Unknown
	 *
	 * @param string $state_name The state name.
	 *
	 * @return string The two-letter US state code.
	 */
	public function get_us_state_code( $state_name ) {
		static $states;

		if ( empty( $states ) ) {
			$states = array_flip( array_map( array( 'GFCommon', 'safe_strtoupper' ), $this->get_default_us_states() ) );
		}

		return rgar( $states, GFCommon::safe_strtoupper( $state_name ), '' );
	}

	public function get_canadian_provinces() {
		static $provinces;

		if ( ! empty( $provinces ) ) {
			return $provinces;
		}

		$provinces = array(
			__( 'Alberta', 'gravityforms' ),
			__( 'British Columbia', 'gravityforms' ),
			__( 'Manitoba', 'gravityforms' ),
			__( 'New Brunswick', 'gravityforms' ),
			__( 'Newfoundland and Labrador', 'gravityforms' ),
			__( 'Northwest Territories', 'gravityforms' ),
			__( 'Nova Scotia', 'gravityforms' ),
			__( 'Nunavut', 'gravityforms' ),
			__( 'Ontario', 'gravityforms' ),
			__( 'Prince Edward Island', 'gravityforms' ),
			__( 'Quebec', 'gravityforms' ),
			__( 'Saskatchewan', 'gravityforms' ),
			__( 'Yukon', 'gravityforms' ),
		);

		return $provinces;
	}

	public function get_state_dropdown( $states, $selected_state = '', $placeholder = '' ) {
		$str = '';
		foreach ( $states as $code => $state ) {
			if ( is_array( $state ) ) {
				$str .= sprintf( '<optgroup label="%1$s">%2$s</optgroup>', esc_attr( $code ), $this->get_state_dropdown( $state, $selected_state, $placeholder ) );
			} else {
				if ( is_numeric( $code ) ) {
					$code = $state;
				}
				if ( empty( $state ) ) {
					$state = $placeholder;
				}

				$str .= $this->get_select_option( $code, $state, $selected_state );
			}
		}

		return $str;
	}

	/**
	 * Returns the option tag for the current choice.
	 *
	 * @param string $value The choice value.
	 * @param string $label The choice label.
	 * @param string $selected_value The value for the selected choice.
	 *
	 * @return string
	 */
	public function get_select_option( $value, $label, $selected_value ) {
		$selected = $value == $selected_value ? "selected='selected'" : '';

		return sprintf( "<option value='%s' %s>%s</option>", esc_attr( $value ), $selected, esc_html( $label ) );
	}

	public function get_us_state_dropdown( $selected_state = '' ) {
		$states = array_merge( array( '' ), $this->get_us_states() );
		$str    = '';
		foreach ( $states as $code => $state ) {
			if ( is_numeric( $code ) ) {
				$code = $state;
			}

			$selected = $code == $selected_state ? "selected='selected'" : '';
			$str .= "<option value='" . esc_attr( $code ) . "' $selected>" . esc_html( $state ) . '</option>';
		}

		return $str;
	}

	public function get_canadian_provinces_dropdown( $selected_province = '' ) {
		$states = array_merge( array( '' ), $this->get_canadian_provinces() );
		$str    = '';
		foreach ( $states as $state ) {
			$selected = $state == $selected_province ? "selected='selected'" : '';
			$str .= "<option value='" . esc_attr( $state ) . "' $selected>" . esc_html( $state ) . '</option>';
		}

		return $str;
	}

	public function get_country_dropdown( $selected_country = '', $placeholder = '' ) {
		$str = '';

		if ( ! $this->is_country_code( $selected_country ) ) {
			$selected_country = $this->get_country_code( $selected_country, true );
		}

		$selected_country = strtolower( $selected_country );
		$countries        = array_merge( array( '' ), $this->get_countries() );

		foreach ( $countries as $code => $country ) {
			if ( is_numeric( $code ) ) {
				$code = $country;
			}
			if ( empty( $country ) ) {
				$country = $placeholder;
			}
			$selected = $selected_country && strtolower( esc_attr( $code ) ) === $selected_country ? "selected='selected'" : '';

			$str .= "<option value='" . esc_attr( $code ) . "' $selected>" . esc_html( $country ) . '</option>';
		}

		return $str;
	}

	/**
	 * Format the entry value for when the field/input merge tag is processed. Not called for the {all_fields} merge tag.
	 *
	 * Returns the country name instead of the country code, unless the `code` modifier is used.
	 *
	 * @since 3.0.3
	 *
	 * @param string|array $value      The field value. Depending on the location the merge tag is being used the following functions may have already been applied to the value: esc_html, nl2br, and urlencode.
	 * @param string       $input_id   The field or input ID from the merge tag currently being processed.
	 * @param array        $entry      The Entry Object currently being processed.
	 * @param array        $form       The Form Object currently being processed.
	 * @param string       $modifier   The merge tag modifier. e.g. value
	 * @param string|array $raw_value  The raw field value from before any formatting was applied to $value.
	 * @param bool         $url_encode Indicates if the urlencode function may have been applied to the $value.
	 * @param bool         $esc_html   Indicates if the esc_html function may have been applied to the $value.
	 * @param string       $format     The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param bool         $nl2br      Indicates if the nl2br function may have been applied to the $value.
	 *
	 * @return string
	 */
	public function get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br ) {
		if ( $input_id !== "{$this->id}.6" || ! $this->is_country_code( $value ) || in_array( 'code', $this->get_modifiers() ) ) {
			return parent::get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br );
		}

		return GFCommon::format_variable_value( $this->get_country_name( $value ), $url_encode, $esc_html, $format, $nl2br );
	}

	/**
	 * Format the entry value for display on the entries list page.
	 *
	 * Returns the country name instead of the country code.
	 *
	 * @since 3.0.3
	 *
	 * @param string|array $value    The field value.
	 * @param array        $entry    The Entry Object currently being processed.
	 * @param string       $field_id The field or input ID currently being processed.
	 * @param array        $columns  The properties for the columns being displayed on the entry list page.
	 * @param array        $form     The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		if ( $field_id !== "{$this->id}.6" || ! $this->is_country_code( $value ) ) {
			return parent::get_value_entry_list( $value, $entry, $field_id, $columns, $form );
		}

		return esc_html( $this->get_country_name( $value ) );
	}

	/**
	 * Format the entry value for display on the entry detail page and for the {all_fields} merge tag.
	 *
	 * @since 1.9
	 * @since 2.9.29 Changed the second parameter $currency (string) to $entry (array).
	 *
	 * @param string|array $value    The field value.
	 * @param array        $entry    The entry.
	 * @param bool|false   $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param string       $format   The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param string       $media    The location where the value will be displayed. Possible values: screen or email.
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $entry = array(), $use_text = false, $format = 'html', $media = 'screen' ) {
		if ( is_array( $value ) ) {
			$street_value  = trim( rgget( $this->id . '.1', $value ) );
			$street2_value = trim( rgget( $this->id . '.2', $value ) );
			$city_value    = trim( rgget( $this->id . '.3', $value ) );
			$state_value   = trim( rgget( $this->id . '.4', $value ) );
			$zip_value     = trim( rgget( $this->id . '.5', $value ) );
			$country_value = trim( rgget( $this->id . '.6', $value ) );

			if ( $this->is_country_code( $country_value ) ) {
				$country_value = $this->get_country_name( $country_value );
			}

			if ( $format === 'html' ) {
				$street_value  = esc_html( $street_value );
				$street2_value = esc_html( $street2_value );
				$city_value    = esc_html( $city_value );
				$state_value   = esc_html( $state_value );
				$zip_value     = esc_html( $zip_value );
				$country_value = esc_html( $country_value );

				$line_break = '<br />';
			} else {
				$line_break = "\n";
			}

			/**
			 * Filters the format that the address is displayed in.
			 *
			 * @since Unknown
			 *
			 * @param string           'default' The format to use. Defaults to 'default'.
			 * @param GF_Field_Address $this     An instance of the GF_Field_Address object.
			 */
			$address_display_format = apply_filters( 'gform_address_display_format', 'default', $this );
			if ( $address_display_format == 'zip_before_city' ) {
				/*
                Sample:
                3333 Some Street
                suite 16
                2344 City, State
                Country
                */

				$addr_ary   = array();
				$addr_ary[] = $street_value;

				if ( ! empty( $street2_value ) ) {
					$addr_ary[] = $street2_value;
				}

				$zip_line = trim( $zip_value . ' ' . $city_value );
				$zip_line .= ! empty( $zip_line ) && ! empty( $state_value ) ? ", {$state_value}" : $state_value;
				$zip_line = trim( $zip_line );
				if ( ! empty( $zip_line ) ) {
					$addr_ary[] = $zip_line;
				}

				if ( ! empty( $country_value ) ) {
					$addr_ary[] = $country_value;
				}

				$address = implode( '<br />', $addr_ary );

			} else {
				$address = $street_value;
				$address .= ! empty( $address ) && ! empty( $street2_value ) ? $line_break . $street2_value : $street2_value;
				$address .= ! empty( $address ) && ( ! empty( $city_value ) || ! empty( $state_value ) ) ? $line_break . $city_value : $city_value;
				$address .= ! empty( $address ) && ! empty( $city_value ) && ! empty( $state_value ) ? ", $state_value" : $state_value;
				$address .= ! empty( $address ) && ! empty( $zip_value ) ? " $zip_value" : $zip_value;
				$address .= ! empty( $address ) && ! empty( $country_value ) ? $line_break . $country_value : $country_value;
			}

			// Adding map link.
			/**
			 * Disables the Google Maps link from displaying in the address field.
			 *
			 * @since 1.9
			 *
			 * @param bool false Determines if the map link should be disabled. Set to true to disable. Defaults to false.
			 */
			$map_link_disabled = apply_filters( 'gform_disable_address_map_link', false );
			if ( ! empty( $address ) && $format == 'html' && ! $map_link_disabled ) {
				$address_qs = str_replace( $line_break, ' ', $address ); //replacing <br/> and \n with spaces
				$address_qs = urlencode( $address_qs );
				$address .= "<br/><a href='https://maps.google.com/maps?q={$address_qs}' target='_blank' class='map-it-link'>Map It</a>";
			}

			return $address;
		} else {
			return '';
		}
	}

	public function sanitize_settings() {
		parent::sanitize_settings();
		if ( $this->addressType ) {
			$this->addressType = wp_strip_all_tags( $this->addressType );
		}

		if ( $this->defaultCountry ) {
			// Ensures the value is a valid country code, or sets it to an empty string.
			$this->defaultCountry = $this->get_country_code( $this->defaultCountry );
		}

		if ( $this->defaultProvince ) {
			$this->defaultProvince = wp_strip_all_tags( $this->defaultProvince );
		}

		if ( $this->copyValuesOptionLabel ) {
			$this->copyValuesOptionLabel = wp_strip_all_tags( $this->copyValuesOptionLabel );
		}

	}

	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
		if ( empty( $input_id ) ) {
			$input_id = $this->id;
		}

		if ( absint( $input_id ) == $input_id ) {
			$street_value  = str_replace( '  ', ' ', trim( rgar( $entry, $input_id . '.1' ) ) );
			$street2_value = str_replace( '  ', ' ', trim( rgar( $entry, $input_id . '.2' ) ) );
			$city_value    = str_replace( '  ', ' ', trim( rgar( $entry, $input_id . '.3' ) ) );
			$state_value   = str_replace( '  ', ' ', trim( rgar( $entry, $input_id . '.4' ) ) );
			$zip_value     = trim( rgar( $entry, $input_id . '.5' ) );
			$country_value = $this->get_country_code( trim( rgar( $entry, $input_id . '.6' ) ) );

			$address = $street_value;
			$address .= ! empty( $address ) && ! empty( $street2_value ) ? "  $street2_value" : $street2_value;
			$address .= ! empty( $address ) && ( ! empty( $city_value ) || ! empty( $state_value ) ) ? ", $city_value," : $city_value;
			$address .= ! empty( $address ) && ! empty( $city_value ) && ! empty( $state_value ) ? "  $state_value" : $state_value;
			$address .= ! empty( $address ) && ! empty( $zip_value ) ? "  $zip_value," : $zip_value;
			$address .= ! empty( $address ) && ! empty( $country_value ) ? "  $country_value" : $country_value;

			return $address;
		} else {

			return rgar( $entry, $input_id );
		}
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

	// # FIELD FILTER UI HELPERS ---------------------------------------------------------------------------------------

	/**
	 * Returns the sub-filters for the current field.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_sub_filters() {
		$sub_filters = array();
		$inputs      = $this->inputs;

		foreach ( $inputs as $input ) {
			if ( rgar( $input, 'isHidden' ) ) {
				continue;
			}

			$sub_filters[] = array(
				'key'             => rgar( $input, 'id' ),
				'text'            => rgar( $input, 'customLabel', rgar( $input, 'label' ) ),
				'preventMultiple' => false,
				'operators'       => $this->get_filter_operators(),
			);
		}

		return $sub_filters;
	}

	/**
	 * Returns the filter operators for the current field.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_operators() {
		$operators   = parent::get_filter_operators();
		$operators[] = 'contains';

		return $operators;
	}

	/**
	 * Indicates if state validation should be skipped if the submitted value is blank.
	 *
	 * @since 3.0
	 *
	 * @param string|int $key The field or input ID.
	 *
	 * @return bool
	 */
	public function skip_state_validation_if_blank( $key ) {
		$id = $this->id;
		switch ( $key ) {
			case "{$id}.4":
				return ! $this->get_input_property( 4, 'isHidden' );
			case "{$id}.6":
				return ! ( rgar( $this->get_selected_address_type(), 'country' ) || $this->get_input_property( 6, 'isHidden' ) );
		}

		return false;
	}

	/**
	 * Prepares the value that will be hashed on form display as part of the state.
	 *
	 * @since 3.0
	 *
	 * @param string|array $value The default value.
	 *
	 * @return null|array
	 */
	public function get_values_for_state_hash( $value ) {
		$id     = $this->id;
		$type   = $this->get_selected_address_type();
		$return = array();

		if ( ! empty( $type['states'] ) && is_array( $type['states'] ) ) {
			$state_id            = "{$id}.4";
			$state_input         = GFFormsModel::get_input( $this, $state_id );
			$return[ $state_id ] = rgar( $state_input, 'isHidden' ) ? rgar( $value, $state_id, $this->defaultState ) : $this->get_choices_for_state_hash( $type['states'] );
		}

		$country_id    = "{$id}.6";
		$country_input = GFFormsModel::get_input( $this, $country_id );
		if ( ! empty( $type['country'] ) ) {
			$return[ $country_id ] = $this->get_country_code( $type['country'] );
		} elseif ( rgar( $country_input, 'isHidden' ) ) {
			$return[ $country_id ] = $this->get_country_code( rgar( $value, $country_id, $this->defaultCountry ) );
		} else {
			$return[ $country_id ] = $this->get_choices_for_state_hash( $this->get_countries() );
		}

		return $return;
	}

	/**
	 * Prepares the array of choice values for the state hash.
	 *
	 * @since 3.0
	 *
	 * @param null|array $choices Optional. The choices to parse or null to use the field choices property.
	 *
	 * @return array
	 */
	protected function get_choices_for_state_hash( $choices = null ) {
		$values = array();
		foreach ( $choices as $key => $choice ) {
			$values[] = is_numeric( $key ) ? $choice : $key;
		}

		return $values;
	}

	/**
	 * Actions to be performed after the field has been converted to an object.
	 *
	 * @since 3.0.3
	 *
	 * @return void
	 */
	public function post_convert_field() {
		parent::post_convert_field();

		if ( $this->defaultCountry ) {
			// Updates the property to use the country code, if it's still using the country name.
			$code = $this->get_country_code( $this->defaultCountry );
			if ( empty( $code ) && get_user_locale() !== 'en_US' ) {
				// If we couldn't get a code for the country name, try again after translating it.
				$translated = translate( $this->defaultCountry, 'gravityforms' ); // phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction, WordPress.WP.I18n.NonSingularStringLiteralText
				$code       = $translated !== $this->defaultCountry ? $this->get_country_code( $translated ) : '';
			}
			$this->defaultCountry = $code;
		}
	}

}

GF_Fields::register( new GF_Field_Address() );
