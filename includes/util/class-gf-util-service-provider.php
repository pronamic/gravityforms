<?php

namespace Gravity_Forms\Gravity_Forms\Util;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Transients\GF_WP_Transient_Strategy;

class GF_Util_Service_Provider extends GF_Service_Provider {

	const GF_CACHE        = 'gf_cache';
	const TRANSIENT_STRAT = 'gf_license_transient_strat';

	public function register( GF_Service_Container $container ) {
		$container->add( self::GF_CACHE, function () {
			return new \GFCache();
		} );

		$container->add( self::TRANSIENT_STRAT, function () {
			return new GF_WP_Transient_Strategy();
		} );
	}
}