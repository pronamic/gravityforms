<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class Button extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'button';

	/**
	 * Button input type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $input_type = 'button';

	/**
	 * Button classes.
	 *
	 * @var string
	 */
	public $class = 'gform_settings_button button';

	/**
	 * Initialize Button field.
	 *
	 * @since 2.5
	 *
	 * @param array                                $props    Field properties.
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {

		parent::__construct( $props, $settings );

		// Set default value.
		if ( ! isset( $this->value ) ) {
			$this->value = esc_html__( 'Submit', 'gravityforms' );
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

		// Prepare markup.
		$html = sprintf(
			'<button type="%s" name="%s" value="save" %s>%s</button>',
			$this->input_type,
			$this->name,
			implode( ' ', $this->get_attributes() ),
			esc_attr( $this->value )
		);

		return $html;

	}

}

Fields::register( 'button', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Button' );
