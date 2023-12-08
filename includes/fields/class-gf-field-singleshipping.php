<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_SingleShipping extends GF_Field {

	public $type = 'singleshipping';

	/**
	 * Indicates if this field supports state validation.
	 *
	 * @since 2.5.11
	 *
	 * @var bool
	 */
	protected $_supports_state_validation = true;

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @since 2.8
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--shipping';
	}

	function get_form_editor_field_settings() {
		return array(
			'base_price_setting',
		);
	}

	public function get_form_editor_button() {
		return array();
	}

	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id       = (int) $this->id;
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$currency = $is_entry_detail && ! empty( $entry ) ? $entry['currency'] : '';

		$price = ! empty( $value ) ? $value : $this->basePrice;
		if ( empty( $price ) ) {
			$price = 0;
		}
		$price = esc_attr( GFCommon::to_money( $price ) );

		return "<div class='ginput_container ginput_container_singleshipping'>
					<input type='text' readonly class='ginput_shipping_price gform-text-input-reset' id='{$field_id}' name='input_{$id}' value='{$price}' />
				</div>";
	}

	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		return GFCommon::to_money( $value, $currency );
	}

	public function sanitize_settings() {
		parent::sanitize_settings();
		$price_number    = GFCommon::to_number( $this->basePrice );
		$this->basePrice = GFCommon::to_money( $price_number );
	}

	public function get_value_default() {
		$value = $this->is_form_editor() ? $this->defaultValue : GFCommon::replace_variables_prepopulate( $this->defaultValue );
		if( rgblank( $value ) ) {
			$value = $this->basePrice;
		}
		return $value;
	}

	/**
	 * Actions to be performed after the field has been converted to an object.
	 *
	 * @since 2.4.8.2
	 */
	public function post_convert_field() {
		parent::post_convert_field();

		// Ensure the choices property is not an array to prevent issues with some features such as the conditional logic reset to default.
		$this->choices = null;
	}

}

GF_Fields::register( new GF_Field_SingleShipping() );
