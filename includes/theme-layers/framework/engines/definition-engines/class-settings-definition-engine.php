<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Definition_Engines;

/**
 * Handles defining Form Settings fields for a theme layer.
 *
 * @since 2.7
 */
class Settings_Definition_Engine extends Definition_Engine {

	protected $type = 'settings';

	/**
	 * @since 2.7
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * Setter for fields.
	 *
	 * @since 2.7
	 *
	 * @param array $fields
	 */
	public function set_fields( array $fields ) {
		$this->fields = $fields;
	}

	/**
	 * Return the fields defined for this layer.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	public function get_definitions() {
		return $this->fields;
	}

}