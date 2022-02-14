<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class Hidden extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'hidden';





	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function markup() {

		// Get value.
		$value = $this->get_value();

		// Convert value to JSON string.
		if ( is_array( $value ) ) {
			$value = json_encode( $value );
		}

		// Prepare markup.
		$html = sprintf(
			'<input type="hidden" name="%1$s_%2$s" id="%2$s" value=\'%3$s\' %s />',
			esc_attr( $this->settings->get_input_name_prefix() ),
			esc_attr( $this->name ),
			esc_attr( $value ),
			implode( ' ', $this->get_attributes() )
		);

		return $html;

	}

}

Fields::register( 'hidden', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Hidden' );
