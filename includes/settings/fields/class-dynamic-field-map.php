<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

// Load base class.
require_once 'class-generic-map.php';

class Dynamic_Field_Map extends Generic_Map {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'dynamic_field_map';

	/**
	 * Initialize Dynamic Field Map field.
	 *
	 * @since 2.5
	 *
	 * @param array                                $props    Field properties.
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {

		// Check for custom key support.
		if ( isset( $props['disabled_custom'] ) && $props['disabled_custom'] === true ) {
			$this->key_field['allow_custom'] = false;
			unset( $props['disabled_custom'] );
		} else if ( isset( $props['enable_custom_key'] ) && $props['enable_custom_key'] !== true ) {
			$this->key_field['allow_custom'] = false;
			unset( $props['enable_custom_key'] );
		}
		if ( isset( $props['allow_duplicates'] ) ) {
			$this->key_field['allow_duplicates'] = $props['allow_duplicates'];
		}

		$this->value_field['allow_custom'] = false;

		parent::__construct( $props, $settings );

	}

}

Fields::register( 'dynamic_field_map', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Dynamic_Field_Map' );
