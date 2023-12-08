<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_CreditCard extends GF_Field {

	public $type = 'creditcard';

	public function get_form_editor_field_title() {
		return esc_attr__( 'Credit Card', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to enter credit card information.', 'gravityforms' );
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
		return 'gform-icon--credit-card';
	}

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'force_ssl_field_setting',
			'credit_card_style_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'sub_labels_setting',
			'sub_label_placement_setting',
			'label_placement_setting',
			'admin_label_setting',
			'rules_setting',
			'description_setting',
			'css_class_setting',
			'credit_card_setting',
			'input_placeholders_setting',
		);
	}

	public function get_form_editor_button() {
		return array(); // this button is conditionally added in the form detail page
	}

	/**
	 * Defines the IDs of required inputs.
	 *
	 * @since 2.5
	 *
	 * @return string[]
	 */
	public function get_required_inputs_ids() {
		return array( '1', '2_month', '2_year', '3' );
	}

	/**
	 * Generates an array that contains aria-describedby attribute for each input.
	 *
	 * Depending on each input's validation state, aria-describedby takes the value of the validation message container ID, the description only or nothing.
	 *
	 * @since 2.5
	 *
	 * @param array        $required_inputs_ids IDs of required field inputs.
	 * @param array|string $values              Inputs values.
	 *
	 * @return array
	 */
	public function get_inputs_describedby_attributes( $required_inputs_ids, $values ) {

		$describedby_attributes = array();
		$warning_container_id   = ! GFCommon::is_ssl() && ! ( $this->is_form_editor() || $this->is_entry_detail() ) ? "field_{$this->formId}_{$this->id}_creditcard_warning_message" : '';

		foreach ( $this->inputs as $input ) {
			$input_id    = str_replace( $this->id . '.', '', $input['id'] );
			$input_value = GFForms::get( $input['id'], $values );
			if ( ! empty( $_POST[ 'is_submit_' . $this->formId ] ) && $this->isRequired && in_array( $input_id, $required_inputs_ids ) &&  empty( $input_value ) ) {
				$describedby_attributes[ $input_id ] = "aria-describedby='validation_message_{$this->formId}_{$this->id} {$warning_container_id}'";
			} else {
				$describedby_attributes[ $input_id ] = empty( $warning_container_id ) ? '' : "aria-describedby='{$warning_container_id}'";
			}
		}

		return $describedby_attributes;
	}

	public function validate( $value, $form ) {
		$card_number     = rgpost( 'input_' . $this->id . '_1' );
		$expiration_date = rgpost( 'input_' . $this->id . '_2' );
		$security_code   = rgpost( 'input_' . $this->id . '_3' );

		if ( $this->isRequired && ( empty( $card_number ) || empty( $security_code ) || empty( $expiration_date[0] ) || empty( $expiration_date[1] ) ) ) {
			$this->failed_validation  = true;
			$this->validation_message = empty( $this->errorMessage ) ? esc_html__( 'Please enter your credit card information.', 'gravityforms' ) : $this->errorMessage;
		} elseif ( ! empty( $card_number ) ) {
			$card_type     = GFCommon::get_card_type( $card_number );

			if ( empty( $security_code ) ) {
				$this->failed_validation  = true;
				$this->validation_message = esc_html__( "Please enter your card's security code.", 'gravityforms' );
			} elseif ( ! $card_type ) {
				$this->failed_validation  = true;
				$this->validation_message = esc_html__( 'Invalid credit card number.', 'gravityforms' );
			} elseif ( ! $this->is_card_supported( $card_type['slug'] ) ) {
				$this->failed_validation  = true;
				$this->validation_message = $card_type['name'] . ' ' . esc_html__( 'is not supported. Please enter one of the supported credit cards.', 'gravityforms' );
			}
		}
	}

	public function is_card_supported( $card_slug ) {
		$supported_cards = $this->creditCards;
		$default_cards   = array( 'amex', 'discover', 'mastercard', 'visa' );

		if ( ! empty( $supported_cards ) && in_array( $card_slug, $supported_cards ) ) {
			return true;
		} elseif ( empty( $supported_cards ) && in_array( $card_slug, $default_cards ) ) {
			return true;
		}

		return false;

	}

	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		if ( $get_from_post_global_var ) {
			$value[ $this->id . '.1' ] = $this->get_input_value_submission( 'input_' . $this->id . '_1', rgar( $this->inputs[0], 'name' ), $field_values, true );
			$value[ $this->id . '.2' ] = $this->get_input_value_submission( 'input_' . $this->id . '_2', rgar( $this->inputs[1], 'name' ), $field_values, true );
			$value[ $this->id . '.3' ] = $this->get_input_value_submission( 'input_' . $this->id . '_3', rgar( $this->inputs[3], 'name' ), $field_values, true );
			$value[ $this->id . '.4' ] = $this->get_input_value_submission( 'input_' . $this->id . '_4', rgar( $this->inputs[4], 'name' ), $field_values, true );
			$value[ $this->id . '.5' ] = $this->get_input_value_submission( 'input_' . $this->id . '_5', rgar( $this->inputs[5], 'name' ), $field_values, true );
		} else {
			$value = $this->get_input_value_submission( 'input_' . $this->id, $this->inputName, $field_values, $get_from_post_global_var );
		}

		return $value;
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
	 * Displays an insecure page warning below the field content.
	 *
	 * @since 2.5
	 *
	 * @param string|array $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param bool         $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param array        $form                 The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_field_content( $value, $force_frontend_label, $form ) {

		$is_form_editor  = GFCommon::is_form_editor();
		$is_entry_detail = GFCommon::is_entry_detail();
		$is_admin        = $is_form_editor || $is_entry_detail;

		// Get existing field content.
		$field_content = parent::get_field_content( $value, $force_frontend_label, $form );

		// If SSL is not used, display warning message.
		if ( ! GFCommon::is_ssl() && ! $is_admin ) {
			$field_content = "<div class='gfield_description gfield_validation_message gfield_creditcard_warning_message' id='field_{$form['id']}_{$this->id}_creditcard_warning_message'><span>" . esc_html__( 'This page is unsecured. Do not enter a real credit card number! Use this field only for testing purposes. ', 'gravityforms' ) . '</span></div>' . $field_content;
		}

		return $field_content;

	}

	public function get_field_input( $form, $value = '', $entry = null ) {
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$form_id  = $form['id'];
		$id       = intval( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$form_id  = ( $is_entry_detail || $is_form_editor ) && empty( $form_id ) ? rgget( 'id' ) : $form_id;

		$disabled_text = $is_form_editor ? "disabled='disabled'" : '';
		$class_suffix  = $is_entry_detail ? '_admin' : '';


		$form_sub_label_placement = rgar( $form, 'subLabelPlacement' );
		$field_sub_label_placement = $this->subLabelPlacement;
		$is_sub_label_above       = $field_sub_label_placement == 'above' || ( empty( $field_sub_label_placement ) && $form_sub_label_placement == 'above' );
		$sub_label_class          = $field_sub_label_placement == 'hidden_label' ? "hidden_sub_label screen-reader-text" : '';

		$card_number      = '';
		$card_name        = '';
		$expiration_month = '';
		$expiration_year  = '';
		$security_code    = '';
		$autocomplete     = "autocomplete='off'";

		if ( is_array( $value ) ) {
			$card_number     = esc_attr( rgget( $this->id . '.1', $value ) );
			$card_name       = esc_attr( rgget( $this->id . '.5', $value ) );
			$expiration_date = rgget( $this->id . '.2', $value );
			if ( ! is_array( $expiration_date ) && ! empty( $expiration_date ) ) {
				$expiration_date = explode( '/', $expiration_date );
			}

			if ( is_array( $expiration_date ) && count( $expiration_date ) == 2 ) {
				$expiration_month = $expiration_date[0];
				$expiration_year  = $expiration_date[1];
			}

			$security_code = esc_attr( rgget( $this->id . '.3', $value ) );
		}

		$action = ! ( $is_entry_detail || $is_form_editor ) ? "gformMatchCard(\"{$field_id}_1\");" : '';

		$onchange = "onchange='{$action}'";
		$onkeyup  = "onkeyup='{$action}'";

		$card_icons         = '';
		$cards              = GFCommon::get_card_types();
		$enabled_card_names = array();

		foreach ( $cards as $card ) {

			$style = '';
			if ( $this->is_card_supported( $card['slug'] ) ) {
				$print_card           = true;
				$enabled_card_names[] = rgar( $card, 'name' );
			} elseif ( $is_form_editor || $is_entry_detail ) {
				$print_card = true;
				$style      = "style='display:none;'";
			} else {
				$print_card = false;
			}

			if ( $print_card ) {
				$card_icons .= "<div class='gform_card_icon gform_card_icon_{$card['slug']}' {$style}>{$card['name']}</div>";
			}
		}

		$payment_methods = apply_filters( 'gform_payment_methods', array(), $this, $form_id );
		$payment_options = '';
		if ( is_array( $payment_methods ) ) {
			foreach ( $payment_methods as $payment_method ) {
				$checked = rgpost( 'gform_payment_method' ) == $payment_method['key'] ? "checked='checked'" : '';
				$payment_options .= "<div class='gform_payment_option gform_payment_{$payment_method['key']}'><input type='radio' name='gform_payment_method' value='{$payment_method['key']}' id='gform_payment_method_{$payment_method['key']}' onclick='gformToggleCreditCard();' onkeypress='gformToggleCreditCard();' {$checked}/> {$payment_method['label']}</div>";
			}
		}
		$checked           = rgpost( 'gform_payment_method' ) == 'creditcard' || rgempty( 'gform_payment_method' ) ? "checked='checked'" : '';
		$card_radio_button = empty( $payment_options ) ? '' : "<input type='radio' name='gform_payment_method' id='gform_payment_method_creditcard' value='creditcard' onclick='gformToggleCreditCard();' onkeypress='gformToggleCreditCard();' {$checked}/>";
		$card_describer    = sprintf(
			"<span class='screen-reader-text' id='field_%d_%d_supported_creditcards'>%s %s</span>",
			$form_id,
			$this->id,
			esc_html__( 'Supported Credit Cards:', 'gravityforms' ),
			implode( ', ', $enabled_card_names )
		);
		$card_icons        = "{$payment_options}<div class='gform_card_icon_container'>{$card_radio_button}{$card_icons}{$card_describer}</div>";

		// Aria attributes.
		$number_aria_attributes = $this->get_aria_attributes( $value, '1' );
		$expiration_month_aria_attributes = $this->get_aria_attributes( $value, '2_month' );
		$expiration_year_aria_attributes = $this->get_aria_attributes( $value, '2_year' );
		$security_aria_attributes = $this->get_aria_attributes( $value, '3' );
		$name_aria_attributes = $this->get_aria_attributes( $value, '5' );

		//card number fields
		$tabindex                = $this->get_tabindex();
		$card_number_field_input = GFFormsModel::get_input( $this, $this->id . '.1' );
		$validation_helper       = ! is_admin() ? "pattern='[0-9]*' title='" . esc_attr__( 'Only digits are allowed', 'gravityforms' ) . "'" : '';
		$card_number_label       = rgar( $card_number_field_input, 'customLabel' ) != '' ? $card_number_field_input['customLabel'] : esc_html__( 'Card Number', 'gravityforms' );
		$card_number_label       = gf_apply_filters( array( 'gform_card_number', $form_id ), $card_number_label, $form_id );

		$card_number_placeholder = $this->get_input_placeholder_attribute( $card_number_field_input );
		if ( $is_sub_label_above ) {
			$card_field = "<span class='ginput_full{$class_suffix} gform-grid-col' id='{$field_id}_1_container' >
                                    {$card_icons}
                                    <label for='{$field_id}_1' id='{$field_id}_1_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$card_number_label}</label>
                                    <input type='text' name='input_{$id}.1' id='{$field_id}_1' value='{$card_number}' {$tabindex} {$disabled_text} {$onchange} {$onkeyup} {$autocomplete} {$validation_helper} {$card_number_placeholder} {$number_aria_attributes}/>
                                 </span>";
		} else {
			$card_field = "<span class='ginput_full{$class_suffix} gform-grid-col' id='{$field_id}_1_container' >
                                    {$card_icons}
                                    <input type='text' name='input_{$id}.1' id='{$field_id}_1' value='{$card_number}' {$tabindex} {$disabled_text} {$onchange} {$onkeyup} {$autocomplete} {$validation_helper} {$card_number_placeholder} {$number_aria_attributes}/>
                                    <label for='{$field_id}_1' id='{$field_id}_1_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$card_number_label}</label>
                                 </span>";
		}

		// Expiration Date Inputs
		$expiration_wrapper_tag       = $this->get_field_container_tag( $form ) === 'fieldset' ? 'fieldset' : 'span';
		$expiration_label_tag         = $expiration_wrapper_tag === 'fieldset' ? 'legend' : 'label';
		$expiration_label_for         = $expiration_label_tag === 'label' ? " for='{$field_id}_2_month'" : '';
		$expiration_month_input       = GFFormsModel::get_input( $this, $this->id . '.2_month' );

		$expiration_label             = rgar( $expiration_month_input, 'customLabel' ) != '' ? esc_html( $expiration_month_input['customLabel'] ) : esc_html__( 'Expiration Date', 'gravityforms' );
		$expiration_label             = gf_apply_filters( array( 'gform_card_expiration', $form_id ), $expiration_label, $form_id );

		// Expiration Date: Month
		$expiration_month_label       = $expiration_wrapper_tag === 'fieldset' ? "<label for='{$field_id}_2_month' class='gform-field-label gform-field-label--type-sub screen-reader-text'>" . esc_html__( 'Month', 'gravityforms' ) . "</label>" : '';
		$expiration_month_tab_index   = $this->get_tabindex();
		$expiration_month_placeholder = $this->get_input_placeholder_value( $expiration_month_input );
		$expiration_months            = $this->get_expiration_months( $expiration_month, $expiration_month_placeholder );

		// Expiration Date: Year
		$expiration_year_input        = GFFormsModel::get_input( $this, $this->id . '.2_year' );
		$expiration_year_tab_index    = $this->get_tabindex();
		$expiration_year_placeholder  = $this->get_input_placeholder_value( $expiration_year_input );
		$expiration_years             = $this->get_expiration_years( $expiration_year, $expiration_year_placeholder );

		if ( $expiration_wrapper_tag === 'fieldset' ) {
			$expiration_year_label_for = $is_form_editor ? '' : $field_id . '_2_year';
			$expiration_year_label     = "<label for='{$expiration_year_label_for}' class='gform-field-label gform-field-label--type-sub screen-reader-text'>" . esc_html__( 'Year', 'gravityforms' ) . "</label>";
		} else {
			$expiration_year_label = '';
		}

		// legend tag should be the first child of fieldset, so we are putting out this markup for fieldset
		// even if the sub-label is below or hidden.
		if ( $expiration_wrapper_tag === 'fieldset' || $is_sub_label_above ) {
			$expiration_field = "<span class='ginput_full{$class_suffix} ginput_cardextras gform-grid-col gform-grid-row' id='{$field_id}_2_container'>
                                            <{$expiration_wrapper_tag} class='ginput_cardinfo_left{$class_suffix} gform-grid-col' id='{$field_id}_2_cardinfo_left'>
                                            <{$expiration_label_tag}{$expiration_label_for} class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$expiration_label}</{$expiration_label_tag}>
                                                <span class='ginput_card_expiration_container ginput_card_field gform-grid-row'>
                                                   " . ( $expiration_wrapper_tag === 'fieldset' ? "<span class='ginput_card_expiration_month_container gform-grid-col'>" : "" ) . "
                                                       {$expiration_month_label}
                                                       <select name='input_{$id}.2[]' id='{$field_id}_2_month' {$expiration_month_tab_index} {$disabled_text} class='ginput_card_expiration ginput_card_expiration_month' {$expiration_month_aria_attributes}>
                                                           {$expiration_months}
                                                       </select>
                                                   " . ( $expiration_wrapper_tag === 'fieldset' ? "</span>" : "" ) . "
                                                   " . ( $expiration_wrapper_tag === 'fieldset' ? "<span class='ginput_card_expiration_year_container gform-grid-col'>" : "" ) . "
                                                       {$expiration_year_label}
                                                       <select name='input_{$id}.2[]' id='{$field_id}_2_year' {$expiration_year_tab_index} {$disabled_text} class='ginput_card_expiration ginput_card_expiration_year' {$expiration_year_aria_attributes}>
                                                           {$expiration_years}
                                                       </select>
                                                   " . ( $expiration_wrapper_tag === 'fieldset' ? "</span>" : "" ) . "
                                                </span>
                                            </{$expiration_wrapper_tag}>";

		} else {
			$expiration_field = "<span class='ginput_full{$class_suffix} ginput_cardextras gform-grid-col gform-grid-row' id='{$field_id}_2_container'>
                                            <{$expiration_wrapper_tag} class='ginput_cardinfo_left{$class_suffix}' id='{$field_id}_2_cardinfo_left'>
                                                <span class='ginput_card_expiration_container ginput_card_field'>
                                                    " . ( $expiration_wrapper_tag === 'fieldset' ? "<span class='ginput_card_expiration_month_container'>" : "" ) . "
                                                       <select name='input_{$id}.2[]' id='{$field_id}_2_month' {$expiration_month_tab_index} {$disabled_text} class='ginput_card_expiration ginput_card_expiration_month' {$expiration_month_aria_attributes}>
                                                           {$expiration_months}
                                                       </select>
                                                       {$expiration_month_label}
                                                   " . ( $expiration_wrapper_tag === 'fieldset' ? "</span>" : "" ) . "
                                                   " . ( $expiration_wrapper_tag === 'fieldset' ? "<span class='ginput_card_expiration_year_container'>" : "" ) . "
                                                       <select name='input_{$id}.2[]' id='{$field_id}_2_year' {$expiration_year_tab_index} {$disabled_text} class='ginput_card_expiration ginput_card_expiration_year' {$expiration_year_aria_attributes}>
                                                           {$expiration_years}
                                                       </select>
                                                       {$expiration_year_label}
                                                       " . ( $expiration_wrapper_tag === 'fieldset' ? "</span>" : "" ) . "
                                                </span>
                                                 <{$expiration_label_tag}{$expiration_label_for} class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$expiration_label}</{$expiration_label_tag}>
                                            </{$expiration_wrapper_tag}>";
		}
		//security code field
		$tabindex                  = $this->get_tabindex();
		$security_code_field_input = GFFormsModel::get_input( $this, $this->id . '.3' );
		$security_code_label       = rgar( $security_code_field_input, 'customLabel' ) != '' ? $security_code_field_input['customLabel'] : esc_html__( 'Security Code', 'gravityforms' );
		$security_code_label       = gf_apply_filters( array( 'gform_card_security_code', $form_id ), $security_code_label, $form_id );
		$validation_helper         = "pattern='[0-9]*' title='" . esc_attr__( 'Only digits are allowed', 'gravityforms' ) . "'";
		$security_code_placeholder = $this->get_input_placeholder_attribute( $security_code_field_input );
		if ( $is_sub_label_above ) {
			$security_field = "<span class='ginput_cardinfo_right{$class_suffix} gform-grid-col' id='{$field_id}_2_cardinfo_right'>
                                                <label for='{$field_id}_3' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>$security_code_label</label>
                                                <input type='text' name='input_{$id}.3' id='{$field_id}_3' {$tabindex} {$disabled_text} class='ginput_card_security_code' value='{$security_code}' {$autocomplete} {$validation_helper} {$security_code_placeholder} {$security_aria_attributes}/>
                                                <span class='ginput_card_security_code_icon'>&nbsp;</span>
                                             </span>
                                        </span>";
		} else {
			$security_field = "<span class='ginput_cardinfo_right{$class_suffix} gform-grid-col' id='{$field_id}_2_cardinfo_right'>
                                                <input type='text' name='input_{$id}.3' id='{$field_id}_3' {$tabindex} {$disabled_text} class='ginput_card_security_code' value='{$security_code}' {$autocomplete} {$validation_helper} {$security_code_placeholder} {$security_aria_attributes}/>
                                                <span class='ginput_card_security_code_icon'>&nbsp;</span>
                                                <label for='{$field_id}_3' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>$security_code_label</label>
                                             </span>
                                        </span>";
		}

		$tabindex              = $this->get_tabindex();
		$card_name_field_input = GFFormsModel::get_input( $this, $this->id . '.5' );
		$card_name_label       = rgar( $card_name_field_input, 'customLabel' ) != '' ? $card_name_field_input['customLabel'] : esc_html__( 'Cardholder Name', 'gravityforms' );
		$card_name_label       = gf_apply_filters( array( 'gform_card_name', $form_id ), $card_name_label, $form_id );

		$card_name_placeholder = $this->get_input_placeholder_attribute( $card_name_field_input );
		if ( $is_sub_label_above ) {
			$card_name_field = "<span class='ginput_full{$class_suffix} gform-grid-col' id='{$field_id}_5_container'>
                                            <label for='{$field_id}_5' id='{$field_id}_5_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$card_name_label}</label>
                                            <input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$card_name}' {$tabindex} {$disabled_text} {$card_name_placeholder} {$name_aria_attributes}/>
                                        </span>";
		} else {
			$card_name_field = "<span class='ginput_full{$class_suffix} gform-grid-col' id='{$field_id}_5_container'>
                                            <input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$card_name}' {$tabindex} {$disabled_text} {$card_name_placeholder} {$name_aria_attributes}/>
                                            <label for='{$field_id}_5' id='{$field_id}_5_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$card_name_label}</label>
                                        </span>";
		}

		return "<div class='ginput_complex{$class_suffix} ginput_container ginput_container_creditcard gform-grid-row' id='{$field_id}'>" . $card_field . $expiration_field . $security_field . $card_name_field . ' </div>';

	}

	/**
	 * Get the options for the Expiration Date Month drop down.
	 *
	 * @since Unknown
	 * @since 2.5 Added $included_placeholder parameter.
	 *
	 * @param string $selected_month The currently selected month.
	 * @param string $placeholder    Placeholder text.
	 *
	 * @return string
	 */
	private function get_expiration_months( $selected_month, $placeholder ) {

		$str = '';

		if ( empty( $placeholder ) ) {
			$placeholder = esc_html__( 'Month', 'gravityforms' );
		}

		$str .= "<option value=''>{$placeholder}</option>";

		for ( $i = 1; $i < 13; $i ++ ) {
			$selected = intval( $selected_month ) == $i ? "selected='selected'" : '';
			$month    = str_pad( $i, 2, '0', STR_PAD_LEFT );
			$str .= "<option value='{$i}' {$selected}>{$month}</option>";
		}

		return $str;

	}

	/**
	 * Get the options for the Expiration Date Year drop down.
	 *
	 * @since Unknown
	 * @since 2.5 Added $included_placeholder parameter.
	 *
	 * @param string $selected_year The currently selected year.
	 * @param string $placeholder   Placeholder text.
	 *
	 * @return string
	 */
	private function get_expiration_years( $selected_year, $placeholder ) {

		$str = '';

		if ( empty( $placeholder ) ) {
			$placeholder = esc_html__( 'Year', 'gravityforms' );
		}

		$str .= "<option value=''>{$placeholder}</option>";

		$year = intval( date( 'Y' ) );

		for ( $i = $year; $i < ( $year + 20 ); $i ++ ) {
			$selected = intval( $selected_year ) == $i ? "selected='selected'" : '';
			$str .= "<option value='{$i}' {$selected}>{$i}</option>";
		}

		return $str;

	}

	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {

		if ( is_array( $value ) ) {
			$card_number = trim( rgget( $this->id . '.1', $value ) );
			$card_type   = trim( rgget( $this->id . '.4', $value ) );
			$separator   = $format == 'html' ? '<br/>' : "\n";

			return empty( $card_number ) ? '' : $card_type . $separator . $card_number;
		} else {
			return '';
		}
	}

	public function get_form_inline_script_on_page_render( $form ) {

		$field_id = "input_{$form['id']}_{$this->id}";

		if ( $this->forceSSL && ! GFCommon::is_ssl() && ! GFCommon::is_preview() ) {
			$script = "document.location.href='" . esc_js( RGFormsModel::get_current_page_url( true ) ) . "';";
		} else {
			$script = "jQuery(document).ready(function(){ { gformMatchCard(\"{$field_id}_1\"); } } );";
		}

		$card_rules = $this->get_credit_card_rules();
		$script     = "if(!window['gf_cc_rules']){window['gf_cc_rules'] = new Array(); } window['gf_cc_rules'] = " . GFCommon::json_encode( $card_rules ) . "; $script";

		return $script;
	}

	public function get_credit_card_rules() {

		$cards = GFCommon::get_card_types();
		//$supported_cards = //TODO: Only include enabled cards
		$rules = array();

		foreach ( $cards as $card ) {
			$prefixes = explode( ',', $card['prefixes'] );
			foreach ( $prefixes as $prefix ) {
				$rules[ $card['slug'] ][] = $prefix;
			}
		}

		return $rules;
	}

	public function get_entry_inputs() {
		$inputs = array();
		// only store month and card number input values
		foreach ( $this->inputs as $input ) {
			if ( in_array( $input['id'], array( $this->id . '.1', $this->id . '.4' ) ) ) {
				$inputs[] = $input;
			}
		}

		return $inputs;
	}

	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {

		//saving last 4 digits of credit card
		list( $input_token, $field_id_token, $input_id ) = rgexplode( '_', $input_name, 3 );
		if ( $input_id == '1' ) {
			$value              = str_replace( ' ', '', $value );
			$card_number_length = strlen( $value );
			$value              = substr( $value, - 4, 4 );
			$value              = str_pad( $value, $card_number_length, 'X', STR_PAD_LEFT );
		} elseif ( $input_id == '4' ) {

			$value = rgpost( "input_{$field_id_token}_4" );

			if ( ! $value ) {
				$card_number = rgpost( "input_{$field_id_token}_1" );
				$card_type   = GFCommon::get_card_type( $card_number );
				$value       = $card_type ? $card_type['name'] : '';
			}
		} else {
			$value = '';
		}

		return $this->sanitize_entry_value( $value, $form['id'] );
	}

	/**
	 * Upgrades inputs, if needed.
	 *
	 * @since  2.1.2.7
	 * @access public
	 * @see    GF_Field::post_convert_field()
	 *
	 * @uses GF_Field::post_convert_field()
	 * @uses GF_Field_CreditCard::maybe_upgrade_inputs()
	 *
	 * @return void
	 */
	public function post_convert_field() {
		parent::post_convert_field();
		$this->maybe_upgrade_inputs();
	}

	/**
	 * GF1.8 and earlier used 5 inputs (1 input for the expiration date); GF1.9 changed to 6 inputs (the expiration month and year now separate); upgrade those fields still using the older configuration.
	 */
	public function maybe_upgrade_inputs() {
		$inputs    = $this->inputs;
		$exp_input = $inputs[1];
		$exp_id    = $this->id . '.2';

		if ( count( $inputs ) == 5 && $exp_input['id'] == $exp_id ) {
			$new_inputs = array(
				array(
					'id'           => $exp_id . '_month',
					'label'        => esc_html__( 'Expiration Month', 'gravityforms' ),
					'defaultLabel' => $exp_input['label']
				),
				array(
					'id'    => $exp_id . '_year',
					'label' => esc_html__( 'Expiration Year', 'gravityforms' ),
				)
			);

			array_splice( $inputs, 1, 1, $new_inputs );
			$this->inputs = $inputs;
		}
	}
}

GF_Fields::register( new GF_Field_CreditCard() );
