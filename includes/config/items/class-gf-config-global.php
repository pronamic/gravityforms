<?php

namespace Gravity_Forms\Gravity_Forms\Config\Items;

/**
 * Acts as a container for any Global Config data we need to send to both
 * the admin and theme side of the ecosystem.
 *
 * @since 2.7
 */
class GF_Config_Global {

	/**
	 * The data to send to both configs.
	 *
	 * @return array
	 */
	public function data() {
		return array(
			'hmr_dev'     => defined( 'GF_ENABLE_HMR' ) && GF_ENABLE_HMR,
			'public_path' => trailingslashit( \GFCommon::get_base_url() ) . 'assets/js/dist/',
		);
	}

}
