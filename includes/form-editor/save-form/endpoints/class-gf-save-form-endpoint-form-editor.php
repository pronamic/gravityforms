<?php

namespace Gravity_Forms\Gravity_Forms\Form_Editor\Save_Form\Endpoints;

use Gravity_Forms\Gravity_Forms\Save_Form\GF_Form_CRUD_Handler;
use Gravity_Forms\Gravity_Forms\Save_Form\GF_Save_Form_Service_Provider;
use Gravity_Forms\Gravity_Forms\Save_Form\Endpoints\GF_Save_Form_Endpoint_Admin;
use Gravity_Forms\Gravity_Forms\Form_Editor\GF_Form_Editor_Service_Provider;
use Gravity_Forms\Gravity_Forms\Form_Editor\Renderer\GF_Form_Editor_Renderer;
use Gravity_Forms\Gravity_Forms\Util\GF_Util_Service_Provider;
/**
 * AJAX Endpoint for Saving the form in the main form editor.
 *
 * @since 2.6
 *
 * @package Gravity_Forms\Gravity_Forms\Save_Form\Endpoints
 */
class GF_Save_Form_Endpoint_Form_Editor extends GF_Save_Form_Endpoint_Admin {

	// AJAX action name.
	const ACTION_NAME = 'form_editor_save_form';

	/**
	 * Handles a successful operation and returns the desired response.
	 *
	 * @since 2.6
	 *
	 * @param array $result The result of the operation.
	 *
	 * @return mixed
	 */
	protected function get_success_status_response( $result ) {

		$gf_forms                 = $this->gf_forms;
		$editor_renderer          = $gf_forms::get_service_container()->get( GF_Form_Editor_Service_Provider::FORM_EDITOR_RENDERER );
		$form_detail              = $gf_forms::get_service_container()->get( GF_Util_Service_Provider::GF_FORM_DETAIL );
		$result['updated_markup'] = $editor_renderer::render_form_editor( $this->form_id, $form_detail );

		return  parent::get_success_status_response( $result );

	}


}
