<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Total extends GF_Field {

	public $type         = 'total';
	public $numberFormat = 'currency'; // This is used to property format the total during conditional logic evaluation.

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'label_setting',
			'admin_label_setting',
			'label_placement_setting',
			'description_setting',
			'css_class_setting',
		);
	}

	public function get_form_editor_field_title() {
		return esc_attr__( 'Total', 'gravityforms' );
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
		return 'gform-icon--total';
	}

	/**
	 * Returns the HTML markup for the field's containing element.
	 *
	 * @since 2.5
	 *
	 * @param array $atts Container attributes.
	 * @param array $form The current Form object.
	 *
	 * @return string
	 */
	public function get_field_container( $atts, $form ) {

		// aria-atomic and aria-live need to be added to make the change of the total announced.
		$atts['aria-atomic'] = 'true';
		$atts['aria-live']   = 'polite';

		return parent::get_field_container( $atts, $form );

	}

	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id          = (int) $this->id;
		$field_id    = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		if ( $is_entry_detail ) {
			return "<div class='ginput_container ginput_container_total'>
						<input type='text' name='input_{$id}' value='{$value}' />
					</div>";
		} else {
			if ( GFCommon::is_legacy_markup_enabled( $form ) ) {
				return "<div class='ginput_container ginput_container_total'>
							<span class='ginput_total ginput_total_{$form_id}'>" . GFCommon::to_money( '0' ) . "</span>
							<input type='hidden' name='input_{$id}' id='{$field_id}' class='gform_hidden'/>
						</div>";
			} else {
				return "<div class='ginput_container ginput_container_total'>
							<input type='text' readonly name='input_{$id}' id='{$field_id}' value='" . GFCommon::to_money( '0' ) . "' class='gform-text-input-reset ginput_total ginput_total_{$form_id}' />
						</div>";
			}
		}
	}

	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		return GFCommon::to_money( $value, $currency );
	}

	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {
		$lead  = empty( $lead ) ? RGFormsModel::get_lead( $lead_id ) : $lead;
		$value = GFCommon::get_order_total( $form, $lead );

		return $value;
	}

	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		return GFCommon::to_money( $value, $entry['currency'] );
	}

	/**
	 * Gets merge tag values.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFCommon::to_number()
	 * @uses GFCommon::to_money()
	 * @uses GFCommon::format_variable_value()
	 *
	 * @param array|string $value      The value of the input.
	 * @param string       $input_id   The input ID to use.
	 * @param array        $entry      The Entry Object.
	 * @param array        $form       The Form Object
	 * @param string       $modifier   The modifier passed.
	 * @param array|string $raw_value  The raw value of the input.
	 * @param bool         $url_encode If the result should be URL encoded.
	 * @param bool         $esc_html   If the HTML should be escaped.
	 * @param string       $format     The format that the value should be.
	 * @param bool         $nl2br      If the nl2br function should be used.
	 *
	 * @return string The processed merge tag.
	 */
	public function get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br ) {
		$format_numeric = in_array( 'price', $this->get_modifiers() );

		$value = $format_numeric ? GFCommon::to_number( $value ) : GFCommon::to_money( $value );

		return GFCommon::format_variable_value( $value, $url_encode, $esc_html, $format );
	}

	/**
	 * Validates the field value.
	 *
	 * @since 2.8.2
	 *
	 * @param string $value The submitted value.
	 * @param array  $form  The form currently being validated.
	 *
	 * @return void
	 */
	public function validate( $value, $form ) {
		if ( ! $this->validateTotal ) {
			return;
		}

		// API requests, such as the one used by Convo Forms, are not currently supported.
		if ( GFFormDisplay::get_submission_context() !== 'form-submit' ) {
			return;
		}

		$entry = GFFormsModel::get_current_lead( $form );
		if ( empty( $entry ) ) {
			return;
		}

		$currency_code  = rgar( $entry, 'currency' );
		$currency       = new RGCurrency( $currency_code );
		$expected_value = GFCommon::get_order_total( $form, $entry );
		$clean_value    = GFCommon::to_number( $value, $currency_code );

		if ( $currency->is_zero_decimal() ) {
			$expected_value_int = (int) $expected_value;
			$clean_value_int    = (int) $clean_value;
		} else {
			$expected_value_int = (int) round( $expected_value * 100 );
			$clean_value_int    = (int) round( $clean_value * 100 );
		}

		if ( $expected_value_int === $clean_value_int ) {
			return;
		}

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Amount mismatch (%s - #%d). Submitted: %s. Clean (int): %s. Expected (int): %s.', $this->label, $this->id, var_export( $value, true ), var_export( $clean_value_int, true ), var_export( $expected_value_int, true ) ) );

		$this->failed_validation  = true;
		$this->validation_message = sprintf( esc_html__( 'Submitted value (%s) does not match expected value (%s).', 'gravityforms' ), $clean_value ? GFCommon::to_money( $clean_value, $currency_code ) : esc_html( $value ), GFCommon::to_money( $expected_value, $currency_code ) );
	}

	/**
	 * Sanitizes the field properties.
	 *
	 * @since 2.8.2
	 *
	 * @return void
	 */
	public function sanitize_settings() {
		parent::sanitize_settings();
		if ( isset( $this->validateTotal ) ) {
			$this->validateTotal = (bool) $this->validateTotal;
		}
	}

}

GF_Fields::register( new GF_Field_Total() );
