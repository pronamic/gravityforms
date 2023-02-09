<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Traits;

use Gravity_Forms\Gravity_Forms\Theme_Layers\GF_Theme_Layers_Provider;

trait Enqueues_Assets {

	/**
	 * Provides an array of scripts to be enqueued for the form.
	 *
	 * @since 2.7
	 *
	 * @param $form           The Form being processed.
	 * @param $ajax           Whether this is an AJAX request.
	 * @param $settings       The current settings for this form.
	 * @param $block_settings The current block settings for this form.
	 *
	 * @return array
	 */
	abstract public function scripts( $form, $ajax, $settings, $block_settings = array() );

	/**
	 * Provides an array of styles to be enqueued for the form.
	 *
	 * @since 2.7
	 *
	 * @param $form           The Form being processed.
	 * @param $ajax           Whether this is an AJAX request.
	 * @param $settings       The current settings for this form.
	 * @param $block_settings The current block settings for this form.
	 *
	 * @return array
	 */
	abstract public function styles( $form, $ajax, $settings, $block_settings = array() );

	/**
	 * Add the engine.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function add_engine_asset_enqueues() {
		$engine = $this->output_engine_factory->get( GF_Theme_Layers_Provider::ASSET_ENQUEUE_OUTPUT_ENGINE );
		$engine->set_styles( array( $this, 'styles' ) );
		$engine->set_scripts( array( $this, 'scripts' ) );

		$this->output_engines[] = $engine;

		add_action( 'init', array( $engine, 'output' ), 11 );
	}

}
