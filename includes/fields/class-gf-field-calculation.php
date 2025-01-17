<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Calculation extends GF_Field {

	public $type = 'calculation';

	function get_form_editor_field_settings() {
		return array(
			'disable_quantity_setting',
			'rules_setting',
			'duplicate_setting',
			'calculation_setting',
			'conditional_logic_field_setting',
		);
	}

	public function get_form_editor_button() {
		return array();
	}

	public function validate( $value, $form ) {
		$quantity_id = $this->id . '.3';
		$quantity    = rgget( $quantity_id, $value );

		if ( $this->isRequired && rgblank( $quantity ) && ! $this->disableQuantity ) {
			$this->failed_validation  = true;
			$this->validation_message = empty($this->errorMessage) ? esc_html__( 'This field is required.', 'gravityforms' ) : $this->errorMessage;
		} elseif ( ! empty( $quantity ) && ( ! is_numeric( $quantity ) || intval( $quantity ) != floatval( $quantity ) || intval( $quantity ) < 0 ) ) {
			$this->failed_validation  = true;
			$this->validation_message = esc_html__( 'Please enter a valid quantity', 'gravityforms' );
		}
	}

	/**
	 * Get the field inputs.
	 *
	 * @since unknown
	 * @since 2.5     Add accessibility enhancements.
	 *
	 * @param array  $form  The form object.
	 * @param string $value The field value.
	 * @param array  $entry The entry object.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id          = $form['id'];
		$is_entry_detail  = $this->is_entry_detail();
		$is_form_editor   = $this->is_form_editor();
		$is_legacy_markup = GFCommon::is_legacy_markup_enabled( $form );

		$id          = (int) $this->id;
		$field_id    = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$product_name = ! is_array( $value ) || empty( $value[ $this->id . '.1' ] ) ? esc_attr( $this->label ) : esc_attr( $value[ $this->id . '.1' ] );
		$price        = ! is_array( $value ) || empty( $value[ $this->id . '.2' ] ) ? $this->basePrice : esc_attr( $value[ $this->id . '.2' ] );
		$quantity     = is_array( $value ) ? esc_attr( $value[ $this->id . '.3' ] ) : '';

		if ( empty( $price ) ) {
			$price = 0;
		}

		$has_quantity = sizeof( GFCommon::get_product_fields_by_type( $form, array( 'quantity' ), $this->id ) ) > 0;
		if ( $has_quantity ) {
			$this->disableQuantity = true;
		}

		$currency = $is_entry_detail && ! empty( $entry ) ? $entry['currency'] : '';

		$quantity_field = '';
		$disabled_text  = $is_form_editor ? 'disabled="disabled"' : '';

		$product_quantity_sub_label = $this->get_product_quantity_label( $form_id );

		if ( $is_entry_detail || $is_form_editor  ) {
			$style          = $this->disableQuantity ? "style='display:none;'" : '';
			$quantity_field = " <label for='ginput_quantity_{$form_id}_{$this->id}' class='ginput_quantity_label gform-field-label' {$style}>{$product_quantity_sub_label}</label> <input type='number' name='input_{$id}.3' value='{$quantity}' id='ginput_quantity_{$form_id}_{$this->id}' class='ginput_quantity' size='10' min='0' {$disabled_text} />";
		} elseif ( ! $this->disableQuantity ) {
			$tabindex                  = $this->get_tabindex();
			$describedby_extra_id = array();
			if ( ! $is_legacy_markup ) {
				$describedby_extra_id = array( "ginput_product_price_{$this->formId}_{$this->id}" );
			}
			$quantity_aria_describedby = $this->get_aria_describedby( $describedby_extra_id );
			$quantity_field            .= " <label for='input_{$form_id}_{$this->id}_1' class='ginput_quantity_label gform-field-label' aria-hidden='true'>" . $product_quantity_sub_label . "</label> <input type='number' name='input_{$id}.3' value='{$quantity}' id='input_{$form_id}_{$this->id}_1' class='ginput_quantity' size='10' min='0' {$tabindex} {$disabled_text} {$quantity_aria_describedby} />";
		} else {
			if ( ! is_numeric( $quantity ) ) {
				$quantity = 1;
			}

			if ( ! $has_quantity ) {
				$quantity_field .= "<input type='hidden' name='input_{$id}.3' value='{$quantity}' class='ginput_quantity_{$form_id}_{$this->id} gform_hidden' />";
			}
		}

		$wrapper_open  = $is_legacy_markup ? '' : "<div id='ginput_product_price_{$form_id}_{$this->id}' class='ginput_product_price_wrapper'>";
		$wrapper_close = $is_legacy_markup ? '' : '</div>';

		return "<div class='ginput_container ginput_container_product_calculation'>
					<input type='hidden' name='input_{$id}.1' value='{$product_name}' class='gform_hidden' />
					$wrapper_open
						<label class='gform-field-label gform-field-label--type-sub-large ginput_product_price_label'>" . gf_apply_filters( array( 'gform_product_price', $form_id, $this->id ), esc_html__( 'Price', 'gravityforms' ), $form_id ) . ":</label>
						<label class='gform-field-label gform-field-label--type-sub-large ginput_product_price' id='{$field_id}'>" . esc_html( GFCommon::to_money( $price, $currency ) ) . "</label>
					$wrapper_close
					<input type='hidden' name='input_{$id}.2' id='ginput_base_price_{$form_id}_{$this->id}' class='gform_hidden ginput_calculated_price' value='" . esc_attr( $price ) . "'/>
					{$quantity_field}
				</div>";
	}

	/**
	 * Retrieve the field label.
	 *
	 * @since 2.5
	 *
	 * @param bool   $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param string $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 *
	 * @return string
	 */
	public function get_field_label( $force_frontend_label, $value ) {
		$field_label = parent::get_field_label( $force_frontend_label, $value );

		// Checking the defined product name.
		if ( ! rgempty( $this->id . '.1', $value ) ) {
			$field_label = rgar( $value, $this->id . '.1' );
		}

		if ( $this->disableQuantity || ! $this->get_context_property( 'rendering_form' )  ) {
			$label = esc_html( $field_label );
		} else {
			$product_quantity_sub_label = $this->get_product_quantity_label( $this->formId );
			$label                      = '<span class="gfield_label_product gform-field-label">' . esc_html( $field_label ) . '</span>' . ' <span class="screen-reader-text">' . $product_quantity_sub_label . '</span>';
		}

		return $label;
	}

	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		if ( is_array( $value ) && ! empty( $value ) ) {
			$product_name = trim( $value[ $this->id . '.1' ] );
			$price        = trim( $value[ $this->id . '.2' ] );
			$quantity     = trim( $value[ $this->id . '.3' ] );

			$product = $product_name . ', ' . esc_html__( 'Qty: ', 'gravityforms' ) . $quantity . ', ' . esc_html__( 'Price: ', 'gravityforms' ) . $price;

			return $product;
		} else {
			return '';
		}
	}

	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {
		// ignore submitted value and recalculate price in backend
		list( $prefix, $field_id, $input_id ) = rgexplode( '_', $input_name, 3 );
		if ( $input_id == 2 ) {
			$currency = new RGCurrency( GFCommon::get_currency() );
			$lead     = empty( $lead ) ? RGFormsModel::get_lead( $lead_id ) : $lead;
			$value    = $currency->to_money( GFCommon::calculate( $this, $form, $lead ) );
		}
		return $value;
	}

	public function sanitize_settings() {
		parent::sanitize_settings();
		$this->enableCalculation = (bool) $this->enableCalculation;

	}


}

GF_Fields::register( new GF_Field_Calculation() );
