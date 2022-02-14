<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

// Load base classes.
require_once 'class-checkbox.php';
require_once 'class-select.php';

class Checkbox_And_Select extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'checkbox_and_select';

	/**
	 * Child inputs.
	 *
	 * @since 2.5
	 *
	 * @var Base[]
	 */
	public $inputs = array();

	/**
	 * Initialize Checbox and Select field.
	 *
	 * @since 2.5
	 *
	 * @param array                                $props    Field properties.
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {

		parent::__construct( $props, $settings );

		// Prepare Checkbox field.
		$checkbox_input = rgars( $props, 'checkbox' );
		$checkbox_field = array(
			'type'       => 'checkbox',
			'name'       => rgar( $props, 'name' ) . 'Enable',
			'label'      => esc_html__( 'Enable', 'gravityforms' ),
			'horizontal' => true,
			'value'      => '1',
			'choices'    => false,
			'tooltip'    => false,
		);
		$this->inputs['checkbox'] = wp_parse_args( $checkbox_input, $checkbox_field );
		$this->inputs['checkbox'] = Fields::create( $this->inputs['checkbox'], $this->settings );

		// Prepare Select field.
		$select_input           = rgars( $props, 'select' );
		$select_field           = array(
			'name'    => rgar( $props, 'name' ) . 'Value',
			'type'    => 'select',
			'class'   => '',
			'tooltip' => false,
			'disabled' => $this->inputs['checkbox']->get_value() ? false : true,
		);
		$select_field['class']  .= ' ' . $select_field['name'];
		$this->inputs['select'] = wp_parse_args( $select_input, $select_field );

		// Add on change event to Checkbox.
		if ( empty( $this->inputs['checkbox']['choices'] ) ) {
			$this->inputs['checkbox']['choices'] = array(
				array(
					'name'     => $this->inputs['checkbox']['name'],
					'label'    => $this->inputs['checkbox']['label'],
					'onchange' => sprintf(
						"( function( $, elem ) {
						$( elem ).parents( 'td' ).css( 'position', 'relative' );
						if( $( elem ).prop( 'checked' ) ) {
							$( '%1\$s' ).prop( 'disabled', false );
						} else {
							$( '%1\$s' ).prop( 'disabled', true );
						}
					} )( jQuery, this );",
						"#{$this->inputs['select']['name']}Span select" ),
				),
			);
		}
		$this->inputs['select'] = Fields::create( $this->inputs['select'], $this->settings );
	}





	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function markup() {

		// Prepare markup.
		// Display description.
		$html = $this->get_description();

		// Settings are more up-to-date at this point; set the disabled attr on the select based on checkbox state.
		$this->inputs['select']->disabled = ! $this->inputs['checkbox']->get_value();

		$html .= sprintf(
			'<span class="%s">%s <span id="%s" class="gform-settings-input__target">%s %s</span></span>',
			esc_attr( $this->get_container_classes() ),
			$this->inputs['checkbox']->markup(),
			$this->inputs['select']->name . 'Span',
			$this->inputs['select']->markup(),
			$this->settings->maybe_get_tooltip( $this->inputs['select'] )
		);

		$html .= $this->get_error_icon();

		return $html;

	}

	/**
	 * Get the correctly-grouped values from $_POST for use in validation.
	 *
	 * @since 2.5
	 *
	 * @param array $values The $_POST values.
	 *
	 * @return array
	 */
	public function get_values_from_post( $values ) {
		$return_values = array();
		$cb_name       = $this->inputs['checkbox']->name;
		$select_name   = $this->inputs['select']->name;

		if ( isset( $values[ $cb_name ] ) ) {
			$return_values['checkbox'] = $values[ $cb_name ];
		}

		if ( isset( $values[ $select_name ] ) ) {
			$return_values['select'] = $values[ $select_name ];
		}

		return $return_values;
	}

	/**
	 * Filter out unneeded select values when the checkbox isn't checked.
	 *
	 * @since 2.5
	 *
	 * @param array             $field_values Posted field values.
	 * @param array|bool|string $field_value  Posted value for field.
	 *
	 * @return array
	 */
	public function save_field( $field_values, $field_value ) {
		$field_values = parent::save_field( $field_values, $field_value );

		$cb_value = isset( $field_values[ $this->inputs['checkbox']->name ] ) ? $field_values[ $this->inputs['checkbox']->name ] : 0;

		// Checkbox is unchecked, remove the select value.
		if ( $cb_value == 0 ) {
			unset( $field_values[ $this->inputs['select']->name ] );
		}

		return $field_values;
	}

	// # VALIDATION METHODS --------------------------------------------------------------------------------------------

	/**
	 * Validate posted field value.
	 *
	 * @since 2.5-beta-3
	 *
	 * @param array $values Posted field values.
	 */
	public function do_validation( $values ) {
		$cb_value     = isset( $values['checkbox'] ) ? $values['checkbox'] : null;
		$select_value = isset( $values['select'] ) ? $values['select'] : null;

		if ( ! isset( $cb_value[0] ) || $cb_value[0] != 1 ) {
			return;
		}

		if ( isset( $cb_value[0] ) && $cb_value[0] == 1 ) {
			$this->inputs['select']->required = true;
		}

		$this->inputs['checkbox']->handle_validation( $cb_value );
		$this->inputs['select']->handle_validation( $select_value );
	}

}

Fields::register( 'checkbox_and_select', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Checkbox_and_Select' );
