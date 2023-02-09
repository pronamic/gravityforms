<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Traits;

use Gravity_Forms\Gravity_Forms\Theme_Layers\GF_Theme_Layers_Provider;

/**
 * Provides methods for outputting custom CSS Properties for a form.
 *
 * @since 2.7
 */
trait Outputs_Form_CSS_Properties {

	/**
	 * Return an array of key/value pairs for CSS output.
	 *
	 * @since 2.7
	 *
	 * @param $form_id The ID of the form being processed.
	 *
	 * @return array
	 */
	abstract public function form_css_properties( $form_id );

	/**
	 * Add the engine.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function add_engine_form_css_properties() {
		$engine = $this->output_engine_factory->get( GF_Theme_Layers_Provider::FORM_CSS_PROPERTIES_OUTPUT_ENGINE );
		$engine->set_form_css_properties_cb( array( $this, 'form_css_properties' ) );

		$this->output_engines[] = $engine;

		add_action( 'init', array( $engine, 'output' ), 11 );
	}

}
