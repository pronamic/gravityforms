<?php

namespace Gravity_Forms\Gravity_Forms\Post_Custom_Field_Select;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Config_Data_Parser;
use GFFormsModel;

defined( 'ABSPATH' ) || die();

/**
 * Config items for the Post Custom Field Select dropdown
 *
 * @since 2.9.20
 */
class GF_Post_Custom_Field_Select_Config extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Constructor
	 *
	 * @since 2.9.20
	 *
	 * @param GF_Config_Data_Parser $data_parser
	 */
	public function __construct( GF_Config_Data_Parser $data_parser ) {
		parent::__construct( $data_parser );
	}

	/**
	 * Only enqueue in the form editor
	 *
	 * @since 2.9.20
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return \GFForms::get_page() === 'form_editor';
	}

	/**
	 * Config data for the post custom field select dropdown
	 *
	 * @since 2.9.20
	 *
	 * @return array
	 */
	public function data() {
		return array(
			'components' => array(
				'post_custom_select' => array(
					'endpoints' => array(
						'get' => array(
							'action' => 'gf_get_custom_fields',
							'nonce'  => wp_create_nonce( 'gf_get_custom_fields' ),
						),
					),
					'data'           => $this->get_initial_custom_fields(),
					'strings'        => array(
						'search_placeholder'  => esc_html__( 'Search custom field names...', 'gravityforms' ),
						'trigger_placeholder' => esc_html__( 'Select a custom field name', 'gravityforms' ),
						'trigger_aria_text'   => esc_html__( 'Default custom field name', 'gravityforms' ),
						'search_aria_text'    => esc_html__( 'Search for custom field names', 'gravityforms' ),
					),
				),
			),
		);
	}

	/**
	 * Get the initial list of custom fields for the dropdown
	 *
	 * @since 2.9.20
	 *
	 * @return array
	 */
	private function get_initial_custom_fields() {

		$results = GFFormsModel::get_custom_field_names( 10 );

		foreach( $results as &$result ) {
			$result = array(
				'value' => $result,
				'label' => $result,
			);
		}

		return $results;
	}
}
