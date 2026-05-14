<?php

namespace Gravity_Forms\Gravity_Forms\Webapi\User_Select;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Config_Data_Parser;

defined( 'ABSPATH' ) || die();

class GF_User_Select_Config extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Constructor
	 *
	 * @since 2.10.2
	 *
	 * @param GF_Config_Data_Parser $data_parser
	 */
	public function __construct( GF_Config_Data_Parser $data_parser ) {
		parent::__construct( $data_parser );
	}

	/**
	 * Only enqueue on the Web API settings page.
	 *
	 * @since 2.10.2
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return \GFForms::get_page() === 'settings' && rgget( 'subview' ) === 'gravityformswebapi';
	}

	/**
	 * Config data for the user select dropdown
	 *
	 * @since 2.10.2
	 *
	 * @return array
	 */
	public function data() {
		return array(
			'components' => array(
				'webapi_user_select' => array(
					'endpoints' => array( 'get' => array( 'action' => 'gfwebapi_get_users', 'nonce' => wp_create_nonce( 'gfwebapi_get_users' ) ) ),
					'data'      => \GFWebAPI::get_users( array( 'number' => 10 ) ),
					'strings'   => array(
						'search_placeholder'  => esc_html__( 'Search users...', 'gravityforms' ),
						'trigger_placeholder' => esc_html__( 'Select a user', 'gravityforms' ),
						'trigger_aria_text'   => esc_html__( 'User', 'gravityforms' ),
						'search_aria_text'    => esc_html__( 'Search for users', 'gravityforms' ),
					),
				),
			),
		);
	}
}
