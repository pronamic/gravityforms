<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_SingleProduct extends GF_Field {

	public $type = 'singleproduct';

	/**
	 * Indicates if this field supports state validation.
	 *
	 * @since 2.5.11
	 *
	 * @var bool
	 */
	protected $_supports_state_validation = true;

	function get_form_editor_field_settings() {
		return array(
			'base_price_setting',
			'disable_quantity_setting',
			'conditional_logic_field_setting',
			'error_message_setting',
			'label_setting',
			'admin_label_setting',
			'rules_setting',
			'duplicate_setting',
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

	public function get_value_default() {
		$value = array();
		if ( is_array( $this->inputs ) ) {
			foreach ( $this->inputs as $index => $input ) {
				$input_value = $this->is_form_editor() ? rgar( $input, 'defaultValue' ) : GFCommon::replace_variables_prepopulate( rgar( $input, 'defaultValue' ) );
				if ( rgblank( $input_value ) && $input['id'] == "{$this->id}.2" ) {
					$input_value = $this->basePrice;
				}
				$value[ strval( $input['id'] ) ] = $input_value;
			}
		}
		return $value;
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

		$price = GFCommon::to_money( $price );

		$has_quantity = sizeof( GFCommon::get_product_fields_by_type( $form, array( 'quantity' ), $this->id ) ) > 0;
		if ( $has_quantity ) {
			$this->disableQuantity = true;
		}

		$currency = $is_entry_detail && ! empty( $entry ) ? $entry['currency'] : '';

		$quantity_field = '';
		$disabled_text  = $is_form_editor ? 'disabled="disabled"' : '';

		$qty_input_type = GFFormsModel::is_html5_enabled() ? 'number' : 'text';

		$qty_min_attr = GFFormsModel::is_html5_enabled() ? "min='0'" : '';

		$product_quantity_sub_label = $this->get_product_quantity_label( $form_id );

		if ( $is_entry_detail || $is_form_editor  ) {
			$style          = $this->disableQuantity ? "style='display:none;'" : '';
			$quantity_field = " <span class='ginput_quantity_label gform-field-label' {$style}>{$product_quantity_sub_label}</span> <input type='{$qty_input_type}' name='input_{$id}.3' value='{$quantity}' id='ginput_quantity_{$form_id}_{$this->id}' class='ginput_quantity' size='10' {$disabled_text} {$style}/>";
		} else if ( ! $this->disableQuantity ) {
			$tabindex                  = $this->get_tabindex();

			$describedby_extra_id = array();
			if ( ! $is_legacy_markup ) {
				$describedby_extra_id = array( "ginput_product_price_{$this->formId}_{$this->id}" );
			}

			$quantity_aria_describedby = $this->get_aria_describedby( $describedby_extra_id );
			$quantity_aria_label       = sprintf( 'aria-label="%s %s"', __( 'Quantity', 'gravityforms' ), $product_name );

			$quantity_field            .= " <span class='ginput_quantity_label gform-field-label' aria-hidden='true'>" . $product_quantity_sub_label . "</span> <input type='{$qty_input_type}' name='input_{$id}.3' value='{$quantity}' id='input_{$form_id}_{$this->id}_1' class='ginput_quantity' size='10' {$qty_min_attr} {$tabindex} {$disabled_text} {$quantity_aria_label} {$quantity_aria_describedby} />";
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
		if ( $has_quantity ) {
			$product_aria_describedby = $this->get_aria_describedby();
			return "<div class='ginput_container ginput_container_singleproduct'>
					<input type='hidden' name='input_{$id}.1' value='{$product_name}' class='gform_hidden' />
					$wrapper_open
						<label for='ginput_price_{$form_id}_{$this->id}_2' class='gform-field-label gform-field-label--type-sub-large ginput_product_price_label'>" . gf_apply_filters( array( 'gform_product_price', $form_id, $this->id ), esc_html__( 'Price', 'gravityforms' ), $form_id ) . ":</label>
						<input type='text' readonly name='input_{$id}.2' class='ginput_product_price gform-text-input-reset' id='ginput_base_price_{$form_id}_{$this->id}' value='" . esc_attr( $price ) . "' {$product_aria_describedby} />
					$wrapper_close
					{$quantity_field}
				</div>";
		} else if ( $this->disableQuantity ) {
			$product_aria_describedby = $this->get_aria_describedby();
			return "<div class='ginput_container ginput_container_singleproduct'>
					<input type='hidden' name='input_{$id}.1' value='{$product_name}' class='gform_hidden' />
					$wrapper_open
						<label for='ginput_base_price_{$form_id}_{$this->id}' class='gform-field-label gform-field-label--type-sub-large ginput_product_price_label'>" . gf_apply_filters( array( 'gform_product_price', $form_id, $this->id ), esc_html__( 'Price', 'gravityforms' ), $form_id ) . ":</label>
						<input type='text' readonly class='ginput_product_price gform-text-input-reset' name='input_{$id}.2' id='ginput_base_price_{$form_id}_{$this->id}' class='gform_hidden' value='" . esc_attr( $price ) . "' aria-label='{$product_name} " . esc_html__( 'Price', 'gravityforms' ) . "' {$product_aria_describedby} />
					$wrapper_close
					{$quantity_field}
				</div>";
		} else {
			return "<div class='ginput_container ginput_container_singleproduct'>
					<input type='hidden' name='input_{$id}.1' value='{$product_name}' class='gform_hidden' />
					$wrapper_open
						<span class='gform-field-label gform-field-label--type-sub-large ginput_product_price_label'>" . gf_apply_filters( array( 'gform_product_price', $form_id, $this->id ), esc_html__( 'Price', 'gravityforms' ), $form_id ) . ":</span>
						<span class='gform-field-label gform-field-label--type-sub-large ginput_product_price' id='{$field_id}'>" . esc_html( GFCommon::to_money( $price, $currency ) ) . "</span>
					$wrapper_close
					<input type='hidden' name='input_{$id}.2' id='ginput_base_price_{$form_id}_{$this->id}' class='gform_hidden' value='" . esc_attr( $price ) . "'/>
					{$quantity_field}
				</div>";
		}
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

		if ( $this->disableQuantity || ! $this->get_context_property( 'rendering_form' ) ) {
			$label = esc_html( $field_label );
		} else {
			$product_quantity_sub_label = $this->get_product_quantity_label( $this->formId );
			$label                      = '<span class="gform-field-label gfield_label_product">' . esc_html( $field_label ) . '</span>' . ' <span class="screen-reader-text">' . $product_quantity_sub_label . '</span>';
		}
		return $label;
	}

	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		if ( is_array( $value ) && ! empty( $value ) ) {
			$product_name = trim( $value[ $this->id . '.1' ] );
			$price        = trim( $value[ $this->id . '.2' ] );
			$quantity     = trim( $value[ $this->id . '.3' ] );

			$product_details = $product_name;

			if ( ! rgblank( $quantity ) ) {
				$product_details .= ', ' . esc_html__( 'Qty: ', 'gravityforms' ) . $quantity;
			}

			if ( ! rgblank( $price ) ) {
				$product_details .= ', ' . esc_html__( 'Price: ', 'gravityforms' ) . GFCommon::format_number( $price, 'currency', $currency );
			}

			return $product_details;
		} else {
			return '';
		}
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

GF_Fields::register( new GF_Field_SingleProduct() );
