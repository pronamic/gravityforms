<?php

namespace Gravity_Forms\Gravity_Forms\Embed_Form\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Embed_Form\Endpoints\GF_Embed_Endpoint_Create_With_Block;
use Gravity_Forms\Gravity_Forms\Embed_Form\Endpoints\GF_Embed_Endpoint_Get_Posts;

/**
 * Config items for the Embed Forms REST Endpoints.
 *
 * @since 2.6
 */
class GF_Embed_Endpoints_Config extends GF_Config {

	protected $script_to_localize = 'gform_gravityforms_admin_vendors';
	protected $name               = 'gform_admin_config';
	protected $overwrite          = false;

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'components' => array(
				'embed_form' => array(
					'endpoints' => $this->get_endpoints(),
				),
			),
		);
	}

	/**
	 * Get the various endpoints for the Embed UI.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	private function get_endpoints() {
		return array(

			// Endpoint to get posts for typeahead
			'get_posts'              => array(
				'action' => array(
					'value'   => 'gf_embed_query_posts',
					'default' => 'mock_endpoint',
				),
				'nonce'  => array(
					'value'   => wp_create_nonce( GF_Embed_Endpoint_Get_Posts::ACTION_NAME ),
					'default' => 'nonce',
				)
			),

			// Endpoint to create a new page with our block inserted.
			'create_post_with_block' => array(
				'action' => array(
					'value'   => GF_Embed_Endpoint_Create_With_Block::ACTION_NAME,
					'default' => 'mock_endpoint',
				),
				'nonce'  => array(
					'value'   => wp_create_nonce( GF_Embed_Endpoint_Create_With_Block::ACTION_NAME ),
					'default' => 'nonce',
				)
			)
		);
	}

}
