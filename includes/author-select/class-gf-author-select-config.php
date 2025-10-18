<?php

namespace Gravity_Forms\Gravity_Forms\Author_Select;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Config_Data_Parser;

defined( 'ABSPATH' ) || die();

/**
 * Config items for the Author Select dropdown
 *
 * @since 2.9.20
 */
class GF_Author_Select_Config extends GF_Config {

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
	 * Config data for the author select dropdown
	 *
	 * @since 2.9.20
	 *
	 * @return array
	 */
	public function data() {
		$form_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$form           = \GFFormsModel::get_form_meta( $form_id );
		$current_author = rgar( $form, 'postAuthor' );
		$selected_label = '';

		if ( $current_author ) {
			$current_user = get_user_by( 'id', $current_author );
			if ( $current_user ) {
				$selected_label = esc_html( $current_user->display_name );
			}
		}

		return array(
			'components' => array(
				'author_select' => array(
					'endpoints' => array(
						'get' => array(
							'action' => 'gf_get_users',
							'nonce'  => wp_create_nonce( 'gf_get_users' ),
						),
					),
					'data'           => $this->get_initial_users( $form_id ),
					'form_id'        => $form_id,
					'selected_value' => $current_author,
					'selected_label' => $selected_label,
					'strings'        => array(
						'search_placeholder'  => esc_html__( 'Search users...', 'gravityforms' ),
						'trigger_placeholder' => esc_html__( 'Select an author', 'gravityforms' ),
						'trigger_aria_text'   => esc_html__( 'Default Post Author', 'gravityforms' ),
						'search_aria_text'    => esc_html__( 'Search for users', 'gravityforms' ),
					),
				),
			),
		);
	}

	/**
	 * Get the initial list of users for the dropdown
	 *
	 * @since 2.9.20
	 *
	 * @param int $form_id The form ID
	 * @return array
	 */
	private function get_initial_users( $form_id ) {
		$args = array(
			'number' => 10,
			'fields' => array( 'ID', 'display_name' ),
		);

		// Apply existing filter for backward compatibility
		$args = gf_apply_filters( array( 'gform_author_dropdown_args', $form_id ), $args );

		$users = get_users( $args );

		if ( ! is_array( $users ) || empty( $users ) ) {
			return array();
		}

		$initial_users = array();
		foreach ( $users as $user ) {
			$initial_users[] = array(
				'value' => $user->ID,
				'label' => esc_html( $user->display_name ),
			);
		}

		return $initial_users;
	}
}
