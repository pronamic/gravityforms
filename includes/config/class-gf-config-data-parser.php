<?php

namespace Gravity_Forms\Gravity_Forms\Config;

/**
 * Parses a given data array to return either Live or Mock values, depending on the
 * environment and context.
 *
 * @since 2.6
 *
 * @package Gravity_Forms\Gravity_Forms\Config
 */
class GF_Config_Data_Parser {

	/**
	 * Parse the given $data array and get the correct values for the context.
	 *
	 * @since 2.6
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public function parse( $data ) {
		$return = array();

		foreach( $data as $key => $value ) {
			$return[ $key ] = $this->get_correct_value( $value );
		}

		return $return;
	}

	/**
	 * Loop through each array key and get the correct value. Is called recursively for
	 * nested arrays.
	 *
	 * @since 2.6
	 *
	 * @param mixed $value
	 *
	 * @return array|mixed
	 */
	private function get_correct_value( $value ) {

		// Value isn't array - we've reached the final level for this branch.
		if ( ! is_array( $value ) ) {
			return $value;
		}

		// Value is an array with our defined value and default keys. Return either live or mock data.
		if ( array_key_exists( 'default', $value ) ) {
			return $this->is_mock() ? $value['default'] : $value['value'];
		}

		$data = array();

		// Value is an array - recursively call this method to dig into each level and return the correct value.
		foreach( $value as $key => $value ) {
			$data[ $key ] = $this->get_correct_value( $value );
		}

		return $data;
	}

	/**
	 * Determine whether the current environmental context is a Mock context.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	private function is_mock() {
		return defined( 'GFORMS_DOING_MOCK' ) && GFORMS_DOING_MOCK;
	}

}