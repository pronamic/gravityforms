<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework;

use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Definition_Engines\Definition_Engine;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Factories\Definition_Engine_Factory;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Factories\Output_Engine_Factory;

/**
 * GF_Theme_Layer
 *
 * Provides base functionality for any system which needs to implement a Theme Layer. Can either be
 * directly extended or used via the API.
 *
 * @since 2.7
 */
abstract class GF_Theme_Layer {

	protected $name;
	protected $icon;
	protected $short_title;
	protected $priority;

	/**
	 * @var Definition_Engine[]
	 */
	protected $definition_engines = array();
	protected $output_engines     = array();

	/**
	 * @var Definition_Engine_Factory
	 */
	protected $definition_engine_factory;

	/**
	 * @var Output_Engine_Factory
	 */
	protected $output_engine_factory;

	/**
	 * Constructor
	 *
	 * @since 2.7
	 *
	 * @param Definition_Engine_Factory $definition_engine_factory
	 * @param Output_Engine_Factory     $output_engine_factory
	 *
	 * @return void
	 */
	public function __construct( Definition_Engine_Factory $definition_engine_factory, Output_Engine_Factory $output_engine_factory ) {
		$this->definition_engine_factory = $definition_engine_factory;
		$this->output_engine_factory     = $output_engine_factory;
		$this->init_engines();
	}

	/**
	 * Initialize the various engines the current Theme Layer implements.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function init_engines() {
		$methods = get_class_methods( $this );

		// Traits will define an `add_engine_{engine_name}` method that we can key from here.
		foreach ( $methods as $method ) {
			if ( strpos( $method, 'add_engine_' ) === false ) {
				continue;
			}

			$this->$method();
		}
	}

	public function output_engines() {
		return $this->output_engines;
	}

	public function output_engine_by_type( $type ) {
		$engines_of_type = array_filter( $this->output_engines, function( $engine ) use ( $type ) {
			return is_a( $engine, $type );
		});

		return empty( $engines_of_type ) ? null : array_shift( $engines_of_type );
	}

	/**
	 * Get the definitions for this theme layer.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	public function get_definitions() {
		$definitions = array();

		foreach ( $this->definition_engines as $engine ) {
			if ( ! isset( $definitions[ $engine->type() ] ) ) {
				$definitions[ $engine->type() ] = array();
			}
			$definitions[ $engine->type() ] = array_merge( $definitions[ $engine->type() ], $engine->get_definitions() );
		}

		return $definitions;
	}

	/**
	 * Getter for name
	 *
	 * @since 2.7
	 *
	 * @return string
	 */
	public function name() {
		return $this->name;
	}

	/**
	 * Getter for priority
	 *
	 * @since 2.7
	 *
	 * @return int
	 */
	public function priority() {
		return $this->priority;
	}

	/**
	 * Getter for short_title
	 *
	 * @since 2.7
	 *
	 * @return string
	 */
	public function short_title() {
		return $this->short_title;
	}

	public function icon() {
		return $this->icon;
	}

}