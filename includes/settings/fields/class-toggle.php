<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class Toggle extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'toggle';

	/**
	 * Message to display for screen readers.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $toggle_label;

	protected $disabled;




	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function markup() {

		// Determine if toggle is selected.
		$enabled = $this->get_value();

		// Prepare input ID.
		$input_id = sprintf(
			'%s_%s',
			esc_attr( $this->settings->get_input_name_prefix() ),
			esc_attr( $this->name )
		);

		// Display description.
		$html = $this->get_description();

		$html .= '<span class="' . esc_attr( $this->get_container_classes() ) . '">';

		// Insert checkbox.
		$html .= sprintf(
			'<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s %3$s %4$s />',
			$input_id,
			checked( '1', $enabled, false ),
			$this->get_describer() ? sprintf( 'aria-describedby="%s"', $this->get_describer() ) : '',
			implode( ' ', $this->get_attributes() )
		);

		// Insert toggle UI.
		$html .= sprintf( '<label class="gform-field__toggle-container" for="%s">', $input_id );
		$html .= $this->toggle_label ? sprintf( '<span class="screen-reader-text">%s</span>', rgar( $this, 'label' ) ? esc_html( $this->toggle_label ) : '' ) : '';
		$html .= '<span class="gform-field__toggle-switch"></span>';
		$html .= '</label>';

		// Insert after input markup.
		$html .= rgobj( $this, 'after_input' );

		// If field failed validation, add error icon.
		$html .= $this->get_error_icon();

		$html .= '</span>';

		return $html;

	}

}

Fields::register( 'toggle', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Toggle' );
