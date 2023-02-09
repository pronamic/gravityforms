<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Traits;

use Gravity_Forms\Gravity_Forms\Theme_Layers\GF_Theme_Layers_Provider;

trait Modifies_Markup {

	/**
	 * Return an array of views to override for fields/forms.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	abstract public function overriden_fields();

	/**
	 * Add the engine.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function add_engine_markup_output() {
		$engine = $this->output_engine_factory->get( GF_Theme_Layers_Provider::MARKUP_OUTPUT_ENGINE );
		$engine->set_views( $this->overriden_fields() );

		$this->output_engines[] = $engine;

		add_action( 'init', array( $engine, 'output' ), 11 );
	}

}
