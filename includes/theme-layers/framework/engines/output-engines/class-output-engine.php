<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Output_Engines;

use GFFormDisplay;

/**
 * Output_Engines are responsible for outputting some sort of value, whether CSS blocks,
 * markup, or some other theme-layer-related data.
 *
 * @since 2.7
 */
abstract class Output_Engine {

	protected $type;

	protected $namespace;

	/**
	 * The namespace of the theme layer, passed from the Addon.
	 *
	 * @since 2.7
	 *
	 * @param $namespace
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Handle output.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	abstract public function output();

	/**
	 * Getter for type.
	 *
	 * @since 2.7
	 *
	 * @return string
	 */
	public function type() {
		return $this->type;
	}

	/**
	 * Get the settings stored for this theme layer.
	 *
	 * @since 2.7
	 *
	 * @param $form_id
	 *
	 * @return array|mixed
	 */
	public function get_settings( $form_id ) {
		$form = \GFAPI::get_form( $form_id );

		return isset( $form[ $this->namespace ] ) ? $form[ $this->namespace ] : array();
	}

	/**
	 * Get a specific setting for this theme layer.
	 *
	 * @since 2.7
	 *
	 * @param      $key
	 * @param      $form_id
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public function get_setting( $key, $form_id, $default = null ) {
		$settings = $this->get_settings( $form_id );

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	public function get_block_settings( $form_id, $instance = 0 ) {
		$block_settings = apply_filters( 'gform_form_block_attribute_values', array() );

		return empty( $block_settings[ $form_id ] ) ? array() : rgar( $block_settings[ $form_id ], $instance, array() );
	}

	/**
	 * Parse the settings from the style filter or shortcode attributes.
	 *
	 * @since 2.7.15
	 *
	 * @param $form
	 *
	 * @return array
	 */
	public function parse_form_style( $form ) {
		$style_settings = array(
			'formId' => $form['id'],
		);

		if ( rgar( $form, 'theme' ) ) {
			$style_settings['theme'] = $form['theme'];
		}

		if ( rgar( $form, 'styles' ) ) {
			$styles = GFFormDisplay::validate_form_styles( $form['styles'] );

			foreach( $styles as $key => $value ) {
				$style_settings[ $key ] = $value;
			}
		}

		return $style_settings;
	}

}
