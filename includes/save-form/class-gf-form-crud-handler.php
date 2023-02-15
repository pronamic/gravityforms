<?php
/**
 * Form CRUD handler.
 *
 * Handles creating and updating forms.
 *
 * @package Gravity_Forms\Gravity_Forms\Save_Form
 */

namespace Gravity_Forms\Gravity_Forms\Save_Form;

use GFFormsModel;
use RGFormsModel;
use GFAPI;
use GFForms;
use GFCommon;

class GF_Form_CRUD_Handler {

	// Statuses expected after attempting to save a form.
	const STATUS_SUCCESS         = 'success';
	const STATUS_FAILURE         = 'failure';
	const STATUS_INVALID_META    = 'invalid_meta';
	const STATUS_INVALID_JSON    = 'invalid_json';
	const STATUS_DUPLICATE_TITLE = 'duplicate_title';
	const STATUS_MISSING_TITLE   = 'missing_title';

	/**
	 * Holds an instance of the GFFormsModel class.
	 *
	 * @since 2.6
	 *
	 * @var \GFFormsModel
	 */
	private $gf_forms_model;

	/**
	 * Holds an instance of the GFCommon class.
	 *
	 * @since 2.6
	 *
	 * @var \GFCommon
	 */
	private $gf_common;

	/**
	 * Holds an instance of the RGFormsModel class.
	 *
	 * @since 2.6
	 *
	 * @var \RGFormsModel
	 */
	private $rg_forms_model;

	/**
	 * Holds an instance of the GFAPI class.
	 *
	 * @since 2.6
	 *
	 * @var \GFAPI
	 */
	private $gf_api;

	/**
	 * Holds an instance of the GFForms class.
	 *
	 * @since 2.6
	 *
	 * @var \GFForms
	 */
	private $gf_forms;

	/**
	 * The ID of the form we are working with.
	 *
	 * @since 2.6
	 *
	 * @var int
	 */
	protected $form_id;

	/**
	 * The JSON representation of the form.
	 *
	 * @since 2.6
	 *
	 * @var string
	 */
	protected $form_json;

	/**
	 * The meta data of the form as an associative array.
	 *
	 * @since 2.6
	 *
	 * @var array
	 */
	private $form_meta;

	/**
	 * Contains the delete form fields.
	 *
	 * @since 2.6
	 *
	 * @var integer[]
	 */
	private $deleted_fields;

	/**
	 * The form object.
	 *
	 * @since 2.6
	 *
	 * @var array
	 */
	private $form;

	/**
	 * GF_Form_CRUD_Handler constructor.
	 *
	 * @since 2.6
	 *
	 * @param array $dependencies Array of dependency objects.
	 */
	public function __construct( $dependencies ) {

		$this->gf_forms_model = $dependencies['gf_forms_model'];
		$this->rg_forms_model = $dependencies['rg_forms_model'];
		$this->gf_common      = $dependencies['gf_common'];
		$this->gf_api         = $dependencies['gf_api'];
		$this->gf_forms       = $dependencies['gf_forms'];

	}

	/**
	 * Updates an existing form or creates a new one if necessary.
	 *
	 * @since 2.6
	 *
	 * @param int    $form_id   The ID of the form to update.
	 * @param string $form_json The JSON representation of the form.
	 *
	 * @return array
	 */
	public function save( $form_id, $form_json ) {

		$this->form_id        = $form_id;
		$this->form_json      = $form_json;
		$this->deleted_fields = array();
		$this->form_meta      = array();

		if ( ! $this->cleanup() ) {
			return array(
				'status' => self::STATUS_INVALID_JSON,
				'meta'   => null,
			);
		}

		$validation_result = $this->validate();
		if ( rgar( $validation_result, 'status' ) !== self::STATUS_SUCCESS ) {
			return $validation_result;
		}
		// $this->form_meta is populated during insert or update.
		if ( $this->form_id <= 0 ) {
			$save_result = $this->insert();
		} else {
			$save_result = $this->update();
		}

		ob_start();

			/**
			 * Fires after a form is saved
			 *
			 * Used to run additional actions after the form is saved
			 *
			 * @since 2.4.6.1 Added the $deleted_fields param.
			 * @since unknown
			 *
			 * @param array $form_meta      The form meta
			 * @param bool  $is_new         Returns true if this is a new form.
			 * @param array $deleted_fields The IDs of any fields which have been deleted.
			 */
			do_action( 'gform_after_save_form', $this->form_meta, rgar( $save_result, 'is_new' ), $this->deleted_fields );

		$after_save_markup = ob_get_clean();

		$save_result['actions_markup'] = array(
			'gform_after_save_form' => $after_save_markup,
		);

		return $save_result;
	}

	/**
	 * Validates the form data before updating or creating it.
	 *
	 * @since 2.6
	 *
	 * @return array An array containing the status of the validation and the form meta.
	 */
	private function validate() {

		$gf_forms_model = $this->gf_forms_model;

		// If form meta is not found, exit.
		if ( ! is_array( $this->form_meta ) ) {
			return array(
				'status' => self::STATUS_INVALID_META,
				'meta'   => null,
			);
		}

		if ( ! rgar( $this->form_meta, 'title' ) ) {
			return array(
				'status' => self::STATUS_MISSING_TITLE,
				'meta'   => null,
			);
		}

		// If form has a duplicate title, exit.
		$forms = $gf_forms_model::get_forms();
		foreach ( $forms as $form ) {
			if ( strtolower( $form->title ) == strtolower( $this->form_meta['title'] ) && rgar( $this->form_meta, 'id' ) != $form->id ) {
				return array(
					'status' => self::STATUS_DUPLICATE_TITLE,
					'meta'   => $this->form_meta,
				);
			}
		}

		return array(
			'status' => self::STATUS_SUCCESS,
			'meta'   => $this->form_meta,
		);
	}

	/**
	 * Performs any sanitization/formatting necessary before processing the form.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	private function cleanup() {

		$gf_forms_model = $this->gf_forms_model;
		$gf_common      = $this->gf_common;
		$gf_forms       = $this->gf_forms;
		$action         = rgpost( 'action' );

		if ( $action !== 'create_from_template' ) {
			// Clean up form meta JSON.
			$gf_common::log_debug( 'GF_Form_CRUD_Handler::cleanup(): Form meta json before stripslashes: ' . $this->form_json );

			$this->form_json = stripslashes( $this->form_json );
		}

		$gf_common::log_debug( 'GF_Form_CRUD_Handler::cleanup(): Form meta json before nl2br: ' . $this->form_json );
		$this->form_json = nl2br( $this->form_json );
		$gf_common::log_debug( 'GF_Form_CRUD_Handler::cleanup(): Final form meta json: ' . $this->form_json );

		// Convert form meta JSON to array.
		$this->form_meta = json_decode( $this->form_json, true );

		if ( ! is_array( $this->form_meta ) ) {
			return false;
		}

		$this->form_meta = $gf_forms_model::convert_field_objects( $this->form_meta );

		// Set version of Gravity Forms form was created with.
		if ( $this->form_id === 0 ) {
			$this->form_meta['version'] = $gf_forms::$version;
		}

		// Sanitize form settings.
		$this->form_meta = $gf_forms_model::maybe_sanitize_form_settings( $this->form_meta );

		$deleted_fields = $this->get_deleted_fields();
		$gf_common::log_debug( 'GF_Form_CRUD_Handler::cleanup(): Deleted fields ' . print_r( $deleted_fields, true ) );
		unset( $this->form_meta['deletedFields'] );
		$gf_common::log_debug( 'GF_Form_CRUD_Handler::cleanup(): Form meta => ' . print_r( $this->form_meta, true ) );

		return true;
	}

	/**
	 * Updates an existing form.
	 *
	 * @since 2.6
	 *
	 * @return array The result of the update and the resulting form meta.
	 */
	private function update() {

			$gf_forms_model = $this->gf_forms_model;
			$gf_common      = $this->gf_common;
			$gf_forms       = $this->gf_forms;
			$gf_api         = $this->gf_api;
			$rg_forms_model = $this->rg_forms_model;

			// Trim form meta values.
			$this->form_meta = $gf_forms_model::trim_form_meta_values( $this->form_meta );
			$deleted_fields  = $this->get_deleted_fields();
			// Delete fields.
		if ( ! empty( $deleted_fields ) ) {
			foreach ( $deleted_fields as $deleted_field_id ) {
				$this->form_meta = $gf_forms_model::delete_field( $this->form_meta, $deleted_field_id, false );
			}
		}

			// Save form meta.
			$gf_forms_model::update_form_meta( $this->form_id, $this->form_meta );

			// Update form title.
			$gf_api::update_form_property( $this->form_id, 'title', $this->form_meta['title'] );

			// Get form meta.
			$this->form_meta = $rg_forms_model::get_form_meta( $this->form_id );

		if ( ! empty( $deleted_fields ) ) {
			// Remove logic/routing rules based on deleted fields from confirmations and notifications.
			foreach ( $deleted_fields as $deleted_field ) {
				$this->form_meta = $gf_forms_model::delete_field_from_confirmations( $this->form_meta, $deleted_field );
				$this->form_meta = $gf_forms_model::delete_field_from_notifications( $this->form_meta, $deleted_field );
			}
		}

			return array(
				'status' => self::STATUS_SUCCESS,
				'meta'   => $this->form_meta,
				'is_new' => false,
			);
	}

	/**
	 * Creates a new form.
	 *
	 * @since 2.6
	 *
	 * @return array The status of the insert and the form meta data.
	 */
	private function insert() {
			$rg_forms_model = $this->rg_forms_model;
			$gf_forms_model = $this->gf_forms_model;

			// Inserting form.
			$this->form_id = $rg_forms_model::insert_form( $this->form_meta['title'] );

			// Updating object's id property.
			$this->form_meta['id'] = $this->form_id;

			// Use the notifications in form_meta if one is set. If not set, and default notification is not disabled by the hook, use default.
			$notifications = rgempty( 'notifications', $this->form_meta ) ? $this->get_default_notification() : rgar( $this->form_meta, 'notifications' );
			if ( ! empty( $notifications ) ) {
				// updating notifications form meta.
				$rg_forms_model::save_form_notifications( $this->form_id, $notifications );
			}

			// Use default confirmation if not set in form_meta.
			$confirmations = rgempty( 'confirmations', $this->form_meta ) ? $this->get_default_confirmation() : rgar( $this->form_meta, 'confirmations' );
			$gf_forms_model::save_form_confirmations( $this->form_id, $confirmations );

			// Adding markup version. Increment this when we make breaking changes to form markup.
			$this->form_meta['markupVersion'] = 2;

			// Removing notifications and confirmations from form meta.
			unset( $this->form_meta['confirmations'] );
			unset( $this->form_meta['notifications'] );

			// Updating form meta.
			$gf_forms_model::update_form_meta( $this->form_id, $this->form_meta );

			// Get form meta.
			$this->form_meta = $gf_forms_model::get_form_meta( $this->form_id );

			return array(
				'status' => self::STATUS_SUCCESS,
				'meta'   => $this->form_meta,
				'is_new' => true,
			);
	}

	/**
	 * Gets the default notifications.
	 *
	 * @since 2.7
	 *
	 * @return array Returns the array containing the default notifications.
	 */
	private function get_default_notification() {
		if ( ! apply_filters( 'gform_default_notification', true ) ) {
			return array();
		}

		$default_notification = array(
			'id'       => uniqid(),
			'isActive' => true,
			'to'       => '{admin_email}',
			'name'     => __( 'Admin Notification', 'gravityforms' ),
			'event'    => 'form_submission',
			'toType'   => 'email',
			'subject'  => __( 'New submission from', 'gravityforms' ) . ' {form_title}',
			'message'  => '{all_fields}',
		);

		return array( $default_notification['id'] => $default_notification );
	}

	/**
	 * Gets the default confirmations.
	 *
	 * @since 2.7
	 *
	 * @return array Returns the confirmation array.
	 */
	private function get_default_confirmation() {
		$gf_forms_model = $this->gf_forms_model;

		$confirmation  = $gf_forms_model::get_default_confirmation();
		return array( $confirmation['id'] => $confirmation );
	}

	/**
	 * Extracts the deleted field IDs from the form meta or returns them if they were already extracted before.
	 *
	 * @since 2.6
	 *
	 * @return int[]
	 */
	private function get_deleted_fields() {
		if ( empty( $this->deleted_fields ) ) {
			// Extract deleted field IDs.
			$this->deleted_fields = rgar( $this->form_meta, 'deletedFields' );
		}

		return $this->deleted_fields;
	}
}
