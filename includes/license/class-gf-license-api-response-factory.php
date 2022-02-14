<?php

namespace Gravity_Forms\Gravity_Forms\License;

use Gravity_Forms\Gravity_Forms\External_API\GF_API_Response_Factory;

/**
 * Class GF_License_API_Response_Factory
 *
 * Concrete response factory used to return a License API Response
 *
 * @since 2.5.11
 *
 * @package Gravity_Forms\Gravity_Forms\License
 */
class GF_License_API_Response_Factory implements GF_API_Response_Factory {

	private $transient_strategy;

	/**
	 * GF_License_API_Response_Factory constructor
	 *
	 * @since 2.5.11
	 *
	 * @param $transient_strategy
	 */
	public function __construct( $transient_strategy ) {
		$this->transient_strategy = $transient_strategy;
	}

	/**
	 * Create a new License API Response from the given data.
	 *
	 * @since 2.5.11
	 *
	 * @param mixed ...$args
	 *
	 * @return GF_License_API_Response
	 */
	public function create( ...$args ) {
		$data     = $args[0];
		$validate = isset( $args[1] ) ? $args[1] : true;

		return new GF_License_API_Response( $data, $validate, $this->transient_strategy );
	}

}