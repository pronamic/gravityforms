<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Traits;

use Gravity_Forms\Gravity_Forms\Theme_Layers\GF_Theme_Layers_Provider;

trait Has_Block_Settings {

	/**
	 * Returns an array of settings to add to the block.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	abstract public function block_settings();

	/**
	 * Add the engine.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function add_engine_block_settings() {
		$engine = $this->definition_engine_factory->get( GF_Theme_Layers_Provider::BLOCK_SETTINGS_DEFINITION_ENGINE );
		$engine->set_block_settings( $this->block_settings() );

		$this->definition_engines[] = $engine;

		add_filter( 'gform_form_block_attributes', function( $attributes ) use ( $engine ) {
			$defined_attrs = $engine->get_definitions();

			return array_merge( $attributes, $defined_attrs );
		}, 10, 1 );
	}

}
