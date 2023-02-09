<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\API;

use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Output_Engines\PHP_Markup_Output_Engine;

/**
 * Class used to handle overriding the content of a field or form.
 *
 * @since 2.7
 */
abstract class View {

	protected $engine;

	/**
	 * The Output_Engine for PHP Markup.
	 *
	 * @since 2.7
	 *
	 * @param PHP_Markup_Output_Engine $engine
	 */
	public function __construct( $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Get the markup for an item.
	 *
	 * @since 2.7
	 *
	 * @param $content
	 * @param $object
	 * @param $value
	 * @param $lead_id
	 * @param $form_id
	 *
	 * @return string
	 */
	abstract public function get_markup( $content, $object, $value, $lead_id, $form_id );

	/**
	 * Whether this markup override should be in effect.
	 *
	 * @since 2.7
	 *
	 * @param $object
	 * @param $form_id
	 * @param $block_settings
	 *
	 * @return bool
	 */
	public function should_override( $object, $form_id, $block_settings = array() ) {
		return true;
	}

	/**
	 * Get a setting from the engine.
	 *
	 * @since 2.7
	 *
	 * @param      $key
	 * @param      $form_id
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	protected function get_setting( $key, $form_id, $default = null ) {
		return $this->engine->get_setting( $key, $form_id, $default );
	}
}
