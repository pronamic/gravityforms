<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use GFCommon;
use Gravity_Forms\Gravity_Forms\Settings\Settings;
use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

// Load base class.
require_once 'class-radio.php';

class Card extends Radio {


	/**
	 * Field type.
	 *
	 * @since 2.5.6
	 *
	 * @var string
	 */
	public $type = 'card';

	/**
	 * Field choices.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	public $choices = array();

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
		$class = array( 'gform-settings-field__radio--visual' );

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
		$choices        = $this->get_choices();
		$selected_value = $this->get_value();
		$has_icons      = self::has_icons( $choices );
		$has_tag        = self::has_tag( $choices );
		$icon_class     = ' gform-settings-choice--visual';

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
			$choice['id']   = rgempty( 'id', $choice ) ? sprintf( '%s%s', $this->name, $i ) : $choice['id'];
			$choice_value   = rgar( $choice, 'value' ) ? $choice['value'] : $choice['label'];
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
			if ( $has_icons ) {

				// Get the defined icon or use default.
				$icon_markup = GFCommon::get_icon_markup( $choice, 'fa-cog' );

			}

			// Get tag markup.
			if ( $has_tag ) {
				$color      = rgar( $choice, 'color' ) ? $choice['color'] : 'port';
				$tag_markup = '<span class="gform-settings-card-tag gform-c-' . $color . '"><i class="gform-icon gform-icon--circle-star"></i>' . esc_html( $choice['tag'] ) . '</span>';
			}

			// Get description markup.
			$description_markup = '<div class="gform-settings-card--description"><div class="gform-settings-card--description-content">';
			if ( rgar( $choice, 'title' ) ) {
				$description_markup .= '<strong>' . esc_html( $choice['title'] ) . '</strong>';
			}
			if ( rgar( $choice, 'description' ) ) {
				$description_markup .= '<p>' . esc_html( $choice['description'] ) . '</p>';
			}
			$description_markup .= '</div></div>';

			$html .= sprintf(
				'<div id="gform-settings-radio-choice-%1$s" class="gform-settings-choice%2$s">
					%3$s
					<label for="%1$s">
						<span>%4$s %5$s %6$s %7$s</span>
					</label>
					%8$s
				</div>',
				esc_attr( $choice['id'] ),
				$icon_class,
				$choice_input,
				isset( $icon_markup ) ? $icon_markup : '',
				esc_html( $choice['label'] ),
				isset( $tag_markup ) ? $tag_markup : '',
				$choice_tooltip,
				$description_markup
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

}

Fields::register( 'card', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Card' );