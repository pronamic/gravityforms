<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Factories;

use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Output_Engines\Asset_Enqueue_Output_Engine;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Output_Engines\Form_CSS_Properties_Output_Engine;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Output_Engines\PHP_Markup_Output_Engine;
use Gravity_Forms\Gravity_Forms\Theme_Layers\GF_Theme_Layers_Provider;

/**
 * Factory to generate the Output Engines used by a theme layer.
 *
 * @since 2.7
 */
class Output_Engine_Factory {

	protected $namespace;

	/**
	 * Constructor
	 *
	 * @since 2.7
	 *
	 * @param $namespace The theme layer namespace.
	 *
	 * @return void
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Map of engines this factory can provide.
	 *
	 * @since 2.7
	 *
	 * @return string[]
	 */
	public function engines() {
		return array(
			GF_Theme_Layers_Provider::MARKUP_OUTPUT_ENGINE              => PHP_Markup_Output_Engine::class,
			GF_Theme_Layers_Provider::FORM_CSS_PROPERTIES_OUTPUT_ENGINE => Form_CSS_Properties_Output_Engine::class,
			GF_Theme_Layers_Provider::ASSET_ENQUEUE_OUTPUT_ENGINE       => Asset_Enqueue_Output_Engine::class,
		);
	}

	/**
	 * Return a specific engine by name.
	 *
	 * @since 2.7
	 *
	 * @param $name
	 *
	 * @return mixed|null
	 */
	public function get( $name ) {
		$engines = $this->engines();

		if ( ! isset( $engines[ $name ] ) ) {
			return null;
		}

		return new $engines[ $name ]( $this->namespace );
	}

}