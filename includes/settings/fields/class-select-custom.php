<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use GFCommon;
use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

// Load base class.
require_once 'class-select.php';
require_once 'class-text.php';

class Select_Custom extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'select_custom';

	/**
	 * Child inputs.
	 *
	 * @since 2.5
	 *
	 * @var Base[]
	 */
	public $inputs = array();

	/**
	 * Initialize Field Select field.
	 *
	 * @since 2.5
	 *
	 * @param array                                $props    Field properties.
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {

		parent::__construct( $props, $settings );

		// Prevent description from showing up on all sub-fields.
		unset( $props['description'] );

		// Prepare Custom input.
		$this->inputs['custom']          = $props;
		$this->inputs['custom']['type']  = 'text';
		$this->inputs['custom']['name']  .= '_custom';

		// Prepare Select input.
		$this->inputs['select']             = $props;
		$this->inputs['select']['type']     = 'select';
		$this->inputs['select']['onchange'] = '';

		// Set custom option flag.
		$has_custom_option = false;

		// Loop through choices, search for custom option.
		foreach ( $this->inputs['select']['choices'] as $choice ) {

			// If choice is a custom option, set flag and stop loop.
			if ( 'gf_custom' === rgar( $choice, 'name' ) || 'gf_custom' === rgar( $choice, 'value' ) ) {
				$has_custom_option = true;
				break;
			}

			// Check sub-choices, if present.
			if ( rgar( $choice, 'choices' ) ) {
				foreach ( $choice['choices'] as $subchoice ) {
					if ( 'gf_custom' === rgar( $subchoice, 'name' ) || 'gf_custom' === rgar( $subchoice, 'value' ) ) {
						$has_custom_option = true;
						break;
					}
				}
			}

		}

		// If no custom option exists, add it.
		if ( ! $has_custom_option ) {

			// Prepare label.
			if ( rgar( $this->inputs['select'], 'label' ) ) {
				$custom_label = sprintf(
					'%s %s',
					esc_html__( 'Add Custom', 'gravityforms' ),
					esc_html( $this->inputs['select']['label'] )
				);
			} else {
				$custom_label = esc_html__( 'Add Custom Value', 'gravityforms' );
			}

			// Add custom option.
			$this->inputs['select']['choices'][] = array(
				'label' => $custom_label,
				'value' => 'gf_custom',
			);

		}

		/**
		 * Prepare input fields.
		 *
		 * @var array $input
		 */
		foreach ( $this->inputs as &$input ) {
			$input = Fields::create( $input, $this->settings );
		}

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

		// Get selected option, initialize custom input display.
		$selected_option      = $this->inputs['select']->get_value();
		$custom_input_display = '';

		// If selected option is the custom option or the only option is the custom option, hide select field.
		if ( $selected_option === 'gf_custom' || ( 1 === count( $this->inputs['select']->choices ) && $this->inputs['select']->choices[0]['value'] === 'gf_custom' ) ) {
			$this->inputs['select']->style = 'display:none;';
		} else {
			$custom_input_display = 'style="display:none;"';
		}

		// Prepare markup.
		$html = $this->get_description();

		$html .= sprintf(
			'<span class="%s">%s<div class="gform-settings-select-custom__custom" %s>%s%s</div>%s</span>',
			esc_attr( $this->get_container_classes() ),
			$this->inputs['select']->markup(),
			$custom_input_display,
			count( $this->inputs['select']->choices ) > 1 ? '<button type="button" class="gform-settings-select-custom__reset"><span class="screen-reader-text">' . esc_html__( 'Reset', 'gravityforms' ) . '</span></button>' : '',
			$this->inputs['custom']->markup(),
			$this->get_error_icon()
		);

		return $html;

	}





	// # VALIDATION METHODS --------------------------------------------------------------------------------------------

	/**
	 * Validate posted field value.
	 *
	 * @since 2.5
	 *
	 * @param array $value Posted field values.
	 */
	public function do_validation( $value ) {

		// If field has no choices, exit.
		if ( empty( $this->inputs['select']->choices ) ) {
			return;
		}

		// Get values for both inputs.
		$select_value = $value;
		$custom_value = rgar( $this->settings->get_posted_values(), $this->inputs['custom']->name );

		// If field is required and no choice was selected, set field error.
		if ( $this->required && rgblank( $select_value ) ) {
			$this->inputs['select']->set_error();
			return;
		}

		// If field is required and no custom value was submitted, set field error.
		if ( $this->required && $select_value === 'gf_custom' && rgblank( $custom_value ) ) {
			$this->inputs['select']->set_error();
			return;
		}

		// If a custom choice was not selected, validate selected choice.
		if ( $select_value === 'gf_custom' ) {

			// Loop through field choices, determine if valid.
			foreach ( $this->inputs['select']->choices as $choice ) {

				// If choice has nested choices, loop through and determine if valid.
				if ( isset( $choice['choices'] ) ) {

					foreach ( $choice['choices'] as $optgroup_choice ) {
						if ( self::is_choice_valid( $optgroup_choice, $select_value ) ) {
							return;
						}
					}

				} else {

					if ( self::is_choice_valid( $choice, $select_value ) ) {
						return;
					}

				}

			}

			$this->inputs['select']->set_error( esc_html__( 'Invalid selection.', 'gravityforms' ) );

		}

	}

}

Fields::register( 'select_custom', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Select_Custom' );
