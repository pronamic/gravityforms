<?php

namespace Gravity_Forms\Gravity_Forms\Util;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Transients\GF_WP_Transient_Strategy;
use Gravity_Forms\Gravity_Forms\Util\Colors\Color_Modifier;

class GF_Util_Service_Provider extends GF_Service_Provider {

	const GF_CACHE        = 'gf_cache';
	const TRANSIENT_STRAT = 'gf_license_transient_strat';
	const GF_COMMON       = 'gf_common';
	const GF_FORMS_MODEL  = 'gf_forms_model';
	const RG_FORMS_MODEL  = 'rg_forms_model';
	const GF_API          = 'gf_api';
	const GF_FORMS        = 'gf_forms';
	const GF_FORM_DETAIL  = 'gf_form_detail';
	const GF_COLORS       = 'gf_colors';


	public function register( GF_Service_Container $container ) {
		require_once( \GFCommon::get_base_path() . '/includes/util/colors/class-color-modifier.php' );

		$container->add(
			self::GF_CACHE,
			function () {
				return new \GFCache;
			}
		);

		$container->add(
			self::TRANSIENT_STRAT,
			function () {
				return new GF_WP_Transient_Strategy();
			}
		);

		$container->add(
			self::GF_COMMON,
			function () {
				return new \GFCommon;
			}
		);

		$container->add(
			self::GF_FORMS_MODEL,
			function () {
				return new \GFFormsModel;
			}
		);

		$container->add(
			self::RG_FORMS_MODEL,
			function () {
				return new \RGFormsModel;
			}
		);

		$container->add(
			self::GF_API,
			function () {
				return new \GFAPI;
			}
		);

		$container->add(
			self::GF_FORMS,
			function () {
				return new \GFForms;
			}
		);

		$container->add(
			self::GF_FORM_DETAIL,
			function () {
				return new \GFFormDetail;
			}
		);

		$container->add( self::GF_COLORS, function () {
			return new Color_Modifier();
		} );
	}
}
