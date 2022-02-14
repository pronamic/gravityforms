<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use GFCommon;
use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

// Load base class.
require_once 'class-generic-map.php';

class Field_Map extends Generic_Map {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'field_map';

	/**
	 * Initialize Field Map field.
	 *
	 * @since 2.5
	 *
	 * @param array                                $props    Field properties.
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {

		// Define key field header, disable custom keys.
		$this->key_field['title']        = esc_html__( 'Field', 'gravityforms' );
		$this->key_field['allow_custom'] = false;
		$this->key_field['display_all']  = true;

		// Define value field header, disable custom values.
		$this->value_field['title']        = esc_html__( 'Form Field', 'gravityforms' );
		$this->value_field['allow_custom'] = false;

		// Prepare key field choices.
		$this->key_field['choices'] = rgar( $props, 'field_map' ) ? array_values( $props['field_map'] ) : array();
		foreach ( $this->key_field['choices'] as $i => $choice ) {

			$field_types = isset( $choice['field_types'] ) ? $choice['field_types'] : rgar( $choice, 'field_type', false );

			// Set required, excluded field types.
			$this->key_field['choices'][ $i ]['required_types'] = $field_types ? $field_types : rgar( $choice, 'required_types', array() );
			$this->key_field['choices'][ $i ]['excluded_types'] = rgar( $choice, 'exclude_field_types', false ) ? $choice['exclude_field_types'] : rgar( $choice, 'excluded_types', array() );

			// Remove legacy properties.
			unset( $this->key_field['choices'][ $i ]['field_type'], $this->key_field['choices'][ $i ]['exclude_field_types'] );

		}

		// Remove field map property.
		unset( $props['field_map'] );

		parent::__construct( $props, $settings );

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Prepare field value for Field Map.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public function get_value() {

		// Get existing value.
		$existing_value = parent::get_value();

		// If existing value is array, use it.
		if ( is_array( $existing_value ) ) {
			return $existing_value;
		}

		// Initialize return array.
		$value = array();

		// Loop through choices, get keys and values from main settings.
		foreach ( $this->key_field['choices'] as $choice ) {

			// Add choice to return array.
			$value[] = array(
				'key'          => $choice['name'],
				'custom_key'   => '',
				'value'        => $this->settings->get_value( sprintf( '%s_%s', $this->name, $choice['name'] ) ),
				'custom_value' => '',
			);

		}

		return $value;

	}

	/**
	 * Modify field value for legacy setting.
	 *
	 * @since 2.5
	 *
	 * @param array             $field_values
	 * @param array|bool|string $field_value
	 *
	 * @return array
	 */
	public function save_field( $field_values, $field_value ) {

		// If field has a save callback, use parent method.
		if ( is_callable( $this->save_callback ) ) {
			return parent::save_field( $field_values, $field_value );
		}

		// Decode field value.
		$field_value = GFCommon::maybe_decode_json( $field_value );

		// Loop through map, save to field values.
		foreach ( $field_value as $mapping ) {
			$field_name                  = sprintf( '%s_%s', $this->name, $mapping['key'] );
			$field_values[ $field_name ] = $mapping['value'];
		}

		// Remove main mapping.
		unset( $field_values[ $this->name ] );

		return $field_values;

	}

}

Fields::register( 'field_map', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Field_Map' );
