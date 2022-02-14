<?php

namespace Gravity_Forms\Gravity_Forms;

/**
 * Class GF_Service_Container
 *
 * A simple Service Container used to collect and organize Services used by the application and its modules.
 *
 * @since 2.5
 *
 * @package Gravity_Forms\Gravity_Forms
 */
class GF_Service_Container {

	private $services = array();

	/**
	 * Add a service to the container.
	 *
	 * @since 2.5
	 *
	 * @param string $name   The service Name
	 * @param mixed $service The service to add
	 */
	public function add( $name, $service ) {
		if ( empty( $name ) ) {
			$name = get_class( $service );
		}

		if ( is_callable( $service ) ) {
			$service = $service();
		}

		$this->services[ $name ] = $service;
	}

	/**
	 * Remove a service from the container.
	 *
	 * @since 2.5
	 *
	 * @param string $name The service name.
	 */
	public function remove( $name ) {
		unset( $this->services[ $name ] );
	}

	/**
	 * Get a service from the container by name.
	 *
	 * @since 2.5
	 *
	 * @param string $name The service name.
	 *
	 * @return mixed|null
	 */
	public function get( $name ) {
		if ( ! isset( $this->services[ $name ] ) ) {
			return null;
		}

		return $this->services[ $name ];
	}

	/**
	 * Add a service provider to the container and register each of its services.
	 *
	 * @since 2.5
	 *
	 * @param GF_Service_Provider $provider
	 */
	public function add_provider( GF_Service_Provider $provider ) {
		$provider->register( $this );
		$provider->init( $this );
	}

}