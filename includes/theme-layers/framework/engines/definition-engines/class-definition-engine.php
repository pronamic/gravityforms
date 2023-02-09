<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Definition_Engines;

/**
 * Definition_Engines are responsible for adding things that define values, such as
 * settings fields, block settings, etc.
 *
 * @since 2.7
 */
abstract class Definition_Engine {

	protected $type;

	/**
	 * Get the registered definitions.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	abstract public function get_definitions();

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

}