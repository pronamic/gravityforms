<?php

namespace Gravity_Forms\Gravity_Forms\External_API;

/**
 * Class GF_API_Connector
 *
 * An abstraction allowing us to create codified API Connector classes with a distinct
 * strategy for each one, and a standardized Cache mechanism. This separates the actually
 * communication logic from the class which calls it, allowing better testability.
 *
 * @package Gravity_Forms\Gravity_Forms\External_API
 *
 * @since 2.5
 */
abstract class GF_API_Connector {

	protected $strategy;

	/**
	 * @var \GFCache $cache
	 */
	protected $cache;

	/**
	 * GF_API_Connector constructor.
	 *
	 * @param $strategy The strategy class used to actually communicate with the API.
	 * @param $cache    The cache class used for caching results and other operations.
	 */
	public function __construct( $strategy, $cache ) {
		$this->strategy = $strategy;
		$this->cache    = $cache;
	}

}