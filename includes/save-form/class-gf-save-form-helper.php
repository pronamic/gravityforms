<?php
/**
 * Class GF_Save_Form_Helper
 *
 * Provides some helper functions to the form saving functionality.
 *
 * @since 2.6
 *
 * @package Gravity_Forms\Gravity_Forms\Save_Form
 */
namespace Gravity_Forms\Gravity_Forms\Save_Form;


use Gravity_Forms\Gravity_Forms\GF_Service_Container;

class GF_Save_Form_Helper {
	/**
	 * Stores an instance of GFForms to call common static functions.
	 *
	 * @since 2.6
	 *
	 * @var \GFForms
	 */
	protected $gf_forms;

	/**
	 * GF_Save_Form_Helper constructor.
	 *
	 * @since 2.6
	 *
	 * @param array $dependencies Array of dependency objects.
	 */
	public function __construct( $dependencies ) {
		$this->gf_forms = $dependencies['gf_forms'];
	}

	/**
	 * Checks if the AJAX action being executed is one of the endpoints of saving the form.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	public function is_ajax_save_action() {
		$action   = rgpost( 'action' );
		$gf_forms = $this->gf_forms;
		$endpoint = $gf_forms::get_service_container()->get( $action );
		return $endpoint && is_a( $endpoint, 'Gravity_Forms\Gravity_Forms\Save_Form\Endpoints\GF_Save_Form_Endpoint_Admin' );
	}

	/**
	 * Checks if the ajax save is disabled using the provided filter
	 *
	 * @param integer $form_id If provided the filter will be used for this specific form.
	 *
	 * @since 2.6
	 *
	 * @return mixed
	 */
	public function is_ajax_save_disabled( $form_id = null ) {
		$is_ajax_save_disabled = false;
		/**
		 *  This filter should be used to disabled ajax save.
		 *
		 * @since 2.6
		 *
		 * @param boolean $is_ajax_save_disabled Defaults to false.
		 */
		return gf_apply_filters( array( 'gform_disable_ajax_save', $form_id ), $is_ajax_save_disabled );
	}
}
