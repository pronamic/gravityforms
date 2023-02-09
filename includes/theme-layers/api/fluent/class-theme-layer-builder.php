<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\API\Fluent;

use Gravity_Forms\Gravity_Forms\Theme_Layers\API\Fluent\Layers\Fluent_Theme_Layer;
use Gravity_Forms\Gravity_Forms\Theme_Layers\GF_Theme_Layers_Provider;

/**
 * Wrapper around the Fluent_Theme_Layer that provides fluent access (each call returns the class so
 * future calls can be chained).
 *
 * @since 2.7
 */
class Theme_Layer_Builder {

	private $layer;

	/**
	 * Gathers the various dependencies
	 *
	 * NOTE: we don't use DI here because this class is instantiated in various places, and it would make
	 * the process onerous for third-party usage.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function __construct() {
		$def_factory    = \GFForms::get_service_container()->get( GF_Theme_Layers_Provider::DEFINITION_ENGINE_FACTORY );
		$output_factory = \GFForms::get_service_container()->get( GF_Theme_Layers_Provider::OUTPUT_ENGINE_FACTORY );
		$this->layer    = new Fluent_Theme_Layer( $def_factory, $output_factory );
	}

	/**
	 * Initialize the layer's engines and add the layer to the list of registered theme layers.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function register() {
		$layer = $this->layer;
		$layer->init_engines();
		add_filter( 'gform_registered_theme_layers', function ( $layers ) use ( $layer ) {
			$layers[] = $layer;

			return $layers;
		} );
	}

	/**
	 * Setter for name.
	 *
	 * @since 2.7
	 *
	 * @param $name
	 *
	 * @return $this
	 */
	public function set_name( $name ) {
		$this->layer->set_name( $name );

		return $this;
	}

	/**
	 * Setter for title.
	 *
	 * @since 2.7
	 *
	 * @param $title
	 *
	 * @return $this
	 */
	public function set_short_title( $title ) {
		$this->layer->set_short_title( $title );

		return $this;
	}

	/**
	 * Setter for priority.
	 *
	 * @since 2.7
	 *
	 * @param $priority
	 *
	 * @return $this
	 */
	public function set_priority( $priority ) {
		$this->layer->set_priority( $priority );

		return $this;
	}

	public function set_icon( $icon ) {
		$this->layer->set_icon( $icon );

		return $this;
	}

	/**
	 * Setter for fields.
	 *
	 * @since 2.7
	 *
	 * @param $fields
	 *
	 * @return $this
	 */
	public function set_settings_fields( $fields ) {
		$this->layer->set_settings_fields( $fields );

		return $this;
	}

	/**
	 * Setter for overidden fields.
	 *
	 * @since 2.7
	 *
	 * @param $fields
	 *
	 * @return $this
	 */
	public function set_overidden_fields( $fields ) {
		$this->layer->set_overidden_fields( $fields );

		return $this;
	}

	/**
	 * Setter for css properties.
	 *
	 * @since 2.7
	 *
	 * @param $properties
	 *
	 * @return $this
	 */
	public function set_form_css_properties( $properties ) {
		$this->layer->set_form_css_properties( $properties );

		return $this;
	}

	/**
	 * Setter for scripts.
	 *
	 * @since 2.7
	 *
	 * @param $scripts
	 *
	 * @return $this
	 */
	public function set_scripts( $scripts ) {
		$this->layer->set_scripts( $scripts );

		return $this;
	}

	/**
	 * Setter for styles.
	 *
	 * @since 2.7
	 *
	 * @param $styles
	 *
	 * @return $this
	 */
	public function set_styles( $styles ) {
		$this->layer->set_styles( $styles );

		return $this;
	}

	/**
	 * Setter for block settings.
	 *
	 * @since 2.7
	 *
	 * @param $settings
	 *
	 * @return $this
	 */
	public function set_block_settings( $settings ) {
		$this->layer->set_block_settings( $settings );

		return $this;
	}
}