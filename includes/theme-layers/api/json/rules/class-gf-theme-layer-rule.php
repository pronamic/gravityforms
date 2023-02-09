<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\API\JSON\Rules;

class GF_Theme_Layer_Rule {

	private $setting;
	private $operator;
	private $value;

	public function __construct( $args ) {
		if ( ! isset( $args['setting'] ) || ! isset( $args['operator'] ) || ! isset( $args['value'] ) ) {
			throw new \InvalidArgumentException( 'Rules must have settings, operators, and values.' );
		}

		$setting_parts  = explode( '.', $args['setting'] );
		$this->setting  = array( 'type' => $setting_parts[0], 'name' => $setting_parts[1] );
		$this->operator = $args['operator'];
		$this->value    = $args['value'];
	}

	public function validate( $settings ) {
		$type = $this->setting['type'];
		$name = $this->setting['name'];

		if ( ! isset( $settings[ $type ][ $name ] ) ) {
			return false;
		}

		$value = $settings[ $type ][ $name ];

		switch ( $this->operator ) {
			case '=':
				return $value == $this->value;
			case '>':
				return $value > $this->value;
			case '>=':
				return $value >= $this->value;
			case '<':
				return $value < $this->value;
			case '<=':
				return $value <= $this->value;
			case '!=':
				return $value != $this->value;
			default:
				return false;
		}
	}

}