<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

// Load base class.
require_once 'class-select.php';
require_once 'class-text.php';

class Text_And_Select extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'text_and_select';

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

		// Prepare Text field.
		if ( isset( $this->inputs['text'] ) ) {

			// Set field type.
			if ( rgars( $this->inputs, 'text/type' ) && $this->inputs['text']['type'] !== 'text' ) {
				$this->inputs['text']['input_type'] = $this->inputs['text']['type'];
				$this->inputs['text']['type'] = 'text';
			} else if ( ! rgars( $this->inputs, 'text/type' ) ) {
				$this->inputs['text']['type'] = 'text';
			}

		}

		// Prepare Select field.
		if ( isset( $this->inputs['select'] ) ) {

			// Set field type.
			$this->inputs['select']['type'] = 'select';

		}

		// Prepare input fields.
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

		// Prepare markup.
		$html = $this->get_description();

		$html .= sprintf(
			'<span class="%s">%s %s %s</span>',
			esc_attr( $this->get_container_classes() ),
			isset( $this->inputs['text'] ) ? $this->inputs['text']->markup() : '',
			isset( $this->inputs['select'] ) ? $this->inputs['select']->markup() : '',
			$this->get_error_icon()
		);

		return $html;

	}

}

Fields::register( 'text_and_select', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Text_And_Select' );
