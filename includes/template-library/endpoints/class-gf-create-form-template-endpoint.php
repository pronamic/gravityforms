<?php

namespace Gravity_Forms\Gravity_Forms\Template_Library\Endpoints;

use GFForms;
use Gravity_Forms\Gravity_Forms\Save_Form\GF_Save_Form_Service_Provider;
use Gravity_Forms\Gravity_Forms\Template_Library\Config\GF_Template_Library_Config;
use Gravity_Forms\Gravity_Forms\Template_Library\Templates\GF_Template_Library_Template;
use Gravity_Forms\Gravity_Forms\Template_Library\Templates\GF_Templates_Repository;
use Gravity_Forms\Gravity_Forms\Template_Library\Templates\GF_Templates_Store;

/**
 * AJAX Endpoint for Creating a form from a template.
 *
 * @since   2.7
 *
 * @package Gravity_Forms\Gravity_Forms\Template_Library\Endpoints
 */
class GF_Create_Form_Template_Library_Endpoint {

	// AJAX action name.
	const ACTION_NAME = 'create_from_template';

	/**
	 * The template id to import.
	 *
	 * @since 2.7
	 *
	 * @var string $template_id
	 */
	protected $template_id;

	/**
	 * The form title of the form to be imported from a template or created.
	 *
	 * @since 2.7
	 *
	 * @var string $form_title
	 */
	protected $form_title;

	/**
	 * The form title of the form to be imported from a template or created.
	 *
	 * @since 2.7
	 *
	 * @var string $form_description;
	 */
	protected $form_description = '';

	/**
	 * The templates' data store to retrieve the templates' data from.
	 *
	 * @since 2.7
	 *
	 * @var GF_Templates_Store $templates_repos
	 */
	protected $templates_store;

	/**
	 * Endpoint constructor.
	 *
	 * @since 2.7
	 *
	 * @param GF_Templates_Store $templates_store The templates' data store to retrieve the templates' data from.
	 */
	public function __construct( GF_Templates_Store $templates_store ) {
		$this->templates_store = $templates_store;
	}

	/**
	 * Handle creating a form from a template or a blank form.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function handle() {

		$this->template_id      = sanitize_text_field( rgpost( 'templateId' ) );
		$this->form_title       = sanitize_text_field( rgpost( 'form_title' ) );
		$this->form_description = sanitize_text_field( rgpost( 'form_description' ) );

		if ( ! $this->template_id || ! $this->form_title ) {
			wp_send_json_error( array( 'message' => 'Missing required parameter' ), 400 );
		}

		if ( $this->template_id === 'blank' ) {
			$template = new GF_Template_Library_Template(
				array(
					'id'          => 'blank',
					'title'       => '',
					'description' => '',
					'form_meta'   => array(
						'fields' => array(),
					),
				)
			);
		} else {
			$template = $this->templates_store->get( $this->template_id );
		}

		if ( ! is_a( $template, GF_Template_Library_Template::class ) ) {
			wp_send_json_error( array( 'message' => 'Invalid template ID' ), 400 );
		}

		$form_meta                = $template->get_form_meta();
		$form_meta['title']       = $this->form_title;
		$form_meta['description'] = $this->form_description;

		if ( $this->template_id !== 'blank' ) {
			$form_meta['template_id'] = $this->template_id;
		}

		$form_crud_handler = GFForms::get_service_container()->get( GF_Save_Form_Service_Provider::GF_FORM_CRUD_HANDLER );
		$result            = $form_crud_handler->save( 0, wp_json_encode( $form_meta ) );

		$status  = rgar( $result, 'status' );
		$form_id = rgars( $result, 'meta/id', false );
		if ( is_numeric( $form_id ) && $form_id !== 0 ) {
			wp_send_json_success(
				array(
					'form_id' => abs( $form_id ),
				)
			);
		}

		wp_send_json_error(
			array(
				'message' => $status,
			)
		);

	}
}
