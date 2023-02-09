<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use GFCommon;
use Gravity_Forms\Gravity_Forms\Settings\Settings;
use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class Radio extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'radio';

	/**
	 * Field choices.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	public $choices = array();

	/**
	 * Whether the input should be an image select.
	 *
	 * @var bool
	 */
	public $image_select = false;

	/**
	 * Initialize field.
	 *
	 * @since 2.5
	 *
	 * @param array    $props    Field properties.
	 * @param Settings $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {

		// Prepare class.
		$class = self::has_icons( rgar( $props, 'choices', array() ) ) ? array( 'gform-settings-field__radio--visual' ) : array();

		// Convert defined classes.
		if ( rgar( $props, 'class' ) ) {
			$props['class'] = explode( ' ', $props['class'] );
			$class          = array_merge( $class, $props['class'] );
			unset( $props['class'] );
		}

		// Define class.
		$this->class = implode( ' ', $class );

		parent::__construct( $props, $settings );

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

		// Get choices, selected value, classes.
		$choices          = $this->get_choices();
		$selected_value   = $this->get_value();
		$horizontal_class = rgobj( $this, 'horizontal' ) || self::has_icons( $choices ) ? ' gform-settings-choice--inline' : '';
		$icon_class       = self::has_icons( $choices ) ? ' gform-settings-choice--visual' : '';

		if ( $this->image_select ) {
			$icon_class = ' gform-settings-choice--image-select';
		}

		// If no choices exist, return.
		if ( $choices === false || empty( $choices ) ) {
			return '';
		}

		// Display description.
		$html = $this->get_description();

		$html .= '<span class="' . esc_attr( $this->get_container_classes() ) . '">';

		// Create inputs from choices.
		foreach ( $choices as $i => $choice ) {

			// Prepare choice attributes.
			$choice['id']   = rgempty( 'id', 'choice' ) ? sprintf( '%s%s', $this->name, $i ) : $choice['id'];
			$choice_value   = isset( $choice['value'] ) ? $choice['value'] : $choice['label'];
			$choice_tooltip = $this->settings->maybe_get_tooltip( $choice );

			// Get input.
			$choice_input = sprintf(
				'<input type="radio" name="%s_%s" value="%s" %s %s />',
				$this->settings->get_input_name_prefix(),
				$this->name,
				$choice_value,
				checked( $selected_value, $choice_value, false ),
				implode( ' ', self::get_choice_attributes( $choice, $this->get_attributes() ) )
			);

			// Get icon markup.
			if ( self::has_icons( $choices ) ) {

				// Get the defined icon or use default.
				$icon_markup = GFCommon::get_icon_markup( $choice, 'fa-cog' );

			}

			$html .= sprintf(
				'<div id="gform-settings-radio-choice-%1$s" class="gform-settings-choice%2$s%3$s">
					%4$s
					<label for="%1$s">
						<span>%5$s <span class="gform-settings-choice-label">%6$s</span> %7$s</span>
					</label>
				</div>',
				esc_attr( $choice['id'] ),
				$icon_class,
				$horizontal_class,
				$choice_input,
				isset( $icon_markup ) ? $icon_markup : '',
				esc_html( $choice['label'] ),
				$choice_tooltip
			);

		}

		// Wrap visual choices with container.
		if ( ! empty( $icon_class ) ) {
			$html = sprintf(
				'<div class="gform-settings-choices--visual">%s</div>',
				$html
			);
		}

		// If field failed validation, add error icon.
		$html .= $this->get_error_icon();

		$html .= '</span>';

		return $html;

	}





	// # VALIDATION METHODS --------------------------------------------------------------------------------------------

	/**
	 * Validate posted field value.
	 *
	 * @since 2.5
	 *
	 * @param string $value Posted field value.
	 */
	public function do_validation( $value ) {

		// If field is required and value is missing, set field error.
		if ( $this->required && rgblank( $value ) ) {
			$this->set_error( rgobj( $this, 'error_message' ) );
		}

		// Get choices.
		$choices = $this->get_choices();

		// If no selection was made or no choices exist, exit.
		if ( rgblank( $value ) || $choices === false || empty( $choices ) ) {
			return;
		}

		// Loop through choices, determine if valid.
		foreach ( $choices as $choice ) {
			if ( self::is_choice_valid( $choice, $value ) ) {
				return; // Choice is valid
			}
		}

		$this->set_error( esc_html__( 'Invalid selection.', 'gravityforms' ) );

	}

}

Fields::register( 'radio', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Radio' );
