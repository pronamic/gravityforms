<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\API\Fluent\Layers;

use Gravity_Forms\Gravity_Forms\Theme_Layers\API\GF_All_Access_Theme_Layer;

/**
 * Theme Layer set up to be used in a fluent context. This layer extends GF_All_Access_Theme_Layer,
 * which allows it to have access to all available traits and engines.
 *
 * This layer mostly acts as a middleware to pass values from the fluent builder to the layer.
 *
 * @since 2.7
 */
class Fluent_Theme_Layer extends GF_All_Access_Theme_Layer {

	////////////////////////////////////////////////
	/// Getters ////////////////////////////////////
	////////////////////////////////////////////////

	public function settings_fields() {
		return $this->_settings_fields;
	}

	public function block_settings() {
		return $this->_block_settings;
	}

	public function overriden_fields() {
		return $this->_overidden_fields;
	}

	public function form_css_properties( $form_id = 0, $settings = array(), $block_settings = array(), $form = array() ) {
		if ( is_callable( $this->_form_css_properties ) ) {
			return call_user_func_array( $this->_form_css_properties, array( $form_id, $settings, $block_settings, $form ) );
		}

		return $this->_form_css_properties;
	}

	public function scripts( $form, $ajax, $settings, $block_settings = array() ) {
		return is_callable( $this->_scripts ) ? call_user_func_array( $this->_scripts, array(
			$form,
			$ajax,
			$settings,
			$block_settings,
		) ) : array();
	}

	public function styles( $form, $ajax, $settings, $block_settings = array() ) {
		return is_callable( $this->_styles ) ? call_user_func_array( $this->_styles, array(
			$form,
			$ajax,
			$settings,
			$block_settings,
		) ) : array();
	}

	////////////////////////////////////////////////
	/// Setters ////////////////////////////////////
	////////////////////////////////////////////////

	public function set_settings_fields( $fields ) {
		$this->_settings_fields = $fields;
	}

	public function set_block_settings( $settings ) {
		$this->_block_settings = $settings;
	}

	public function set_overidden_fields( $fields ) {
		$this->_overidden_fields = $fields;
	}

	public function set_form_css_properties( $properties ) {
		$this->_form_css_properties = $properties;
	}

	public function set_scripts( $scripts ) {
		$this->_scripts = $scripts;
	}

	public function set_styles( $styles ) {
		$this->_styles = $styles;
	}

	public function set_name( $name ) {
		$this->name = $name;
	}

	public function set_priority( $priority ) {
		$this->priority = $priority;
	}

	public function set_short_title( $title ) {
		$this->short_title = $title;
	}

	public function set_icon( $icon ) {
		$this->icon = $icon;
	}

	public function set_capability( $capability ) {
		$this->form_settings_capability = $capability;
	}

}
