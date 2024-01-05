<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class Checkbox extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'checkbox';

	/**
	 * Field choices.
	 *
	 * @since 2.5
	 *
	 * @var array
	 */
	public $choices = array();

	/**
	 * Data Format.
	 *
	 * Set this to "array" to return an array of values.
	 *
	 * @since 2.6
	 *
	 * @var string
	 */
	public $data_format = 'bool';

	public $onclick;
	protected $no_choices;
	protected $horizontal;
	protected $enabledLabel;
	protected $onkeypress;




	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function markup() {

		if ( $this->data_format === 'array' ) {
			return $this->array_markup();
		}

		// Get choices, determine if choices have icon, display direction, default choice attributes.
		$choices                   = $this->get_choices();
		$have_icon                 = self::has_icons( $choices );
		$horizontal_class          = rgobj( $this, 'horizontal' ) || $have_icon ? ' gform-settings-choice--inline' : '';
		$default_choice_attributes = array(
			'onclick'    => 'event.target.previousSibling.value = ( this.checked ? 1 : 0 ); event.target.previousSibling.dispatchEvent( new Event( "change" ) );',
			'onkeypress' => 'event.target.previousSibling.value = ( this.checked ? 1 : 0 ); event.target.previousSibling.dispatchEvent( new Event( "change" ) );',
		);

		// If no choices exist, return.
		if ( $choices === false || empty( $choices ) ) {
			return '';
		}

		// Display description.
		$html = $this->get_description();

		$html .= '<span class="' . esc_attr( $this->get_container_classes() ) . '">';

		// Display checkboxes.
		foreach ( array_values( $choices ) as $i => $choice ) {

			// Prepare choice ID, attributes, value and tooltip.
			$choice['id']      = sanitize_title( $choice['name'] );
			$choice_attributes = self::get_choice_attributes( $choice, $this->get_attributes(), $default_choice_attributes );
			$value             = $this->settings->get_value( $choice['name'], rgar( $choice, 'default_value' ) );
			$tooltip           = $this->settings->maybe_get_tooltip( $choice );

			// Add icon to choice, if choices have icon and icon is missing.
			if ( $have_icon && ! rgar( $choice, 'icon' ) ) {
				$choice['icon'] = 'fag-cog';
			}

			// Open choice container.
			$html .= sprintf(
				'<div id="gform-settings-checkbox-choice-%s" class="gform-settings-choice%s%s">',
				esc_attr( $choice['id'] ),
				$horizontal_class,
				rgar( $choice, 'icon' ) ? ' gform-settings-choice--visual' : ''
			);

			// Insert hidden input.
			$html .= sprintf(
				'<input type="hidden" name="%s_%s" value="%s" />',
				esc_attr( $this->settings->get_input_name_prefix() ),
				esc_attr( $choice['name'] ),
				$value == '1' ? '1' : '0'
			);

			// Prepare choice markup.
			if ( is_callable( array( $this, 'checkbox_input_' . $choice['name'] ) ) ) {
				$html .= call_user_func( array(
					$this,
					'checkbox_input_' . $choice['name'],
				), $choice, $choice_attributes, $value, $tooltip );
			} else {
				$html .= self::render_input( $choice, $choice_attributes, $value, $tooltip );
			}

			// Close container.
			$html .= '</div>';

		}

		$html .= '</span>';

		// Wrap visual choices with container.
		if ( $have_icon ) {
			$html = sprintf(
				'<div class="gform-settings-choices--visual">%s</div>',
				$html
			);
		}

		// Display error.
		$html .= $this->get_error_icon();

		return $html;

	}

	/**
	 * Get the markup for array-style checkbox inputs.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function array_markup() {
		// Get choices, determine if choices have icon, display direction, default choice attributes.
		$choices                   = $this->get_choices();
		$have_icon                 = self::has_icons( $choices );
		$horizontal_class          = rgobj( $this, 'horizontal' ) || $have_icon ? ' gform-settings-choice--inline' : '';
		$default_choice_attributes = '';

		// If no choices exist, return.
		if ( $choices === false || empty( $choices ) ) {
			return '';
		}

		// Display description.
		$html = $this->get_description();

		$html .= '<span class="' . esc_attr( $this->get_container_classes() ) . '">';

		// Display checkboxes.
		foreach ( array_values( $choices ) as $i => $choice ) {

			// Prepare choice ID, attributes, value and tooltip.
			$choice['id'] = $choice['name'];
			$value        = $this->settings->get_value( $this->name );

			$choice_attributes          = self::get_choice_attributes( $choice, $this->get_attributes(), $default_choice_attributes );
			$choice_attributes['name']  = 'name="_gform_setting_' . $this->name . '[]"';
			$choice_attributes['value'] = 'value="' . $choice['name'] . '"';

			$tooltip = $this->settings->maybe_get_tooltip( $choice );

			// Add icon to choice, if choices have icon and icon is missing.
			if ( $have_icon && ! rgar( $choice, 'icon' ) ) {
				$choice['icon'] = 'fag-cog';
			}

			// Open choice container.
			$html .= sprintf(
				'<div id="gform-settings-checkbox-choice-%s" class="gform-settings-choice%s%s">',
				esc_attr( $choice['id'] ),
				$horizontal_class,
				rgar( $choice, 'icon' ) ? ' gform-settings-choice--visual' : ''
			);

			// Prepare choice markup.
			if ( is_callable( array( $this, 'checkbox_input_' . $choice['name'] ) ) ) {
				$html .= call_user_func( array(
					$this,
					'checkbox_input_' . $choice['name'],
				), $choice, $choice_attributes, $value, $tooltip );
			} else {
				$html .= self::render_input( $choice, $choice_attributes, $value, $tooltip, 'array' );
			}

			// Close container.
			$html .= '</div>';

		}

		$html .= '</span>';

		// Wrap visual choices with container.
		if ( $have_icon ) {
			$html = sprintf(
				'<div class="gform-settings-choices--visual">%s</div>',
				$html
			);
		}

		// Display error.
		$html .= $this->get_error_icon();

		return $html;
	}

	/**
	 * Returns markup for an individual checkbox input and its label.
	 *
	 * @since 2.5
	 * @since 2.6 Added $data_as_array attribute
	 *
	 * @param array  $choice        Choice array with all configured properties.
	 * @param array  $attributes    Array containing all the attributes for the input tag.
	 * @param string $value         Currently selection (1 if field has been checked. 0 or null otherwise).
	 * @param string $tooltip       Tooltip for this checkbox item.
	 * @param string $data_as_array Whether the data should be formatted for array values.
	 *
	 * @return string
	 */
	public static function render_input( $choice, $attributes = array(), $value = null, $tooltip = '', $data_as_array = false ) {

		// Initialize icon, label strings.
		$icon_string = $label_string = '';

		// Prepare the icon string.
		if ( rgar( $choice, 'icon' ) ) {

			// Get the icon.
			$icon = rgar( $choice, 'icon' ) ? $choice['icon'] : 'fag-cog';

			// Prepare string based on icon type.
			if ( filter_var( $icon, FILTER_VALIDATE_URL ) ) {
				$icon_string = sprintf( '<img src="%s" />', esc_attr( $icon ) );
			} else {
				$icon_string = sprintf( '<i class="fa %s"></i>', esc_attr( $icon ) );
			}

		}

		// Prepare label markup.
		if ( rgar( $choice, 'label' ) ) {
			$label_string = sprintf(
				'<label for="%s"><span>%s%s%s</span></label>',
				esc_attr( $choice['id'] ),
				$icon_string,
				esc_html( $choice['label'] ),
				$tooltip
			);
		}

		if ( $data_as_array ) {
			$checked = is_array( $value ) && in_array( $choice['name'], $value ) ? 'checked="checked"' : '';
		} else {
			$checked = checked( $value, '1', false );
		}

		// Prepare checkbox markup.
		return sprintf(
			'<input type="checkbox" %s %s />%s',
			$checked,
			implode( ' ', $attributes ),
			$label_string
		);

	}






	// # VALIDATION METHODS --------------------------------------------------------------------------------------------

	/**
	 * Validate posted field value.
	 *
	 * @since 2.5
	 *
	 * @param array $value Posted field value.
	 */
	public function do_validation( $value ) {

		if ( $this->data_format === 'array' ) {
			return $this->do_array_validation( $value );
		}

		// Get choices.
		$choices = $this->get_choices();

		// If field does not have choices defined, exit.
		if ( $choices === false || empty( $choices ) ) {
			return;
		}

		$selected = 0;

		// Loop through field choices, determine if any were selected.
		foreach ( $choices as $choice ) {

			// Get value for choice.
			$value = $this->settings->get_value( $choice['name'], '' );

			// If value is not in possible values array, set field error.
			if ( ! in_array( $value, array( '1', '0' ) ) ) {
				$this->set_error( esc_html__( 'Invalid selection.', 'gravityforms' ) );
				return;
			}

			if ( $value === '1' ) {
				$selected++;
			}

		}

		// If field is required and no choices were selected, set field error.
		if ( $this->required && $selected < 1 ) {
			$this->set_error( rgobj( $this, 'error_message' ) );
		}

	}

	/**
	 * Validate posted array field value.
	 *
	 * @since 2.6
	 *
	 * @param array $value Posted field value.
	 */
	public function do_array_validation( $value ) {
		// Get choices.
		$choices = $this->get_choices();

		if ( empty( $value ) ) {
			$value = $this->get_value( $this->name, array() );
		}

		// If field does not have choices defined, exit.
		if ( $choices === false || empty( $choices ) ) {
			return;
		}

		$selected = 0;

		// get list of acceptable choices.
		$whitelist = array();

		foreach ( $choices as $choice ) {
			$whitelist[] = $choice['name'];
		}

		foreach ( $value as $checked ) {
			if ( ! in_array( $checked, $whitelist ) ) {
				$this->set_error( esc_html__( 'Invalid selection.', 'gravityforms' ) );

				return;
			}
			$selected++;
		}

		// If field is required and no choices were selected, set field error.
		if ( $this->required && $selected < 1 ) {
			$this->set_error( rgobj( $this, 'error_message' ) );
		}
	}

}

Fields::register( 'checkbox', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Checkbox' );
