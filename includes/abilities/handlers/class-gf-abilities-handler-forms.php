<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Handles form ability callbacks.
 *
 * @since 3.1.0
 */
class GF_Abilities_Handler_Forms {

	/**
	 * Get a form by ID.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function get_form( $input ) {
		$form = \GFAPI::get_form( absint( $input['form_id'] ) );

		if ( false === $form ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		// Slim down field objects — remove empty/default properties that carry no information.
		if ( ! empty( $form['fields'] ) && is_array( $form['fields'] ) ) {
			$form['fields'] = array_values( array_map( array( __CLASS__, 'slim_field' ), $form['fields'] ) );
		}

		// These are object-typed in the output schema; empty PHP arrays encode as
		// [] and fail strict MCP client validation of structuredContent.
		foreach ( array( 'notifications', 'confirmations' ) as $key ) {
			if ( empty( $form[ $key ] ) || ! is_array( $form[ $key ] ) ) {
				$form[ $key ] = (object) array();
			}
		}

		return $form;
	}

	/**
	 * List forms.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array
	 */
	public static function list_forms( $input ) {
		$is_trash = isset( $input['trash'] ) ? (bool) $input['trash'] : false;

		// When fetching trashed forms, don't filter by active status — trashed forms have is_active=0
		// and GFAPI::get_forms() applies both conditions with AND, returning zero results otherwise.
		// When the caller does not pass 'active', don't filter either — the default result must
		// include inactive forms so agents can discover them.
		$active = ( $is_trash || ! isset( $input['active'] ) ) ? null : (bool) $input['active'];

		$forms = \GFAPI::get_forms(
			$active,
			$is_trash,
			isset( $input['sort_column'] ) ? $input['sort_column'] : 'id',
			isset( $input['sort_dir'] ) ? $input['sort_dir'] : 'ASC'
		);

		// Post-fetch title filtering — GFAPI::get_forms() doesn't support title search natively.
		if ( ! empty( $input['search'] ) ) {
			$search = strtolower( $input['search'] );
			$forms  = array_filter(
				$forms,
				function ( $form ) use ( $search ) {
					return str_contains( strtolower( rgar( $form, 'title', '' ) ), $search );
				}
			);
			$forms  = array_values( $forms );
		}

		// Return summaries only — full form objects are massive and will overwhelm AI agent context windows.
		return array_map(
			function ( $form ) {
				return array(
					'id'           => (int) rgar( $form, 'id' ),
					'title'        => rgar( $form, 'title', '' ),
					'is_active'    => rgar( $form, 'is_active', '0' ),
					'date_created' => rgar( $form, 'date_created', '' ),
					'is_trash'     => rgar( $form, 'is_trash', '0' ),
					'field_count'  => count( rgar( $form, 'fields', array() ) ),
				);
			},
			$forms
		);
	}

	/**
	 * Create a form.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function create_form( $input ) {
		$validation_error = self::validate_layout_properties( $input['form'] );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		$strip_result  = self::strip_ready_classes( $input['form'] );
		$input['form'] = self::prepare_compound_fields( $strip_result['form'] );

		if ( is_wp_error( $input['form'] ) ) {
			return $input['form'];
		}

		$input['form']            = self::sanitize_supplied_content( $input['form'] );
		$input['form']['version'] = \GFForms::$version;

		if ( isset( $input['form']['confirmations'] ) && is_array( $input['form']['confirmations'] ) ) {
			$input['form']['confirmations'] = self::pin_confirmation_defaults(
				self::key_confirmations_by_id( $input['form']['confirmations'] ),
				array()
			);
		}

		$input['form'] = self::apply_speed_check_dependency( $input['form'], $input['form'] );

		if ( is_wp_error( $input['form'] ) ) {
			return $input['form'];
		}

		$input['form'] = self::normalize_layout_group_ids( $input['form'] );
		$form_id       = \GFAPI::add_form( $input['form'] );

		if ( is_wp_error( $form_id ) ) {
			return $form_id;
		}

		$response = array(
			'success'  => true,
			'form_id'  => (int) $form_id,
			'edit_url' => admin_url( 'admin.php?page=gf_edit_forms&id=' . (int) $form_id ),
		);

		return self::add_ready_class_notice( $response, $strip_result['stripped'] );
	}

	/**
	 * Update a form.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function update_form( $input ) {
		$form_input = $input['form'];

		$validation_error = self::validate_layout_properties( $form_input );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		// Strip deprecated Ready Classes from agent-provided fields only; fields
		// not included in this update (and any admin-authored data) are untouched.
		$strip_result = self::strip_ready_classes( $form_input );
		$form_input   = $strip_result['form'];

		$form_input = self::sanitize_supplied_content( $form_input );

		if ( empty( $form_input['id'] ) ) {
			return new \WP_Error( 'gf_ability_missing_form_id', __( 'Missing form id', 'gravityforms' ) );
		}

		$form_id = absint( $form_input['id'] );

		// Fetch existing form to merge with.
		$existing_form = \GFAPI::get_form( $form_id );
		if ( ! $existing_form || is_wp_error( $existing_form ) ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found', 'gravityforms' ) );
		}

		// Trash state changes: is_trash is a table column, not display meta, so
		// GFAPI::update_form() cannot change it. Record the requested transition
		// and strip the property; the transition is applied after the meta update
		// so a payload like { title, is_trash } honors both changes.
		$trash_transition = null;

		if ( isset( $form_input['is_trash'] ) ) {
			$input_is_trash    = filter_var( $form_input['is_trash'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
			$existing_is_trash = isset( $existing_form['is_trash'] ) && '1' === (string) $existing_form['is_trash'];

			if ( false === $input_is_trash && $existing_is_trash ) {
				$trash_transition = 'restored';
			} elseif ( true === $input_is_trash && ! $existing_is_trash ) {
				$trash_transition = 'trashed';
			}

			unset( $form_input['is_trash'] );

			// Trash and restore are delete-level operations: the admin gates both
			// behind gravityforms_delete_forms (form_list.php), and forms-delete
			// requires it too. The edit capability alone must not bypass that.
			if ( $trash_transition && ! \GFCommon::current_user_can_any( 'gravityforms_delete_forms' ) ) {
				return new \WP_Error(
					'gf_ability_forbidden',
					__( 'Trashing or restoring forms requires the gravityforms_delete_forms capability.', 'gravityforms' )
				);
			}
		}

		// Merge input onto existing form.
		// - Scalar/array properties: input value replaces existing (including 'fields').
		// - Notifications/confirmations: merge by key so existing items are preserved.
		$merged_form = $existing_form;

		foreach ( $form_input as $key => $value ) {
			if ( 'notifications' === $key && is_array( $value ) ) {
				$merged_form['notifications'] = array_replace(
					isset( $existing_form['notifications'] ) ? $existing_form['notifications'] : array(),
					$value
				);
			} elseif ( 'confirmations' === $key && is_array( $value ) ) {
				$existing_confirmations       = isset( $existing_form['confirmations'] ) && is_array( $existing_form['confirmations'] ) ? $existing_form['confirmations'] : array();
				$merged_form['confirmations'] = self::pin_confirmation_defaults(
					array_replace( $existing_confirmations, self::key_confirmations_by_id( $value ) ),
					$existing_confirmations
				);
			} else {
				$merged_form[ $key ] = $value;
			}
		}

		$merged_form = self::apply_speed_check_dependency( $form_input, $merged_form );

		if ( is_wp_error( $merged_form ) ) {
			return $merged_form;
		}

		$merged_form['version'] = \GFForms::$version;

		// Only prepare fields the request actually supplied. A title-only or
		// settings-only update must leave stored fields byte-for-byte unchanged —
		// running the editor-default backfills over merged legacy fields would
		// change their behavior (validateState, storageType, …) uninvited.
		if ( isset( $form_input['fields'] ) ) {
			$existing_field_ids = array();

			if ( ! empty( $existing_form['fields'] ) && is_array( $existing_form['fields'] ) ) {
				foreach ( $existing_form['fields'] as $existing_field ) {
					$existing_field_ids[] = (int) ( is_object( $existing_field ) ? $existing_field->id : rgar( $existing_field, 'id' ) );
				}
			}

			$merged_form = self::prepare_compound_fields( $merged_form, $existing_field_ids );

			if ( is_wp_error( $merged_form ) ) {
				return $merged_form;
			}

			$merged_form = self::normalize_layout_group_ids( $merged_form );
		}

		$result = \GFAPI::update_form( $merged_form );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// trash_form()/restore_form() boolean returns are unreliable (inverted
		// query-result check); only a WP_Error (submissions blocked) is actionable.
		if ( $trash_transition ) {
			$transition_result = 'trashed' === $trash_transition
				? \GFFormsModel::trash_form( $form_id )
				: \GFFormsModel::restore_form( $form_id );

			if ( is_wp_error( $transition_result ) ) {
				return $transition_result;
			}
		}

		$response = array( 'success' => (bool) $result );

		if ( $trash_transition ) {
			$response[ $trash_transition ] = true;
		}

		return self::add_ready_class_notice( $response, $strip_result['stripped'] );
	}

	/**
	 * Delete a form.
	 *
	 * By default, moves the form to the trash (recoverable from the admin UI).
	 * Pass force: true to permanently delete the form and all its data.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function delete_form( $input ) {
		$form_id = absint( $input['form_id'] );
		$force   = ! empty( $input['force'] );

		if ( $force ) {
			$form = \GFAPI::get_form( $form_id );
			if ( false === $form ) {
				return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
			}

			if ( ( $input['confirmation'] ?? '' ) !== $form['title'] ) {
				return new \WP_Error( 'gf_ability_confirmation_mismatch', __( 'Confirmation does not match the form title. Please provide the exact form title to confirm deletion.', 'gravityforms' ) );
			}

			$result = \GFAPI::delete_form( $form_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return array( 'success' => true, 'trashed' => false );
		}

		// Verify form exists before trashing.
		$form = \GFAPI::get_form( $form_id );
		if ( false === $form ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		\GFFormsModel::trash_form( $form_id );

		return array( 'success' => true, 'trashed' => true );
	}

	/**
	 * Duplicate a form.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function duplicate_form( $input ) {
		$source_id = absint( $input['form_id'] );

		$source = \GFAPI::get_form( $source_id );
		if ( $source && '1' === (string) rgar( $source, 'is_trash' ) && ! \GFCommon::current_user_can_any( 'gravityforms_delete_forms' ) ) {
			return new \WP_Error(
				'gf_ability_forbidden',
				__( 'Duplicating a trashed form requires the gravityforms_delete_forms capability.', 'gravityforms' )
			);
		}

		$feeds = \GFAPI::get_feeds( null, $source_id, null, null );
		if ( is_array( $feeds ) ) {
			$checked = array();
			foreach ( $feeds as $feed ) {
				$slug = (string) rgar( $feed, 'addon_slug' );
				if ( isset( $checked[ $slug ] ) ) {
					continue;
				}
				$checked[ $slug ] = true;

				$addon = \GFAddOn::get_addon_by_slug( $slug );
				$caps  = $addon ? $addon->get_form_settings_capabilities() : array();

				if ( ! \GFCommon::current_user_can_any( $caps ) ) {
					return new \WP_Error(
						'gf_ability_forbidden',
						/* translators: %s: add-on slug */
						sprintf( __( 'Duplicating this form requires permission to manage its %s feeds.', 'gravityforms' ), $slug )
					);
				}
			}
		}

		$form_id = \GFAPI::duplicate_form( $source_id );

		if ( is_wp_error( $form_id ) ) {
			return $form_id;
		}

		return array(
			'success'  => true,
			'form_id'  => (int) $form_id,
			'edit_url' => admin_url( 'admin.php?page=gf_edit_forms&id=' . (int) $form_id ),
		);
	}

	/**
	 * Analyze conditional logic on a form.
	 *
	 * Extracts conditional logic from all locations (fields, notifications,
	 * confirmations, submit button, page buttons) and returns an agent-optimized
	 * dependency map with cross-referenced field labels.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function analyze_form_logic( $input ) {
		$form = \GFAPI::get_form( absint( $input['form_id'] ) );

		if ( false === $form ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		$fields = rgar( $form, 'fields', array() );

		// Build field label map for cross-referencing rules to readable labels.
		$field_map = array();
		foreach ( $fields as $field ) {
			$id    = is_object( $field ) ? $field->id : rgar( $field, 'id' );
			$label = is_object( $field ) ? $field->label : rgar( $field, 'label', '' );
			$type  = is_object( $field ) ? $field->type : rgar( $field, 'type', '' );

			$field_map[ (string) $id ] = array(
				'label' => (string) $label,
				'type'  => (string) $type,
			);
		}

		$field_logic        = array();
		$page_logic         = array();
		$dependency_entries = array();

		// 1. Field-level conditional logic.
		foreach ( $fields as $field ) {
			$cl = is_object( $field ) ? $field->conditionalLogic : rgar( $field, 'conditionalLogic' );

			if ( empty( $cl ) || ! is_array( $cl ) ) {
				// Check page field buttons for conditional logic.
				$field_type = is_object( $field ) ? $field->type : rgar( $field, 'type' );
				if ( 'page' === $field_type ) {
					$next_button = is_object( $field ) ? $field->nextButton : rgar( $field, 'nextButton' );
					$next_cl     = is_array( $next_button ) ? rgar( $next_button, 'conditionalLogic' ) : null;

					if ( ! empty( $next_cl ) && is_array( $next_cl ) ) {
						$page_number = is_object( $field ) ? $field->pageNumber : rgar( $field, 'pageNumber' );
						$enriched    = self::enrich_logic( $next_cl, $field_map );

						$page_logic[] = array(
							'page_number' => (int) $page_number,
							'button'      => 'next',
							'action'      => $enriched['action'],
							'logic_type'  => $enriched['logic_type'],
							'rules'       => $enriched['rules'],
						);

						self::collect_dependencies( $enriched['rules'], 'page', $page_number, 'Page ' . $page_number . ' Next', $enriched['action'], $enriched['logic_type'], $dependency_entries );
					}
				}
				continue;
			}

			$field_id    = (int) ( is_object( $field ) ? $field->id : rgar( $field, 'id' ) );
			$field_label = (string) ( is_object( $field ) ? $field->label : rgar( $field, 'label', '' ) );
			$field_type  = (string) ( is_object( $field ) ? $field->type : rgar( $field, 'type', '' ) );
			$enriched    = self::enrich_logic( $cl, $field_map );

			$field_logic[] = array(
				'field_id'    => $field_id,
				'field_label' => $field_label,
				'field_type'  => $field_type,
				'action'      => $enriched['action'],
				'logic_type'  => $enriched['logic_type'],
				'rules'       => $enriched['rules'],
			);

			self::collect_dependencies( $enriched['rules'], 'field', $field_id, $field_label, $enriched['action'], $enriched['logic_type'], $dependency_entries );
		}

		// 2. Notification conditional logic.
		$notification_logic = array();
		$notifications      = rgar( $form, 'notifications', array() );

		if ( is_array( $notifications ) ) {
			foreach ( $notifications as $notification_id => $notification ) {
				$cl = rgar( $notification, 'conditionalLogic' );

				if ( empty( $cl ) || ! is_array( $cl ) ) {
					continue;
				}

				$enriched = self::enrich_logic( $cl, $field_map );

				$notification_logic[] = array(
					'notification_id'   => (string) $notification_id,
					'notification_name' => rgar( $notification, 'name', '' ),
					'action'            => $enriched['action'],
					'logic_type'        => $enriched['logic_type'],
					'rules'             => $enriched['rules'],
				);

				self::collect_dependencies( $enriched['rules'], 'notification', $notification_id, rgar( $notification, 'name', '' ), $enriched['action'], $enriched['logic_type'], $dependency_entries );
			}
		}

		// 3. Confirmation conditional logic.
		$confirmation_logic = array();
		$confirmations      = rgar( $form, 'confirmations', array() );

		if ( is_array( $confirmations ) ) {
			foreach ( $confirmations as $confirmation_id => $confirmation ) {
				$cl = rgar( $confirmation, 'conditionalLogic' );

				if ( empty( $cl ) || ! is_array( $cl ) ) {
					continue;
				}

				$enriched = self::enrich_logic( $cl, $field_map );

				$confirmation_logic[] = array(
					'confirmation_id'   => (string) $confirmation_id,
					'confirmation_name' => rgar( $confirmation, 'name', '' ),
					'action'            => $enriched['action'],
					'logic_type'        => $enriched['logic_type'],
					'rules'             => $enriched['rules'],
				);

				self::collect_dependencies( $enriched['rules'], 'confirmation', $confirmation_id, rgar( $confirmation, 'name', '' ), $enriched['action'], $enriched['logic_type'], $dependency_entries );
			}
		}

		// 4. Submit button conditional logic.
		$submit_button_logic = null;
		$button              = rgar( $form, 'button', array() );
		$button_cl           = is_array( $button ) ? rgar( $button, 'conditionalLogic' ) : null;

		if ( ! empty( $button_cl ) && is_array( $button_cl ) ) {
			$enriched            = self::enrich_logic( $button_cl, $field_map );
			$submit_button_logic = array(
				'action'     => $enriched['action'],
				'logic_type' => $enriched['logic_type'],
				'rules'      => $enriched['rules'],
			);

			self::collect_dependencies( $enriched['rules'], 'submit_button', 0, 'Submit Button', $enriched['action'], $enriched['logic_type'], $dependency_entries );
		}

		// Controls are keyed by target during collection for dedupe; reindex so
		// they serialize as JSON arrays.
		foreach ( $dependency_entries as &$dependency ) {
			$dependency['controls'] = array_values( $dependency['controls'] );
		}
		unset( $dependency );

		// Count total rules across all locations.
		$total_rules = 0;
		foreach ( $field_logic as $fl ) {
			$total_rules += count( $fl['rules'] );
		}
		foreach ( $notification_logic as $nl ) {
			$total_rules += count( $nl['rules'] );
		}
		foreach ( $confirmation_logic as $cl_item ) {
			$total_rules += count( $cl_item['rules'] );
		}
		foreach ( $page_logic as $pl ) {
			$total_rules += count( $pl['rules'] );
		}
		if ( $submit_button_logic ) {
			$total_rules += count( $submit_button_logic['rules'] );
		}

		return array(
			'form_id'             => (int) rgar( $form, 'id' ),
			'form_title'          => rgar( $form, 'title', '' ),
			'summary'             => array(
				'total_rules'              => $total_rules,
				'fields_with_logic'        => count( $field_logic ),
				'notifications_with_logic' => count( $notification_logic ),
				'confirmations_with_logic' => count( $confirmation_logic ),
				'has_submit_button_logic'  => ! empty( $submit_button_logic ),
				'has_page_button_logic'    => ! empty( $page_logic ),
			),
			'field_logic'         => $field_logic,
			'notification_logic'  => $notification_logic,
			'confirmation_logic'  => $confirmation_logic,
			'submit_button_logic' => $submit_button_logic,
			'page_logic'          => $page_logic,
			// Object-typed in the output schema; an empty PHP array encodes as []
			// and fails strict MCP client validation of structuredContent.
			'dependency_map'      => empty( $dependency_entries ) ? (object) array() : $dependency_entries,
		);
	}

	/**
	 * Prepare compound fields by populating their inputs arrays.
	 *
	 * When forms are created via the form editor UI, compound field inputs
	 * (name, address, time) are populated by JavaScript. When created via the
	 * API, this doesn't happen — fields are stored without inputs, which breaks
	 * validation, rendering, and entry storage.
	 *
	 * This method normalizes agent-supplied fields into editor-equivalent
	 * fields: it assigns missing field IDs, applies the editor's per-type
	 * defaults to NEW fields (size, validateState, formats, storage types),
	 * populates missing inputs for compound and choice fields, validates and
	 * links pricing field product relationships, and keeps the form-level
	 * pagination object in sync with the page break count.
	 *
	 * @since 3.1.0
	 *
	 * @param array $form               The form array containing a 'fields' key.
	 * @param int[] $existing_field_ids IDs of fields already stored on the form. Editor defaults apply only to fields whose ID is not in this list; round-tripped stored fields keep their configuration as supplied.
	 *
	 * @return array|\WP_Error The prepared form, or a WP_Error for invalid field relationships.
	 */
	private static function prepare_compound_fields( $form, $existing_field_ids = array() ) {
		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return $form;
		}

		$compound_inputs = GF_Abilities_Handler_System::get_compound_field_inputs();

		// Ensure every field has an ID so we can build sub-input IDs. Seed the
		// allocator from the stored form's IDs too: when an update replaces the
		// whole field list with ID-less fields, allocating from the submitted
		// array alone would reuse stored IDs and misclassify new fields as
		// existing, skipping their editor defaults.
		$max_id = 0;
		foreach ( $form['fields'] as $field ) {
			$fid = isset( $field['id'] ) ? (int) $field['id'] : 0;
			if ( $fid > $max_id ) {
				$max_id = $fid;
			}
		}

		foreach ( $existing_field_ids as $existing_id ) {
			if ( $existing_id > $max_id ) {
				$max_id = $existing_id;
			}
		}

		$next_id = $max_id + 1;

		foreach ( $form['fields'] as &$field ) {
			if ( empty( $field['id'] ) ) {
				$field['id'] = $next_id++;
			}

			// Sanitize defaultValue — must be a scalar string, not an array.
			if ( isset( $field['defaultValue'] ) && ! is_scalar( $field['defaultValue'] ) ) {
				$field['defaultValue'] = '';
			}

			// Mirror UpgradeEmailField (form_editor.js): an email field with
			// confirmation enabled needs its two inputs or the confirm input never
			// renders. The editor repairs stored fields on load; do the same here
			// for new and round-tripped fields alike.
			$is_email = 'email' === ( $field['type'] ?? '' ) || 'email' === ( $field['inputType'] ?? '' );

			if ( $is_email && ! empty( $field['emailConfirmEnabled'] ) && empty( $field['inputs'] ) ) {
				$email_input = array( 'id' => (string) $field['id'], 'label' => __( 'Enter Email', 'gravityforms' ), 'name' => '', 'autocompleteAttribute' => 'email' );

				// Confirm-enabled rendering reads input-level placeholders, so the
				// editor repair copies the field placeholder to the primary input.
				if ( ! empty( $field['placeholder'] ) && is_scalar( $field['placeholder'] ) ) {
					$email_input['placeholder'] = $field['placeholder'];
				}

				$field['inputs'] = array(
					$email_input,
					array( 'id' => $field['id'] . '.2', 'label' => __( 'Confirm Email', 'gravityforms' ), 'name' => '', 'autocompleteAttribute' => 'email' ),
				);
			}

			// Editor defaults apply to NEW fields only. Fields that already exist
			// on the stored form arrive here on forms-update round-trips; changing
			// their behavior (validateState, storageType, formats) uninvited could
			// break live forms.
			$is_new = ! in_array( (int) $field['id'], $existing_field_ids, true );

			if ( ! $is_new ) {
				continue;
			}

			// The editor gives every new field size "large"; GFAPI leaves it empty.
			if ( empty( $field['size'] ) ) {
				$field['size'] = 'large';
			}

			$type = isset( $field['type'] ) ? $field['type'] : '';

			// The editor enables state validation (anti-tamper: submitted values
			// must match the configured choices/values) on these types; without
			// it, agent-created fields accept arbitrary submitted values.
			$validate_state_types = array( 'select', 'checkbox', 'radio', 'multiselect', 'multi_choice', 'image_choice', 'hidden', 'name', 'address', 'option' );

			if ( in_array( $type, $validate_state_types, true ) && ! isset( $field['validateState'] ) ) {
				$field['validateState'] = true;
			}

			// Auto-populate inputs for compound fields that don't already have them.
			if ( isset( $compound_inputs[ $type ] ) && empty( $field['inputs'] ) ) {
				$field_id = $field['id'];
				$inputs   = array();

				foreach ( $compound_inputs[ $type ] as $input_def ) {
					$input = array(
						'id'    => $field_id . $input_def['id'],
						'label' => $input_def['label'],
					);

					if ( ! empty( $input_def['hidden_by_default'] ) ) {
						$input['isHidden'] = true;
					}

					$inputs[] = $input;
				}

				$field['inputs'] = $inputs;
			}

			// Apply sensible defaults for field types that need them.
			// These run regardless of whether inputs were auto-populated above,
			// so cloned fields (which already have inputs) still get correct defaults.
			self::apply_field_defaults( $field, isset( $form['id'] ) ? (int) $form['id'] : 0 );
		}

		unset( $field );

		// Link quantity/option fields to a product and validate explicit links.
		// Runtime pricing selects dependents strictly by productField equality,
		// so an orphaned or wrong link silently corrupts totals.
		$form['fields'] = array_values( $form['fields'] );
		$product_ids    = array();

		foreach ( $form['fields'] as $index => $pricing_field ) {
			if ( 'product' === ( $pricing_field['type'] ?? '' ) ) {
				$product_ids[ $index ] = (int) $pricing_field['id'];
			}
		}

		foreach ( $form['fields'] as $index => &$field ) {
			if ( ! in_array( $field['type'] ?? '', array( 'quantity', 'option' ), true ) ) {
				continue;
			}

			$explicit = isset( $field['productField'] ) ? (int) $field['productField'] : 0;

			if ( $explicit && in_array( $explicit, $product_ids, true ) ) {
				continue;
			}

			if ( $explicit ) {
				return new \WP_Error(
					'gf_ability_invalid_product_field',
					sprintf(
						/* translators: 1: field ID, 2: field type, 3: referenced product field ID */
						__( 'Field %1$d (%2$s) references productField %3$d, which is not a product field on this form.', 'gravityforms' ),
						$field['id'],
						$field['type'],
						$explicit
					)
				);
			}

			// Never auto-link round-tripped stored fields — the same
			// new-fields-only rule the editor defaults follow.
			if ( in_array( (int) $field['id'], $existing_field_ids, true ) ) {
				continue;
			}

			if ( empty( $product_ids ) ) {
				return new \WP_Error(
					'gf_ability_missing_product',
					sprintf(
						/* translators: 1: field ID, 2: field type */
						__( 'Field %1$d (%2$s) requires a product field on the form. Add a product field, or remove this field.', 'gravityforms' ),
						$field['id'],
						$field['type']
					)
				);
			}

			// Nearest preceding product, else the first product on the form.
			$target = 0;

			foreach ( $product_ids as $product_index => $product_id ) {
				if ( $product_index >= $index ) {
					break;
				}

				$target = $product_id;
			}

			$field['productField'] = $target ? $target : reset( $product_ids );
		}

		unset( $field );

		// Multi-page forms need a form-level pagination object (one label per
		// page) or the editor and progress bar misbehave. The editor maintains
		// this; GFAPI does not. Backfill it, and keep the labels array sized to
		// the real page count when fields change on update.
		$page_count = count(
			array_filter(
				$form['fields'],
				function ( $field ) {
					return 'page' === ( $field['type'] ?? '' );
				}
			)
		);

		if ( $page_count > 0 ) {
			$total_pages = $page_count + 1;
			$pagination  = isset( $form['pagination'] ) && is_array( $form['pagination'] ) ? $form['pagination'] : array();
			$pages       = isset( $pagination['pages'] ) && is_array( $pagination['pages'] ) ? $pagination['pages'] : array();

			$pagination['type']  = isset( $pagination['type'] ) ? $pagination['type'] : 'percentage';
			$pagination['style'] = isset( $pagination['style'] ) ? $pagination['style'] : 'blue';
			$pagination['pages'] = array_pad( array_slice( $pages, 0, $total_pages ), $total_pages, '' );

			$form['pagination'] = $pagination;
		} elseif ( isset( $form['pagination'] ) ) {
			// The submitted field list removed the last page break; the editor
			// stores null pagination for single-page forms.
			$form['pagination'] = null;
		}

		return $form;
	}

	/**
	 * Apply the unfiltered_html kses gate to the caller-supplied HTML sink values in a form payload.
	 *
	 * GFAPI does not sanitize form writes, so this applies the gate to the form-level markup keys.
	 * Fields are sanitized separately in sanitize_supplied_field.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The caller-supplied form properties.
	 *
	 * @return array
	 */
	private static function sanitize_supplied_content( $input ) {
		if ( ! is_array( $input ) ) {
			return $input;
		}

		$message_keys = array( 'description', 'limitEntriesMessage', 'schedulePendingMessage', 'scheduleMessage', 'requireLoginMessage' );

		foreach ( $message_keys as $key ) {
			if ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) ) {
				$input[ $key ] = \GFCommon::maybe_wp_kses( $input[ $key ] );
			}
		}

		foreach ( array( 'title', 'cssClass' ) as $key ) {
			if ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) ) {
				$input[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		foreach ( array( 'scheduleStart', 'scheduleEnd' ) as $key ) {
			if ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) ) {
				$input[ $key ] = wp_strip_all_tags( $input[ $key ] );
			}
		}

		if ( isset( $input['button'] ) && is_array( $input['button'] ) ) {
			foreach ( array( 'text', 'imageUrl' ) as $key ) {
				if ( isset( $input['button'][ $key ] ) && is_string( $input['button'][ $key ] ) ) {
					$input['button'][ $key ] = sanitize_text_field( $input['button'][ $key ] );
				}
			}

			if ( isset( $input['button']['conditionalLogic'] ) ) {
				$input['button']['conditionalLogic'] = \GFFormsModel::sanitize_conditional_logic( $input['button']['conditionalLogic'] );
			}
		}

		if ( isset( $input['save']['button']['text'] ) && is_string( $input['save']['button']['text'] ) ) {
			$input['save']['button']['text'] = sanitize_text_field( $input['save']['button']['text'] );
		}

		if ( ! empty( $input['fields'] ) && is_array( $input['fields'] ) ) {
			foreach ( $input['fields'] as &$field ) {
				if ( is_array( $field ) ) {
					$field = self::sanitize_supplied_field( $field );
				}
			}

			unset( $field );
		}

		if ( ! empty( $input['notifications'] ) && is_array( $input['notifications'] ) ) {
			foreach ( $input['notifications'] as $key => $notification ) {
				if ( is_array( $notification ) ) {
					$input['notifications'][ $key ] = GF_Abilities_Handler_Notifications::sanitize( $notification );
				}
			}
		}

		if ( ! empty( $input['confirmations'] ) && is_array( $input['confirmations'] ) ) {
			foreach ( $input['confirmations'] as $key => $confirmation ) {
				if ( is_array( $confirmation ) ) {
					$input['confirmations'][ $key ] = GF_Abilities_Handler_Confirmations::sanitize( $confirmation );
				}
			}
		}

		return $input;
	}

	/**
	 * Sanitize a single supplied field's markup sinks.
	 *
	 * It runs the admin's own GF_Field::sanitize_settings(), then takes back only the keys the caller
	 * supplied. This keeps the partial-update shape and cannot drift behind the admin.
	 *
	 * @since 3.1.0
	 *
	 * @param array $field The caller-supplied field.
	 *
	 * @return array
	 */
	private static function sanitize_supplied_field( $field ) {
		$object = \GF_Fields::create( $field );
		$object->sanitize_settings();

		return array_intersect_key( (array) $object, $field );
	}

	/**
	 * Re-key a confirmations array by each item's inner id.
	 *
	 * GFAPI keys confirmations by inner id when it saves, so the nested path must key the same way
	 * before it merges. Otherwise a caller can use a mismatched outer key to overwrite the stored
	 * default.
	 *
	 * @since 3.1.0
	 *
	 * @param array $confirmations The confirmations to re-key.
	 *
	 * @return array
	 */
	private static function key_confirmations_by_id( $confirmations ) {
		if ( ! is_array( $confirmations ) ) {
			return $confirmations;
		}

		$keyed = array();

		foreach ( $confirmations as $key => $confirmation ) {
			if ( is_array( $confirmation ) && isset( $confirmation['id'] ) && '' !== (string) $confirmation['id'] ) {
				$keyed[ (string) $confirmation['id'] ] = $confirmation;
			} else {
				$keyed[ $key ] = $confirmation;
			}
		}

		return $keyed;
	}

	/**
	 * Preserve the default-confirmation invariant across a wholesale confirmations write.
	 *
	 * It pins each existing confirmation's isDefault to its stored value and keeps at most one
	 * default. If none remains it designates the first, so the form always keeps a fallback.
	 *
	 * @since 3.1.0
	 *
	 * @param array $confirmations The confirmations to write (already merged and id-keyed on update).
	 * @param array $existing      The stored confirmations, keyed by id (empty on create).
	 *
	 * @return array
	 */
	private static function pin_confirmation_defaults( $confirmations, $existing ) {
		if ( ! is_array( $confirmations ) ) {
			return $confirmations;
		}

		$existing     = is_array( $existing ) ? $existing : array();
		$seen_default = false;

		foreach ( $confirmations as $id => &$confirmation ) {
			if ( ! is_array( $confirmation ) ) {
				continue;
			}

			if ( array_key_exists( $id, $existing ) && is_array( $existing[ $id ] ) ) {
				if ( array_key_exists( 'isDefault', $existing[ $id ] ) ) {
					$confirmation['isDefault'] = $existing[ $id ]['isDefault'];
				} else {
					unset( $confirmation['isDefault'] );
				}
			}

			if ( ! empty( $confirmation['isDefault'] ) ) {
				if ( $seen_default ) {
					unset( $confirmation['isDefault'] );
				} else {
					$seen_default = true;
				}
			}
		}

		unset( $confirmation );

		if ( ! $seen_default ) {
			foreach ( $confirmations as $id => $confirmation ) {
				if ( is_array( $confirmation ) ) {
					$confirmations[ $id ]['isDefault'] = true;
					break;
				}
			}
		}

		return $confirmations;
	}

	/**
	 * Keep the speed check dependent on the honeypot.
	 *
	 * Core only runs the speed check inside honeypot validation
	 * (GF_Honeypot_Handler::is_honeypot_enabled), and the settings UI nests
	 * the speed check under the honeypot toggle. Without the honeypot the
	 * speed check is stored but never runs, and its settings stay hidden.
	 * Enabling the speed check enables the honeypot; disabling the honeypot
	 * while the resulting form has the speed check on returns an error.
	 *
	 * @since 3.1.0
	 *
	 * @param array $request The form properties supplied by the caller.
	 * @param array $form    The form the change applies to (the merged form on update).
	 *
	 * @return array|\WP_Error
	 */
	private static function apply_speed_check_dependency( $request, $form ) {
		// Act only when the request touches either property. The UI itself can
		// store honeypot-off with speed-check-on (inert), so an update that
		// touches neither must not rewrite stored settings.
		$disables_honeypot = isset( $request['enableHoneypot'] ) && ! $request['enableHoneypot'];

		if ( $disables_honeypot && ! empty( $form['enableSubmitSpeedCheck'] ) ) {
			return new \WP_Error(
				'gf_ability_speed_check_requires_honeypot',
				__( 'The submission speed check only runs when the honeypot is enabled. Set enableHoneypot to true, or also disable enableSubmitSpeedCheck.', 'gravityforms' )
			);
		}

		if ( ! empty( $request['enableSubmitSpeedCheck'] ) ) {
			$form['enableHoneypot'] = true;
		}

		return $form;
	}

	/**
	 * Apply sensible defaults to a field array.
	 *
	 * The GF form editor sets certain properties automatically when a field is
	 * created through the UI. API-created fields don't get that treatment, which
	 * can cause rendering issues (e.g., name sub-inputs stacking instead of
	 * appearing side-by-side). This method fills in those defaults so that
	 * API-created fields match UI-created behaviour out of the box.
	 *
	 * @since 3.1.0
	 *
	 * @param array $field   Field array (modified by reference via caller).
	 * @param int   $form_id The form ID (0 on create, before the form exists), used by filterable per-form defaults.
	 */
	private static function apply_field_defaults( &$field, $form_id = 0 ) {
		$type = isset( $field['type'] ) ? $field['type'] : '';

		switch ( $type ) {
			case 'name':
				// 'advanced' renders First/Last side-by-side; without it they stack.
				if ( empty( $field['nameFormat'] ) ) {
					$field['nameFormat'] = 'advanced';
				}
				break;

			case 'consent':
				if ( empty( $field['inputType'] ) ) {
					$field['inputType'] = 'consent';
				}

				if ( empty( $field['checkboxLabel'] ) ) {
					$field['checkboxLabel'] = __( 'I agree to the terms.', 'gravityforms' );
				}

				if ( empty( $field['descriptionPlaceholder'] ) ) {
					$field['descriptionPlaceholder'] = __( 'Enter consent agreement text here.  The Consent Field will store this agreement text with the form entry in order to track what the user has consented to.', 'gravityforms' );
				}

				if ( empty( $field['choices'] ) ) {
					$field['choices'] = array(
						array(
							'text'  => 'Checked',
							'value' => '1',
						),
					);
				}

				if ( empty( $field['inputs'] ) ) {
					$field_id        = $field['id'];
					$field['inputs'] = array(
						array(
							'id'    => $field_id . '.1',
							'label' => 'Consent',
							'name'  => '',
						),
						array(
							'id'       => $field_id . '.2',
							'label'    => 'Text',
							'name'     => '',
							'isHidden' => true,
						),
						array(
							'id'       => $field_id . '.3',
							'label'    => 'Description',
							'name'     => '',
							'isHidden' => true,
						),
					);
				}
				break;

			case 'multiselect':
				// Without storageType 'json', values are stored as comma-separated strings.
				// This causes data loss when choice values contain commas (e.g., "Atlanta, GA").
				// The form editor sets this via JS SetDefaultValues; GFAPI does not.
				if ( empty( $field['storageType'] ) ) {
					$field['storageType'] = 'json';
				}
				break;

			case 'multi_choice':
			case 'image_choice':
				// The editor builds one input per choice with a shared key linking
				// choice to input (IDs skip multiples of 10, like checkbox),
				// defaults the variant to single-select radio, and sets the
				// type-specific display defaults. GFAPI does none of it.
				if ( empty( $field['inputType'] ) ) {
					$field['inputType'] = 'radio';
				}

				if ( 'multi_choice' === $type && empty( $field['selectAllText'] ) ) {
					$field['selectAllText'] = __( 'Select All', 'gravityforms' );
				}

				if ( 'image_choice' === $type && empty( $field['imageChoiceLabelVisibility'] ) ) {
					$field['imageChoiceLabelVisibility'] = 'show';
				}

				if ( empty( $field['inputs'] ) && ! empty( $field['choices'] ) && is_array( $field['choices'] ) ) {
					$field['choices'] = array_values( $field['choices'] );
					$inputs           = array();
					$skip             = 0;

					foreach ( $field['choices'] as $i => &$choice ) {
						if ( empty( $choice['key'] ) ) {
							$choice['key'] = strtolower( wp_generate_password( 17, false ) );
						}

						// Input IDs skip multiples of 10, matching the editor —
						// 5.10 float-equals 5.1 in GF input lookups.
						if ( 0 === ( $i + 1 + $skip ) % 10 ) {
							++$skip;
						}

						$inputs[] = array(
							'id'    => $field['id'] . '.' . ( $i + 1 + $skip ),
							'label' => rgar( $choice, 'text', rgar( $choice, 'value', '' ) ),
							'name'  => '',
							'key'   => $choice['key'],
						);
					}

					unset( $choice );
					$field['inputs'] = $inputs;
				}
				break;

			case 'checkbox':
				// The editor builds one input per choice (SetFieldCheckboxInputs in
				// form_editor.js); GFAPI does not, and without inputs submitted
				// checkbox values are silently dropped by the entry pipeline. Input
				// IDs skip multiples of 10 so 5.10 never conflicts with 5.1 + "0".
				if ( empty( $field['inputs'] ) && ! empty( $field['choices'] ) && is_array( $field['choices'] ) ) {
					$inputs = array();
					$skip   = 0;

					foreach ( array_values( $field['choices'] ) as $i => $choice ) {
						if ( 0 === ( $i + 1 + $skip ) % 10 ) {
							++$skip;
						}

						$inputs[] = array(
							'id'    => $field['id'] . '.' . ( $i + 1 + $skip ),
							'label' => rgar( $choice, 'text', rgar( $choice, 'value', '' ) ),
						);
					}

					$field['inputs'] = $inputs;
				}
				break;

			case 'date':
				// The editor default is the three-input date field (Month/Day/Year).
				if ( empty( $field['dateType'] ) ) {
					$field['dateType'] = 'datefield';
				}

				if ( 'datefield' === $field['dateType'] && empty( $field['inputs'] ) ) {
					$field['inputs'] = array(
						array( 'id' => $field['id'] . '.1', 'label' => __( 'Month', 'gravityforms' ), 'name' => '' ),
						array( 'id' => $field['id'] . '.2', 'label' => __( 'Day', 'gravityforms' ), 'name' => '' ),
						array( 'id' => $field['id'] . '.3', 'label' => __( 'Year', 'gravityforms' ), 'name' => '' ),
					);
				}
				break;

			case 'textarea':
				if ( empty( $field['textareaHeight'] ) ) {
					$field['textareaHeight'] = 'large';
				}
				break;

			case 'address':
				if ( empty( $field['addressType'] ) ) {
					// The editor default is filterable per site and form
					// (gform_default_address_type); mirror it, do not hardcode.
					$address_field        = \GF_Fields::get( 'address' );
					$field['addressType'] = $address_field instanceof \GF_Field_Address
						? $address_field->get_default_address_type( $form_id )
						: 'international';
				}
				break;

			case 'website':
				if ( empty( $field['autocompleteAttribute'] ) ) {
					$field['autocompleteAttribute'] = 'url';
				}
				break;

			case 'email':
				if ( empty( $field['autocompleteAttribute'] ) ) {
					$field['autocompleteAttribute'] = 'email';
				}
				break;

			case 'post_category':
				if ( empty( $field['inputType'] ) ) {
					$field['inputType'] = 'select';
				}

				if ( ! isset( $field['displayAllCategories'] ) ) {
					$field['displayAllCategories'] = true;
				}
				break;

			case 'post_custom_field':
				if ( empty( $field['inputType'] ) ) {
					$field['inputType'] = 'text';
				}
				break;

			case 'post_image':
				if ( empty( $field['allowedExtensions'] ) ) {
					$field['allowedExtensions'] = 'jpg, jpeg, png, gif';
				}
				break;

			case 'quantity':
				// Editor defaults: plain number input, decimal-dot format.
				if ( empty( $field['inputType'] ) ) {
					$field['inputType'] = 'number';
				}

				if ( empty( $field['numberFormat'] ) ) {
					$field['numberFormat'] = 'decimal_dot';
				}
				break;

			case 'option':
				// Editor defaults: select variant with per-choice pricing enabled.
				if ( empty( $field['inputType'] ) ) {
					$field['inputType'] = 'select';
				}

				if ( ! isset( $field['enablePrice'] ) ) {
					$field['enablePrice'] = true;
				}
				break;

			case 'time':
				// Editor default: 12-hour format.
				if ( empty( $field['timeFormat'] ) ) {
					$field['timeFormat'] = '12';
				}
				break;

			case 'number':
				// Editor default: decimal-dot number format, used by validation.
				if ( empty( $field['numberFormat'] ) ) {
					$field['numberFormat'] = 'decimal_dot';
				}
				break;

			case 'fileupload':
				// Editor default; without json storage the entry value shape is wrong.
				if ( empty( $field['storageType'] ) ) {
					$field['storageType'] = 'json';
				}
				break;

			case 'page':
				// The editor gives every page break Next/Previous button objects;
				// without them the page settings pane cannot open. Form-level
				// pagination is backfilled in prepare_compound_fields().
				if ( empty( $field['nextButton'] ) ) {
					$field['nextButton'] = array(
						'type'     => 'text',
						'text'     => __( 'Next', 'gravityforms' ),
						'imageUrl' => '',
					);
				}

				if ( empty( $field['previousButton'] ) ) {
					$field['previousButton'] = array(
						'type'     => 'text',
						'text'     => __( 'Previous', 'gravityforms' ),
						'imageUrl' => '',
					);
				}
				break;

			case 'phone':
				// Persist the same default sanitize_settings() and post_convert_field()
				// resolve to at runtime — GFAPI skips the former, and the latter patches
				// the object without persisting or setting storageType.
				if ( empty( $field['phoneFormat'] ) || ! in_array( $field['phoneFormat'], GF_Ability_Schemas::phone_format_options(), true ) ) {
					$field['phoneFormat'] = 'formatted';
				}

				if ( 'formatted' === $field['phoneFormat'] && empty( $field['storageType'] ) ) {
					$field['storageType'] = 'json';
				}

				if ( empty( $field['autocompleteAttribute'] ) ) {
					$field['autocompleteAttribute'] = 'tel';
				}
				break;

			case 'product':
				// The editor's Field Type dropdown defaults to Single Product.
				if ( empty( $field['inputType'] ) ) {
					$field['inputType'] = 'singleproduct';
				}

				// The editor sets enablePrice for choice-based product types
				// (StartChangeProductType in form_editor.js). Without it, state
				// validation hashes choice values without the |price suffix and
				// priced submissions are rejected as tampered.
				if ( in_array( $field['inputType'], array( 'select', 'radio' ), true ) && ! isset( $field['enablePrice'] ) ) {
					$field['enablePrice'] = true;
				}

				// Single-product style types store name/price/quantity in
				// sub-inputs the editor creates when the type is selected.
				if ( in_array( $field['inputType'], array( 'singleproduct', 'hiddenproduct', 'calculation' ), true ) && empty( $field['inputs'] ) ) {
					$field_id        = $field['id'];
					$field['inputs'] = array(
						array(
							'id'    => $field_id . '.1',
							'label' => __( 'Name', 'gravityforms' ),
						),
						array(
							'id'    => $field_id . '.2',
							'label' => __( 'Price', 'gravityforms' ),
						),
						array(
							'id'    => $field_id . '.3',
							'label' => __( 'Quantity', 'gravityforms' ),
						),
					);
				}
				break;

			case 'shipping':
				// The editor's Field Type dropdown defaults to Single Method.
				if ( empty( $field['inputType'] ) ) {
					$field['inputType'] = 'singleshipping';
				}

				// Editor rule from StartChangeShippingType in form_editor.js.
				if ( in_array( $field['inputType'], array( 'select', 'radio' ), true ) && ! isset( $field['enablePrice'] ) ) {
					$field['enablePrice'] = true;
				}
				break;
		}

		// Inputs supplied with placeholder suffix IDs ('.1', '.3', …) — the format
		// system-field-types documents — are prefixed with the field ID so they
		// match the {field_id}.{suffix} format GF stores.
		if ( ! empty( $field['inputs'] ) && is_array( $field['inputs'] ) ) {
			foreach ( $field['inputs'] as &$field_input ) {
				if ( isset( $field_input['id'] ) && is_string( $field_input['id'] ) && 0 === strpos( $field_input['id'], '.' ) ) {
					$field_input['id'] = $field['id'] . $field_input['id'];
				}
			}

			unset( $field_input );
		}
	}

	/**
	 * Enrich a conditional logic block by resolving field IDs to labels.
	 *
	 * @since 3.1.0
	 *
	 * @param array $logic     The conditional logic array (actionType, logicType, rules).
	 * @param array $field_map Field ID → {label, type} lookup.
	 *
	 * @return array Enriched logic with action, logic_type, and rules with source_field_label.
	 */
	private static function enrich_logic( $logic, $field_map ) {
		$rules     = array();
		$raw_rules = rgar( $logic, 'rules', array() );

		if ( is_array( $raw_rules ) ) {
			foreach ( $raw_rules as $rule ) {
				$source_id   = (string) rgar( $rule, 'fieldId', '' );
				$source_info = isset( $field_map[ $source_id ] ) ? $field_map[ $source_id ] : array( 'label' => '', 'type' => '' );

				$rules[] = array(
					'source_field_id'    => $source_id,
					'source_field_label' => $source_info['label'],
					'operator'           => rgar( $rule, 'operator', 'is' ),
					'value'              => rgar( $rule, 'value', '' ),
				);
			}
		}

		return array(
			'action'     => rgar( $logic, 'actionType', 'show' ),
			'logic_type' => rgar( $logic, 'logicType', 'all' ),
			'rules'      => $rules,
		);
	}

	/**
	 * Collect dependency entries for the dependency map.
	 *
	 * The dependency map is keyed by source field ID and shows what each field controls.
	 * This is the reverse of "field X depends on Y" — it's "field Y controls X".
	 *
	 * @since 3.1.0
	 *
	 * @param array  $rules             Enriched rules array.
	 * @param string $target_type       Type of target (field, notification, confirmation, submit_button, page).
	 * @param mixed  $target_id         ID of the target.
	 * @param string $target_label      Label of the target.
	 * @param string $effect            Action type (show/hide).
	 * @param string $logic_type        Logic type (all/any).
	 * @param array  &$dependency_entries Reference to the dependency map being built.
	 */
	private static function collect_dependencies( $rules, $target_type, $target_id, $target_label, $effect, $logic_type, &$dependency_entries ) {
		foreach ( $rules as $rule ) {
			$source_id = $rule['source_field_id'];

			if ( ! isset( $dependency_entries[ $source_id ] ) ) {
				$dependency_entries[ $source_id ] = array(
					'label'    => $rule['source_field_label'],
					'controls' => array(),
				);
			}

			// One control entry per distinct target — multiple rules on the same
			// source field increment rule_count instead of duplicating the entry.
			// The key includes target_type: a field and a notification can share
			// an ID value, and merging on target_id alone would drop one.
			$target_key = $target_type . ':' . $target_id;

			if ( isset( $dependency_entries[ $source_id ]['controls'][ $target_key ] ) ) {
				++$dependency_entries[ $source_id ]['controls'][ $target_key ]['rule_count'];
				continue;
			}

			$dependency_entries[ $source_id ]['controls'][ $target_key ] = array(
				'target_type'  => $target_type,
				'target_id'    => $target_id,
				'target_label' => $target_label,
				'effect'       => $effect,
				'logic_type'   => $logic_type,
				'rule_count'   => 1,
			);
		}
	}

	/**
	 * Validate layout grid properties on all fields.
	 *
	 * @since 3.1.0
	 *
	 * @param array $form The form array.
	 *
	 * @return true|\WP_Error True if valid, WP_Error if any field has invalid layout properties.
	 */
	private static function validate_layout_properties( $form ) {
		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return true;
		}

		foreach ( $form['fields'] as $index => $field ) {
			if ( isset( $field['layoutGridColumnSpan'] ) ) {
				$span = $field['layoutGridColumnSpan'];
				if ( ! is_int( $span ) || $span < 1 || $span > 12 ) {
					$label = isset( $field['label'] ) ? $field['label'] : '#' . ( $index + 1 );
					return new \WP_Error(
						'gf_ability_invalid_layout',
						sprintf(
							/* translators: %s: field label or index */
							__( 'Field "%s": layoutGridColumnSpan must be an integer between 1 and 12.', 'gravityforms' ),
							$label
						)
					);
				}
			}
		}

		return true;
	}

	/**
	 * Deprecated Ready Classes that must never be written by the abilities API.
	 *
	 * Mirrors the deprecated class list in GFFormDetail::need_deprecated_class_message().
	 * Ready Classes were superseded by the layout grid (layoutGroupId /
	 * layoutGridColumnSpan) in Gravity Forms 2.5.
	 *
	 * @since 3.1.0
	 *
	 * @var string[]
	 */
	private static $ready_classes = array(
		'gf_inline',
		'gf_left_half',
		'gf_right_half',
		'gf_left_third',
		'gf_middle_third',
		'gf_right_third',
		'gf_first_quarter',
		'gf_second_quarter',
		'gf_third_quarter',
		'gf_fourth_quarter',
		'gf_scroll_text',
		'gf_hide_ampm',
		'gf_hide_charleft',
		'gf_alert_green',
		'gf_alert_red',
		'gf_alert_yellow',
		'gf_alert_gray',
		'gf_alert_blue',
		'gf_simple_horizontal',
		'gf_invisible',
		'gf_list_2col',
		'gf_list_3col',
		'gf_list_4col',
		'gf_list_5col',
		'gf_list_2col_vertical',
		'gf_list_3col_vertical',
		'gf_list_4col_vertical',
		'gf_list_5col_vertical',
		'gf_list_height_25',
		'gf_list_height_50',
		'gf_list_height_75',
		'gf_list_height_100',
		'gf_list_height_125',
		'gf_list_height_150',
	);

	/**
	 * Strip deprecated Ready Classes from the cssClass of agent-provided fields.
	 *
	 * Other classes in cssClass are preserved. Only fields present in the given
	 * form array are touched, so update calls without a fields array never alter
	 * stored data.
	 *
	 * @since 3.1.0
	 *
	 * @param array $form The form array from ability input.
	 *
	 * @return array {
	 *     @type array $form     The form with Ready Classes removed.
	 *     @type array $stripped Unique list of Ready Classes that were removed.
	 * }
	 */
	private static function strip_ready_classes( $form ) {
		$stripped = array();

		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return array(
				'form'     => $form,
				'stripped' => $stripped,
			);
		}

		foreach ( $form['fields'] as &$field ) {
			if ( empty( $field['cssClass'] ) || ! is_string( $field['cssClass'] ) ) {
				continue;
			}

			$kept = array();

			foreach ( preg_split( '/\s+/', trim( $field['cssClass'] ) ) as $class ) {
				if ( in_array( $class, self::$ready_classes, true ) ) {
					$stripped[] = $class;
				} elseif ( '' !== $class ) {
					$kept[] = $class;
				}
			}

			$field['cssClass'] = implode( ' ', $kept );
		}

		unset( $field );

		return array(
			'form'     => $form,
			'stripped' => array_values( array_unique( $stripped ) ),
		);
	}

	/**
	 * Append the Ready Class notice to a successful response when classes were stripped.
	 *
	 * @since 3.1.0
	 *
	 * @param array $response The success response.
	 * @param array $stripped Ready Classes removed from the input.
	 *
	 * @return array
	 */
	private static function add_ready_class_notice( $response, $stripped ) {
		if ( empty( $stripped ) ) {
			return $response;
		}

		$response['stripped_ready_classes'] = $stripped;
		$response['notice']                 = sprintf(
			/* translators: %s: comma-separated list of CSS class names */
			__( 'Deprecated Ready Classes were removed from cssClass: %s. Ready Classes are not supported — use layoutGroupId and layoutGridColumnSpan on fields to control layout.', 'gravityforms' ),
			implode( ', ', $stripped )
		);

		return $response;
	}

	/**
	 * Normalize agent-friendly layoutGroupId values to 8-character hex strings.
	 *
	 * Agents can use readable group names like "row1" or "name-row". This method
	 * maps all fields sharing the same input value to the same generated hex ID,
	 * matching the format used by the form editor's getGroupId() function.
	 *
	 * Values that are already valid 8-char hex strings are left as-is.
	 *
	 * @since 3.1.0
	 *
	 * @param array $form The form array.
	 *
	 * @return array The form with normalized layoutGroupId values.
	 */
	private static function normalize_layout_group_ids( $form ) {
		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return $form;
		}

		// Collect all unique layoutGroupId values that need normalization.
		$group_map = array();

		foreach ( $form['fields'] as $field ) {
			if ( empty( $field['layoutGroupId'] ) ) {
				continue;
			}

			$group_id = $field['layoutGroupId'];

			// Already a valid 8-char hex string — skip.
			if ( preg_match( '/^[0-9a-f]{8}$/', $group_id ) ) {
				continue;
			}

			// Generate a deterministic hex ID for this friendly name.
			if ( ! isset( $group_map[ $group_id ] ) ) {
				$group_map[ $group_id ] = substr( md5( $group_id . wp_generate_uuid4() ), 0, 8 );
			}
		}

		// No normalization needed.
		if ( empty( $group_map ) ) {
			return $form;
		}

		// Apply the mapping.
		foreach ( $form['fields'] as &$field ) {
			if ( ! empty( $field['layoutGroupId'] ) && isset( $group_map[ $field['layoutGroupId'] ] ) ) {
				$field['layoutGroupId'] = $group_map[ $field['layoutGroupId'] ];
			}
		}

		unset( $field );

		return $form;
	}

	/**
	 * Slim down a field object for AI-friendly output.
	 *
	 * GF field objects carry ~30+ properties per field, most with empty/default values.
	 * A 10-field form can generate 10-20KB of noise. This strips properties that carry
	 * no information while preserving all actual configuration.
	 *
	 * @since 3.1.0
	 *
	 * @param \GF_Field|array $field The field object.
	 *
	 * @return array Slimmed field data.
	 */
	private static function slim_field( $field ) {
		$data = $field instanceof \GF_Field ? get_object_vars( $field ) : (array) $field;

		// Properties that must always be kept even if "empty".
		$always_keep = array( 'id', 'type', 'label', 'formId', 'pageNumber', 'isRequired', 'visibility' );

		// Properties that are internal/visual noise for AI agents.
		$always_strip = array(
			'is_payment',
			'displayOnly',
			'enableEnhancedUI',
			'enablePasswordInput',
			'multipleFiles',
			'calculationFormula',
			'calculationRounding',
			'enableCalculation',
			'disableQuantity',
			'displayAllCategories',
			'useRichTextEditor',
			'enableAutocomplete',
			'autocompleteAttribute',
			'inputMask',
			'inputMaskValue',
			'inputMaskIsCustom',
			'enableCopyValuesOption',
			'productField',
			'errors',
			'validateState',
			'enablePrice',
		);

		$stripped = array();

		foreach ( $data as $key => $value ) {
			// Always strip internal properties.
			if ( in_array( $key, $always_strip, true ) ) {
				continue;
			}

			// Always keep essential properties.
			if ( in_array( $key, $always_keep, true ) ) {
				$stripped[ $key ] = $value;
				continue;
			}

			// Strip empty values: null, empty string, empty array.
			if ( null === $value || '' === $value || ( is_array( $value ) && empty( $value ) ) ) {
				continue;
			}

			// Strip false booleans (default state).
			if ( false === $value ) {
				continue;
			}

			$stripped[ $key ] = $value;
		}

		return $stripped;
	}
}
