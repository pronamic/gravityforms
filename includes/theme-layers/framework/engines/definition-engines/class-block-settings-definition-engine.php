<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Definition_Engines;

/**
 * Handles defining Settings Fields for the Form block.
 *
 * @since 2.7
 */
class Block_Settings_Definition_Engine extends Definition_Engine {

	protected $type = 'block_settings';

	/**
	 * The settings.
	 *
	 * @since 2.7
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Setter for block settings.
	 *
	 * @since 2.7
	 *
	 * @param array $settings
	 */
	public function set_block_settings( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Getter for settings/definitions.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	public function get_definitions() {
		return $this->settings;
	}

}