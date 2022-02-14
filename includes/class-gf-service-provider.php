<?php

namespace Gravity_Forms\Gravity_Forms;

/**
 * Class GF_Service_Provider
 *
 * An abstraction which provides a contract for defining Service Providers. Service Providers facilitate
 * organizing Services into discreet modules, as opposed to having to register each service in a single location.
 *
 * @since 2.5
 *
 * @package Gravity_Forms\Gravity_Forms
 */
abstract class GF_Service_Provider {

	/**
	 * Register new services to the Service Container.
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	abstract public function register( GF_Service_Container $container );

	/**
	 * Noop by default - used to initialize hooks and filters for the given module.
	 */
	public function init( GF_Service_Container $container ) {}

}