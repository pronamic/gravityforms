<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\API\JSON\Layers;

use Gravity_Forms\Gravity_Forms\Theme_Layers\API\GF_All_Access_Theme_Layer;
use Gravity_Forms\Gravity_Forms\Theme_Layers\API\JSON\Rules\GF_Theme_Layer_Rule;

class Json_Theme_Layer extends GF_All_Access_Theme_Layer {

	protected $_file;
	protected $_json_data;
	protected $_rules;

	private function process_json() {
		$contents = file_get_contents( $this->_file );
		$data     = json_decode( $contents, true );

		if ( empty( $data['gravityforms'] ) ) {
			throw new \InvalidArgumentException( 'Invalid theme.json file provided.' );
		}

		$this->_json_data = $data['gravityforms'];

		if ( empty( $this->_json_data['name'] ) || empty( $this->_json_data['short_title'] ) ) {
			throw new \InvalidArgumentException( 'theme.json file must have a name and short_title value.' );
		}

		$this->set_name( $this->_json_data['name'] );
		$this->set_short_title( $this->_json_data['short_title'] );

		if ( isset( $this->_json_data['settings']['fields']['form'] ) ) {
			$this->set_settings_fields( $this->_json_data['settings']['fields']['form'] );
		}

		if ( isset( $this->_json_data['settings']['fields']['blocks'] ) ) {
			$this->set_block_settings( $this->_json_data['settings']['fields']['blocks'] );
		}

		if ( isset( $this->_json_data['settings']['cssProperties'] ) ) {
			$this->set_form_css_properties( $this->_json_data['settings']['cssProperties'] );
		}

		if ( isset( $this->_json_data['settings']['templateParts'] ) ) {
			$this->set_overidden_fields( $this->_json_data['settings']['templateParts'] );
		}

		if ( isset( $this->_json_data['settings']['assets']['scripts'] ) ) {
			$this->set_scripts( $this->_json_data['settings']['assets']['scripts'] );
		}

		if ( isset( $this->_json_data['settings']['assets']['styles'] ) ) {
			$this->set_styles( $this->_json_data['settings']['assets']['styles'] );
		}

		if ( isset( $this->_json_data['settings']['rules'] ) ) {
			$this->set_rules( $this->_json_data['settings']['rules'] );
		}
	}

	private function evaluate_rule( $rule, $settings, $block_settings ) {
		if ( is_string( $rule ) && ! isset( $this->_rules[ $rule ] ) ) {
			return false;
		}

		if ( is_string( $rule ) ) {
			$rule = $this->_rules[ $rule ];
		}

		$rule_object = new GF_Theme_Layer_Rule( $rule );

		return $rule_object->validate( array( 'form' => $settings, 'blocks' => $block_settings ) );
	}

	private function filter_values_by_rule( $values, $settings, $block_settings ) {
		$self = $this;

		return array_filter( $values, function ( $item ) use ( $self, $settings, $block_settings ) {
			if ( ! isset( $item['rules'] ) ) {
				return true;
			}

			$rule = $item['rules'];

			return $self->evaluate_rule( $rule, $settings, $block_settings );
		} );
	}

	////////////////////////////////////////////////
	/// Setters ////////////////////////////////////
	////////////////////////////////////////////////

	public function set_json( $file ) {
		$this->_file = $file;
		$this->process_json();
	}

	public function set_rules( $rules ) {
		$this->_rules = $rules;
	}

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
		foreach( $styles as &$style ) {
			$parsed = str_replace( '%gforms_plugin_url%', \GFCommon::get_base_url(), $style['path'] );
			$style['path'] = $parsed;
		}

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

	////////////////////////////////////////////////
	/// Getters ////////////////////////////////////
	////////////////////////////////////////////////

	public function settings_fields() {
		return array(
			array(
				'description' => $this->short_title(),
				'fields'      => $this->_settings_fields,
			),
		);
	}

	public function block_settings() {
		return $this->_block_settings;
	}

	public function overriden_fields() {
		return $this->_overidden_fields;
	}

	public function form_css_properties( $form_id = 0, $settings = array(), $block_settings = array() ) {
		return array();

		return $this->filter_values_by_rule( $this->_form_css_properties, $settings, $block_settings );
	}

	public function scripts( $form, $ajax, $settings, $block_settings = array() ) {
		return $this->filter_values_by_rule( $this->_scripts, $settings, $block_settings );
	}

	public function styles( $form, $ajax, $settings, $block_settings = array() ) {
		return $this->filter_values_by_rule( $this->_styles, $settings, $block_settings );
	}

}