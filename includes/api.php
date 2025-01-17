<?php

use Gravity_Forms\Gravity_Forms\Async;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * API for standard Gravity Forms functionality.
 *
 * Supports:
 * - Forms
 * - Entries
 *
 * @package    Gravity Forms
 * @subpackage GFAPI
 * @since      1.8
 * @access     public
 */
class GFAPI {

	// FORMS ----------------------------------------------------

	/**
	 * Returns the form object for a given Form ID.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @uses GFFormsModel::get_form_meta()
	 * @uses GFFormsModel::get_form()
	 *
	 * @param int $form_id The ID of the Form.
	 *
	 * @return mixed The form meta array or false.
	 */
	public static function get_form( $form_id ) {

		$form_id = absint( $form_id );

		$form = GFFormsModel::get_form_meta( $form_id );
		if ( ! $form ) {
			return false;
		}

		$form_info = GFFormsModel::get_form( $form_id, true );
		if ( ! $form_info ) {
			return false;
		}

		// Loading form columns into meta.
		$form['is_active']    = $form_info->is_active;
		$form['date_created'] = $form_info->date_created;
		$form['is_trash']     = $form_info->is_trash;
		$form['title']        = $form_info->title;

		return $form;

	}

	/**
	 * Returns all the form objects.
	 *
	 * @since  1.8.11.5
	 * @since  2.5 added $sort_column and $sort_dir parameters.
	 * @access public
	 *
	 * @uses GFFormsModel::get_form_ids()
	 * @uses GFAPI::get_form()
	 *
	 * @param bool   $active      True if active forms are returned. False to get inactive forms. Defaults to true.
	 * @param bool   $trash       True if trashed forms are returned. False to exclude trash. Defaults to false.
	 * @param string $sort_column The column to sort the results on.
	 * @param string $sort_dir    The sort direction, ASC or DESC.
	 *
	 * @return array The array of Form Objects.
	 */
	public static function get_forms( $active = true, $trash = false, $sort_column = 'id', $sort_dir = 'ASC' ) {

		$form_ids = GFFormsModel::get_form_ids( $active, $trash, $sort_column, $sort_dir );
		if ( empty( $form_ids ) ) {
			return array();
		}

		$forms = array();
		foreach ( $form_ids as $form_id ) {
			$forms[] = GFAPI::get_form( $form_id );
		}

		return $forms;
	}

	/**
	 * Deletes the forms with the given Form IDs.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @uses GFFormsModel::delete_forms()
	 *
	 * @param array $form_ids An array of form IDs to delete.
	 *
	 * @return void
	 */
	public static function delete_forms( $form_ids ) {

		if ( gf_upgrade()->get_submissions_block() ) {
			return;
		}

		GFFormsModel::delete_forms( $form_ids );
	}

	/**
	 * Deletes the form with the given Form ID.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @uses GFAPI::get_form()
	 * @uses GFAPI::delete_forms()
	 *
	 * @param int $form_id The ID of the Form to delete.
	 *
	 * @return mixed True for success, or a WP_Error instance.
	 */
	public static function delete_form( $form_id ) {

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		$form = self::get_form( $form_id );
		if ( empty( $form ) ) {
			return new WP_Error( 'not_found', sprintf( __( 'Form with id: %s not found', 'gravityforms' ), $form_id ), $form_id );
		}
		self::delete_forms( array( $form_id ) );

		return true;
	}

	/**
	 * Duplicates the form with the given Form ID.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFFormsModel::duplicate_form()
	 *
	 * @param int $form_id The ID of the Form to delete.
	 *
	 * @return mixed True for success, or a WP_Error instance
	 */
	public static function duplicate_form( $form_id ) {

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		return GFFormsModel::duplicate_form( $form_id );

	}

	/**
	 * Updates the forms with an array of form objects.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @uses GFAPI::update_form()
	 *
	 * @param array $forms Array of form objects.
	 *
	 * @return mixed True for success, or a WP_Error instance.
	 */
	public static function update_forms( $forms ) {

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		foreach ( $forms as $form ) {
			$result = self::update_form( $form );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Updates the form with a given form object.
	 *
	 * @since  1.8
	 * @access public
	 * @global $wpdb
	 *
	 * @uses \GFFormsModel::get_meta_table_name()
	 * @uses \GFFormsModel::update_form_meta()
	 *
	 * @param array $form The Form object
	 * @param int   $form_id  Optional. If specified, then the ID in the Form Object will be ignored.
	 *
	 * @return bool|WP_Error True for success, or a WP_Error instance.
	 */
	public static function update_form( $form, $form_id = null ) {
		global $wpdb;

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		if ( ! $form ) {
			return new WP_Error( 'invalid', __( 'Invalid form object', 'gravityforms' ) );
		}

		$form_table_name = GFFormsModel::get_form_table_name();
		if ( empty( $form_id ) ) {
			$form_id = $form['id'];
		} else {
			// Make sure the form object has the right form ID.
			$form['id'] = $form_id;
		}

		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', __( 'Missing form id', 'gravityforms' ) );
		}

		if ( isset( $form['title'] ) ) {
			$form['title'] = self::unique_title( $form['title'], $form_id );
		}

		if ( isset( $form['fields'] ) ) {

			// Make sure the formId is correct.
			$form = GFFormsModel::convert_field_objects( $form );

			$next_field_id = GFFormsModel::get_next_field_id( $form['fields'] );

			$form['fields'] = self::add_missing_ids( $form['fields'], $next_field_id );
		}

		$meta_table_name = GFFormsModel::get_meta_table_name();

		if ( intval( $wpdb->get_var( $wpdb->prepare( "SELECT count(0) FROM {$meta_table_name} WHERE form_id=%d", $form_id ) ) ) == 0 ) {
			return new WP_Error( 'not_found', __( 'Form not found', 'gravityforms' ) );
		}

		// Strip confirmations and notifications.
		$form_display_meta = $form;
		unset( $form_display_meta['confirmations'] );
		unset( $form_display_meta['notifications'] );

		$result = GFFormsModel::update_form_meta( $form_id, $form_display_meta );
		if ( false === $result ) {
			return new WP_Error( 'error_updating_form', __( 'Error updating form', 'gravityforms' ), $wpdb->last_error );
		}

		if ( isset( $form['confirmations'] ) && is_array( $form['confirmations'] ) ) {
			$confirmations = self::set_property_as_key( $form['confirmations'], 'id' );
			$result        = GFFormsModel::update_form_meta( $form_id, $confirmations, 'confirmations' );
			if ( false === $result ) {
				return new WP_Error( 'error_updating_confirmations', __( 'Error updating form confirmations', 'gravityforms' ), $wpdb->last_error );
			}
		}

		if ( isset( $form['notifications'] ) && is_array( $form['notifications'] ) ) {
			$notifications = self::set_property_as_key( $form['notifications'], 'id' );
			$result        = GFFormsModel::update_form_meta( $form_id, $notifications, 'notifications' );
			if ( false === $result ) {
				return new WP_Error( 'error_updating_notifications', __( 'Error updating form notifications', 'gravityforms' ), $wpdb->last_error );
			}
		}

		// Updating form title and is_active flag.
		$is_active = rgar( $form, 'is_active' ) ? '1' : '0';
		$result    = $wpdb->query( $wpdb->prepare( "UPDATE {$form_table_name} SET title=%s, is_active=%s WHERE id=%d", $form['title'], $is_active, $form['id'] ) );

		GFFormsModel::flush_current_form( GFFormsModel::get_form_cache_key( $form_id ) );

		if ( false === $result ) {
			return new WP_Error( 'error_updating_title', __( 'Error updating title', 'gravityforms' ), $wpdb->last_error );
		}

		return true;
	}

	/**
	 * Adds missing IDs to field objects.
	 *
	 * @since 2.4.6.12
	 *
	 * @param GF_Field[] $fields
	 * @param $next_field_id
	 *
	 * @return GF_Field[]
	 */
	private static function add_missing_ids( $fields, $next_field_id ) {
		foreach ( $fields as &$field ) {
			if ( empty( $field->id ) ) {
				$field->id = $next_field_id ++;
			}
			if ( is_array( $field->fields ) ) {
				$field->fields = self::add_missing_ids( $field->fields, $next_field_id );
			}
		}
		return $fields;
	}

	/**
	 * Updates a form property - a column in the main forms table. e.g. is_trash, is_active, title
	 *
	 * @since  1.8.3.15
	 * @access public
	 *
	 * @param array  $form_ids     The IDs of the forms to update.
	 * @param string $property_key The name of the column in the database e.g. is_trash, is_active, title.
	 * @param mixed  $value        The new value.
	 *
	 * @return mixed Either a WP_Error instance or the result of the query
	 */
	public static function update_forms_property( $form_ids, $property_key, $value ) {
		global $wpdb;

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		$table      = GFFormsModel::get_form_table_name();
		$db_columns = GFFormsModel::get_form_db_columns();

		if ( ! in_array( strtolower( $property_key ), $db_columns ) ) {
			return new WP_Error( 'property_key_incorrect', __( 'Property key incorrect', 'gravityforms' ) );
		}

		if ( 'title' == $property_key ) {
			if ( count( $form_ids ) === 1 ) {
				$value = self::unique_title( $value, $form_ids[0] );
			} else {
				foreach ( $form_ids as $form_id ) {
					$result = self::update_forms_property( array( $form_id ), $property_key, $value );
					if ( is_wp_error( $result ) ) {
						// If the result is an error, return the error right away.
						return $result;
					}
				}

				return $result;
			}
		}

		$value = esc_sql( $value );
		if ( ! is_numeric( $value ) ) {
			$value = sprintf( "'%s'", $value );
		}
		$in_str_arr = array_fill( 0, count( $form_ids ), '%d' );
		$in_str     = implode( ',', $in_str_arr );
		$result     = $wpdb->query(
			$wpdb->prepare(
				"
                UPDATE $table
                SET {$property_key} = {$value}
                WHERE id IN ($in_str)
                ", $form_ids
			)
		);

		GFFormsModel::flush_current_forms();

		return $result;
	}

	/**
	 * Updates the property of one form - columns in the main forms table. e.g. is_trash, is_active, title.
	 *
	 * @since  1.8.3.15
	 * @access public
	 *
	 * @param array|int $form_id      The ID of the forms to update.
	 * @param string    $property_key The name of the column in the database e.g. is_trash, is_active, title.
	 * @param string    $value        The new value.
	 *
	 * @return mixed Either a WP_Error instance or the result of the query
	 */
	public static function update_form_property( $form_id, $property_key, $value ) {
		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}
		return self::update_forms_property( array( $form_id ), $property_key, $value );
	}


	/**
	 * Adds multiple form objects.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @uses GFAPI::add_form()
	 *
	 * @param array $forms The Form Objects.
	 *
	 * @return array|WP_Error Either an array of new form IDs or a WP_Error instance.
	 */
	public static function add_forms( $forms ) {

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		if ( ! $forms || ! is_array( $forms ) ) {
			return new WP_Error( 'invalid', __( 'Invalid form objects', 'gravityforms' ) );
		}
		$form_ids = array();
		foreach ( $forms as $form ) {
			$result = self::add_form( $form );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$form_ids[] = $result;
		}

		return $form_ids;
	}

	/**
	 * Adds a new form using the given Form object. Warning, little checking is done to make sure it's a valid Form object.
	 *
	 * @since  1.8
	 * @access public
	 * @global $wpdb
	 *
	 * @param array $form_meta The Form object.
	 *
	 * @return int|WP_Error Either the new Form ID or a WP_Error instance.
	 */
	public static function add_form( $form_meta ) {
		global $wpdb;

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		if ( ! $form_meta || ! is_array( $form_meta ) ) {
			return new WP_Error( 'invalid', __( 'Invalid form object', 'gravityforms' ) );
		}

		if ( rgar( $form_meta, 'title' ) == '' ) {
			return new WP_Error( 'missing_title', __( 'The form title is missing', 'gravityforms' ) );
		}

		if ( ! isset( $form_meta['fields'] ) || ! is_array( $form_meta['fields'] ) ) {
			return new WP_Error( 'missing_fields', __( 'The form fields are missing', 'gravityforms' ) );
		}

		// Making sure title is not duplicate.
		$title = self::unique_title( $form_meta['title'] );

		// Inserting form.
		$form_id = RGFormsModel::insert_form( $title );

		// Updating form meta.
		$form_meta['title'] = $title;

		// Updating object's id property.
		$form_meta['id'] = $form_id;

		// Adding markup version. Increment this when we make breaking changes to form markup.
		$form_meta['markupVersion'] = rgar( $form_meta, 'markupVersion' ) ? $form_meta['markupVersion'] : 2;

		// Add default confirmation if form has no confirmations.
		if ( ! isset( $form_meta['confirmations'] ) || empty( $form_meta['confirmations'] ) ) {

			$confirmation = GFFormsModel::get_default_confirmation();

			// Add default confirmation to form.
			$form_meta['confirmations'] = array( $confirmation['id'] => $confirmation );

		}

		if ( isset( $form_meta['confirmations'] ) ) {
			$form_meta['confirmations'] = self::set_property_as_key( $form_meta['confirmations'], 'id' );
			GFFormsModel::update_form_meta( $form_id, $form_meta['confirmations'], 'confirmations' );
			unset( $form_meta['confirmations'] );
		}

		if ( isset( $form_meta['notifications'] ) ) {
			$form_meta['notifications'] = self::set_property_as_key( $form_meta['notifications'], 'id' );
			GFFormsModel::update_form_meta( $form_id, $form_meta['notifications'], 'notifications' );
			unset( $form_meta['notifications'] );
		}

		// Make sure the formId is correct.
		$form_meta = GFFormsModel::convert_field_objects( $form_meta );

		$next_field_id = GFFormsModel::get_next_field_id( $form_meta['fields'] );

		$form_meta['fields'] = self::add_missing_ids( $form_meta['fields'], $next_field_id );

		// Updating form meta.
		$result = GFFormsModel::update_form_meta( $form_id, $form_meta );

		if ( false === $result ) {
			return new WP_Error( 'insert_form_error', __( 'There was a problem while inserting the form', 'gravityforms' ), $wpdb->last_error );
		}

		return $form_id;
	}

	/**
	 * Private.
	 *
	 * @since  1.8
	 * @access private
	 * @ignore
	 */
	private static function set_property_as_key( $array, $property ) {
		$new_array = array();
		foreach ( $array as $item ) {
			$new_array[ $item[ $property ] ] = $item;
		}

		return $new_array;
	}

	// ENTRIES ----------------------------------------------------

	/**
	 * Returns an array of Entry objects for the given search criteria. The search criteria array is constructed as follows:
	 *
	 *  Filter by status
	 *     $search_criteria['status'] = 'active';
	 *
	 *  Filter by date range
	 *     $search_criteria['start_date'] = $start_date; // Using the time zone in the general settings.
	 *     $search_criteria['end_date'] =  $end_date;    // Using the time zone in the general settings.
	 *
	 *  Filter by any column in the main table
	 *     $search_criteria['field_filters'][] = array("key" => 'currency', value => 'USD');
	 *     $search_criteria['field_filters'][] = array("key" => 'is_read', value => true);
	 *
	 *  Filter by Field Values
	 *     $search_criteria['field_filters'][] = array('key' => '1', 'value' => 'gquiz159982170');
	 *
	 *  Filter Operators
	 *     Supported operators for scalar values: is/=, isnot/<>, contains
	 *     $search_criteria['field_filters'][] = array('key' => '1', 'operator' => 'contains', value' => 'Steve');
	 *     Supported operators for array values: in/=, not in/<>/!=
	 *     $search_criteria['field_filters'][] = array('key' => '1', 'operator' => 'not in', value' => array( 'Alex', 'David', 'Dana' );
	 *
	 *  Filter by a checkbox value - input ID search keys
	 *     $search_criteria['field_filters'][] = array('key' => '2.2', 'value' => 'gquiz246fec995');
	 *     NOTES:
	 *          - Using input IDs as search keys will work for checkboxes but it won't work if the checkboxes have been re-ordered since the first submission.
	 *          - the 'not in' operator is not currently supported for checkbox values.
	 *
	 *  Filter by a checkbox value - field ID keys
	 *     Using the field ID as the search key is recommended for checkboxes.
	 *     $search_criteria['field_filters'][] = array('key' => '2', 'value' => 'gquiz246fec995');
	 *     $search_criteria['field_filters'][] = array('key' => '2', 'operator' => 'in', 'value' => array( 'First Choice', 'Third Choice' );
	 *     NOTE: Neither 'not in' nor '<>' operators are not currently supported for checkboxes using field IDs as search keys.
	 *
	 *  Filter by a global search of values of any form field
	 *     $search_criteria['field_filters'][] = array('value' => $search_value);
	 *  OR
	 *     $search_criteria['field_filters'][] = array('key' => 0, 'value' => $search_value);
	 *
	 *  Filter entries by Entry meta (added using the gform_entry_meta hook)
	 *     $search_criteria['field_filters'][] = array('key' => 'gquiz_score', 'value' => '1');
	 *     $search_criteria['field_filters'][] = array('key' => 'gquiz_is_pass', 'value' => '1');
	 *
	 *  Filter by ALL / ANY of the field filters
	 *     $search_criteria['field_filters']['mode'] = 'all'; // default
	 *     $search_criteria['field_filters']['mode'] = 'any';
	 *
	 *  Sorting: column, field or entry meta
	 *     $sorting = array('key' => $sort_field, 'direction' => 'ASC' );
	 *
	 *  Paging
	 *     $paging = array('offset' => 0, 'page_size' => 20 );
	 *
	 * @since  1.8
	 * @access public
	 *
	 *
	 * @param int|array $form_ids        The ID of the form or an array IDs of the Forms. Zero for all forms.
	 * @param array     $search_criteria Optional. An array containing the search criteria. Defaults to empty array.
	 * @param array     $sorting         Optional. An array containing the sorting criteria. Defaults to null.
	 * @param array     $paging          Optional. An array containing the paging criteria. Defaults to null.
	 * @param int       $total_count     Optional. An output parameter containing the total number of entries. Pass a non-null value to get the total count. Defaults to null.
	 *
	 * @return array|WP_Error Either an array of the Entry objects or a WP_Error instance.
	 */
	public static function get_entries( $form_ids, $search_criteria = array(), $sorting = null, $paging = null, &$total_count = null ) {

		if ( empty( $sorting ) ) {
			$sorting = array( 'key' => 'id', 'direction' => 'DESC', 'is_numeric' => true );
		}

		if ( version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '<' ) ) {
			$entries = GF_Forms_Model_Legacy::search_leads( $form_ids, $search_criteria, $sorting, $paging );
			if ( ! is_null( $total_count ) ) {
				$total_count = self::count_entries( $form_ids, $search_criteria );
			}
			return $entries;
		}

		$q = new GF_Query( $form_ids, $search_criteria, $sorting, $paging );
		$entries = $q->get();
		$total_count = $q->total_found;

		return $entries;
	}

	/**
	 * Returns an array of Entry IDs for the given search criteria.
	 *
	 * @since  2.3     Added $sorting and $paging parameters.
	 * @since  Unknown
	 * @access public
	 *
	 * @param int|array $form_id         The ID of the form or an array IDs of the Forms. Zero for all forms.
	 * @param array     $search_criteria Optional. An array containing the search criteria. Defaults to empty array.
	 * @param array     $sorting         Optional. An array containing the sorting criteria. Defaults to null.
	 * @param array     $paging          Optional. An array containing the paging criteria. Defaults to null.
	 * @param null|int  $total_count     Optional. An output parameter containing the total number of entries. Pass a non-null value to get the total count. Defaults to null.
	 *
	 * @return array An array of the Entry IDs.
	 */
	public static function get_entry_ids( $form_id, $search_criteria = array(), $sorting = null, $paging = null, &$total_count = null ) {

		if ( version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '<' ) ) {
			$entry_ids = GF_Forms_Model_Legacy::search_lead_ids( $form_id, $search_criteria );
			return $entry_ids;
		}

		if ( ! $paging ) {
			$paging = array( 'page_size' => 0 );
		}

		$the_query = new GF_Query( $form_id, $search_criteria, $sorting, $paging  );
		$entry_ids = $the_query->get_ids();
		$total_count = $the_query->total_found;
		return $entry_ids;
	}

	/**
	 * Returns the total number of entries for the given search criteria. See get_entries() for examples of the search criteria.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @uses GFFormsModel::count_search_leads()
	 *
	 * @param int|array $form_ids        The ID of the Form or an array of Form IDs.
	 * @param array     $search_criteria Optional. An array containing the search criteria. Defaults to empty array.
	 *
	 * @return int The total count.
	 */
	public static function count_entries( $form_ids, $search_criteria = array() ) {

		if ( version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '<' ) ) {
			return GF_Forms_Model_Legacy::count_search_leads( $form_ids, $search_criteria );
		}

		$q = new GF_Query( $form_ids, $search_criteria );
		$ids = $q->get_ids();
		return $q->total_found;
	}

	/**
	 * Returns the Entry object for a given Entry ID.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @uses GFAPI::get_entries()
	 *
	 * @param int $entry_id The ID of the Entry.
	 *
	 * @return array|WP_Error The Entry object or a WP_Error instance.
	 */
	public static function get_entry( $entry_id ) {

		if ( version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '<' ) ) {
			$search_criteria['field_filters'][] = array( 'key' => 'id', 'value' => $entry_id );

			$paging  = array( 'offset' => 0, 'page_size' => 1 );
			$entries = self::get_entries( 0, $search_criteria, null, $paging );
			if ( empty( $entries ) ) {
				return new WP_Error( 'not_found', sprintf( __( 'Entry with id %s not found', 'gravityforms' ), $entry_id ), $entry_id );
			}

			return $entries[0];
		}

		$q = new GF_Query();

		$entry = $q->get_entry( $entry_id );

		if ( empty( $entry ) ) {
			return new WP_Error( 'not_found', sprintf( __( 'Entry with id %s not found', 'gravityforms' ), $entry_id ), $entry_id );
		}

		return $entry;
	}

	/**
	 * Adds multiple Entry objects.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @uses GFAPI::add_entry()
	 *
	 * @param array $entries The Entry objects
	 * @param int   $form_id Optional. If specified, the form_id in the Entry objects will be ignored. Defaults to null.
	 *
	 * @return array|WP_Error Either an array of new Entry IDs or a WP_Error instance
	 */
	public static function add_entries( $entries, $form_id = null ) {

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		$entry_ids = array();
		foreach ( $entries as $entry ) {
			if ( $form_id ) {
				$entry['form_id'] = $form_id;
			}
			$result = self::add_entry( $entry );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$entry_ids[] = $result;
		}

		return $entry_ids;
	}

	/**
	 * Updates multiple Entry objects.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @uses GFCommon::log_debug()
	 * @uses GFAPI::update_entry()
	 *
	 * @param array $entries The Entry objects
	 *
	 * @return bool|WP_Error Either true for success, or a WP_Error instance
	 */
	public static function update_entries( $entries ) {

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		foreach ( $entries as $entry ) {
			$entry_id = rgar( $entry, 'id' );
			GFCommon::log_debug( __METHOD__ . '(): Updating entry ' . $entry_id );
			$result = self::update_entry( $entry, $entry_id );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Updates an entire single Entry object.
	 *
	 * If the date_created value is not set then the current time UTC will be used.
	 * The date_created value, if set, is expected to be in 'Y-m-d H:i:s' format (UTC).
	 *
	 * @since  1.8
	 * @access public
	 * @global $wpdb
	 * @global $current_user
	 *
	 * @uses \GFAPI::get_entry
	 * @uses \GFAPI::form_id_exists
	 * @uses \GFFormsModel::get_ip
	 * @uses \GFFormsModel::get_current_page_url
	 * @uses \GFCommon::get_currency
	 * @uses \GFFormsModel::get_lead_table_name
	 * @uses \GFFormsModel::get_lead_details_table_name
	 * @uses \GFFormsModel::get_form_meta
	 * @uses \GFFormsModel::get_input_type
	 * @uses \GF_Field::get_entry_inputs
	 * @uses \GFFormsModel::get_lead_detail_id
	 * @uses \GFFormsModel::update_lead_field_value
	 * @uses \GFFormsModel::get_entry_meta
	 * @uses \GFFormsModel::get_field
	 *
	 * @param array $entry    The Entry Object.
	 * @param int   $entry_id Optional. If specified, the ID in the Entry Object will be ignored. Defaults to null.
	 *
	 * @return true|WP_Error Either True or a WP_Error instance
	 */
	public static function update_entry( $entry, $entry_id = null ) {
		global $wpdb;

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		if ( version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '<' ) ) {
			return GF_Forms_Model_Legacy::update_entry( $entry, $entry_id );
		}

		if ( empty( $entry_id ) ) {
			if ( rgar( $entry, 'id' ) ) {
				$entry_id = absint( $entry['id'] );
			}
		} else {
			$entry['id'] = absint( $entry_id );
		}

		if ( empty( $entry_id ) ) {
			return new WP_Error( 'missing_entry_id', __( 'Missing entry id', 'gravityforms' ) );
		}

		$current_entry = $original_entry = self::get_entry( $entry_id );

		if ( ! $current_entry ) {
			return new WP_Error( 'not_found', __( 'Entry not found', 'gravityforms' ), $entry_id );
		}

		if ( is_wp_error( $current_entry ) ) {
			return $current_entry;
		}

		// Make sure the form id exists
		$form_id = rgar( $entry, 'form_id' );
		if ( empty( $form_id ) ) {
			$form_id          = rgar( $current_entry, 'form_id' );
			$entry['form_id'] = $form_id;
		}

		if ( false === self::form_id_exists( $form_id ) ) {
			return new WP_Error( 'invalid_form_id', __( 'The form for this entry does not exist', 'gravityforms' ) );
		}

		$form = GFFormsModel::get_form_meta( $form_id );

		/**
		 * Filters the entry before it is updated.
		 *
		 * @since Unknown
		 *
		 * @param array $entry          The Entry Object.
		 * @param array $original_entry Te original Entry Object, before changes.
		 */
		$entry = apply_filters( 'gform_entry_pre_update', $entry, $original_entry );

		// Use values in the entry object if present.
		if ( ! isset( $entry['post_id'] ) ) {
			$entry['post_id'] = null;
		}
		$post_id = ! empty( $entry['post_id'] ) ? intval( $entry['post_id'] ) : 'NULL';

		$current_time = $wpdb->get_var( 'SELECT utc_timestamp()' );

		if ( empty( $entry['date_created'] ) ) {
			$entry['date_created'] = $current_time;
		}
		$date_created = sprintf( "'%s'", esc_sql( $entry['date_created'] ) );

		if ( empty( $entry['date_updated'] ) ) {
			$entry['date_updated'] = $current_time;
		}
		$date_updated = sprintf( "'%s'", esc_sql( $entry['date_updated'] ) );

		if ( ! isset( $entry['is_starred'] ) ) {
			$entry['is_starred'] = 0;
		}
		$is_starred = intval( $entry['is_starred'] );

		if ( ! isset( $entry['is_read'] ) ) {
			$entry['is_read'] = 0;
		}
		$is_read = intval( $entry['is_read'] );

		if ( ! isset( $entry['ip'] ) ) {
			$entry['ip'] = rgars( $form, 'personalData/preventIP' ) ? '' : GFFormsModel::get_ip();
		}
		$ip = $entry['ip'];

		if ( ! isset( $entry['source_url'] ) ) {
			$entry['source_url'] = GFFormsModel::get_current_page_url();
		}
		$source_url = $entry['source_url'];

		$entry['user_agent'] = isset( $entry['user_agent'] ) ? sanitize_text_field( $entry['user_agent'] ) : 'API';
		$user_agent          = $entry['user_agent'];

		if ( empty( $entry['currency'] ) ) {
			$entry['currency'] = GFCommon::get_currency();
		}
		$currency = $entry['currency'];

		if ( ! isset( $entry['payment_status'] ) ) {
			$entry['payment_status'] = null;
		}
		$payment_status = ! empty( $entry['payment_status'] ) ? sprintf( "'%s'", esc_sql( $entry['payment_status'] ) ) : 'NULL';

		if ( empty( $entry['payment_date'] ) ) {
			$payment_date          = null;
			$entry['payment_date'] = $payment_date;
		} else {
			$payment_date = strtotime( $entry['payment_date'] );
		}
		$payment_date = $payment_date ? sprintf( "'%s'", esc_sql( gmdate( 'Y-m-d H:i:s', $payment_date ) ) ) : 'NULL';

		if ( ! isset( $entry['payment_amount'] ) ) {
			$entry['payment_amount'] = null;
		}
		$payment_amount = ! empty( $entry['payment_amount'] ) ? (float) $entry['payment_amount'] : 'NULL';

		if ( ! isset( $entry['payment_method'] ) ) {
			$entry['payment_method'] = '';
		}
		$payment_method = $entry['payment_method'];

		if ( ! isset( $entry['transaction_id'] ) ) {
			$entry['transaction_id'] = null;
		}
		$transaction_id = ! empty( $entry['transaction_id'] ) ? sprintf( "'%s'", esc_sql( $entry['transaction_id'] ) ) : 'NULL';

		if ( ! isset( $entry['is_fulfilled'] ) ) {
			$entry['is_fulfilled'] = null;
		}
		$is_fulfilled = ! empty( $entry['is_fulfilled'] ) ? intval( $entry['is_fulfilled'] ) : 'NULL';

		if ( empty( $entry['status'] ) ) {
			$entry['status'] = 'active';
		}
		$status = $entry['status'];

		$user_id = isset( $entry['created_by'] ) ? absint( $entry['created_by'] ) : '';
		if ( empty( $user_id ) ) {
			global $current_user;
			if ( $current_user && $current_user->ID ) {
				$user_id             = absint( $current_user->ID );
				$entry['created_by'] = $user_id;
			} else {
				$user_id             = 'NULL';
				$entry['created_by'] = null;
			}
		}

		if ( ! isset( $entry['transaction_type'] ) ) {
			$entry['transaction_type'] = null;
		}
		$transaction_type = ! empty( $entry['transaction_type'] ) ? intval( $entry['transaction_type'] ) : 'NULL';

		if ( ! isset( $entry['source_id'] ) ) {
			$entry['source_id'] = null;
		}
		$source_id = ! empty( $entry['source_id'] ) ? absint( $entry['source_id'] ) : 'NULL';

		$entry_table = GFFormsModel::get_entry_table_name();
		$sql = $wpdb->prepare(
			"
                UPDATE $entry_table
                SET
                form_id = %d,
                post_id = {$post_id},
                date_created = {$date_created},
                date_updated = {$date_updated},
                is_starred = %d,
                is_read = %d,
                ip = %s,
                source_url = %s,
                user_agent = %s,
                currency = %s,
                payment_status = {$payment_status},
                payment_date = {$payment_date},
                payment_amount = {$payment_amount},
                transaction_id = {$transaction_id},
                is_fulfilled = {$is_fulfilled},
                created_by = {$user_id},
                transaction_type = {$transaction_type},
                status = %s,
                payment_method = %s,
                source_id = {$source_id}
                WHERE
                id = %d
                ", $form_id, $is_starred, $is_read, $ip, $source_url, $user_agent, $currency, $status, $payment_method, $entry_id
		);
		$result     = $wpdb->query( $sql );
		if ( false === $result ) {
			return new WP_Error( 'update_entry_properties_failed', __( 'There was a problem while updating the entry properties', 'gravityforms' ), $wpdb->last_error );
		}

		// Only save field values for fields that currently exist in the form. The rest in $entry will be ignored. The rest in $current_entry will get deleted.

		$entry_meta_table = GFFormsModel::get_entry_meta_table_name();
		$current_fields    = $wpdb->get_results( $wpdb->prepare( "SELECT id, meta_key, item_index FROM $entry_meta_table WHERE entry_id=%d", $entry_id ) );

		$form = gf_apply_filters( array( 'gform_form_pre_update_entry', $form_id ), $form, $entry, $entry_id );

		GFFormsModel::begin_batch_field_operations();

		$has_product_field = false;

		foreach ( $form['fields'] as $field ) {
			/* @var GF_Field $field */
			if ( $field->displayOnly ) {
				continue;
			}

			self::queue_batch_field_operation( $form, $entry, $field, '', $current_entry, $current_fields );

			if ( ! $has_product_field && GFCommon::is_product_field( $field->type ) ) {
				$has_product_field = true;
			}
		}

		// Save the entry meta values - only for the entry meta currently available for the form, ignore the rest.
		$entry_meta = GFFormsModel::get_entry_meta( $form_id );
		if ( is_array( $entry_meta ) ) {
			foreach ( array_keys( $entry_meta ) as $key ) {
				if ( isset( $entry[ $key ] ) ) {
					if ( $entry[ $key ] != $current_entry[ $key ] ) {
						gform_update_meta( $entry_id, $key, $entry[ $key ] );
					}
					unset( $current_entry[ $key ] );
				}
			}
		}

		// Now delete remaining values from the old entry.

		if ( is_array( $entry_meta ) ) {
			foreach ( array_keys( $entry_meta ) as $meta_key ) {
				if ( isset( $current_entry[ $meta_key ] ) ) {
					gform_delete_meta( $entry_id, $meta_key );
					unset( $current_entry[ $meta_key ] );
				}
			}
		}

		// Unset to prevent GFFormsModel::queue_batch_field_operation() setting them to empty strings in $entry during the next foreach.
		$entry_columns = GFFormsModel::get_lead_db_columns();
		foreach ( $entry_columns as $column ) {
			unset( $current_entry[ $column ] );
		}

		self::purge_missing_entry_values( $form, $entry, $current_entry, $current_fields );

		GFFormsModel::commit_batch_field_operations();

		if ( $has_product_field ) {
			GFFormsModel::refresh_product_cache( $form, $entry );
		}

		/**
		 * Fires after the Entry is updated.
		 *
		 * @since Unknown.
		 *
		 * @param array $lead           The entry object after being updated.
		 * @param array $original_entry The entry object before being updated.
		 */
		gf_do_action( array( 'gform_post_update_entry', $form_id ), $entry, $original_entry );

		return true;
	}

	/**
	 * Delete obsolete fields from the current entry.
	 *
	 * The $current_entry object here contains subfields in repeater fields which are no longer available in the
	 * updated entry. So we need to delete them all from the $entry object.
	 *
	 * @since 2.4.11
	 *
	 * @param array  $form The form object.
	 * @param array  $entry The entry object.
	 * @param array  $current_entry The current entry array.
	 * @param array  $current_fields Current entry meta gets from DB.
	 * @param string $item_index Item index.
	 *
	 * @return void|WP_Error Return WP_Error if there's DB errors.
	 */
	private static function purge_missing_entry_values( $form, &$entry, $current_entry, $current_fields, $item_index = '' ) {
		global $wpdb;

		if ( $current_entry !== null ) {
			foreach ( $current_entry as $k => $v ) {
				$field = self::get_field( $form, $k );

				if ( $field instanceof GF_Field_Repeater && ! empty( $v ) ) {
					foreach ( $v as $i => $values ) {
						$new_item_index = $item_index . '_' . $i;
						self::purge_missing_entry_values( $form, $entry, $values, $current_fields, $new_item_index );
					}
				} else {
					$lead_detail_id = GFFormsModel::get_lead_detail_id( $current_fields, $k, $item_index );
					$result         = GFFormsModel::queue_batch_field_operation( $form, $entry, $field, $lead_detail_id, $k, '', $item_index );
					if ( false === $result ) {
						return new WP_Error( 'update_field_values_failed', __( 'There was a problem while updating the field values', 'gravityforms' ), $wpdb->last_error );
					}
				}
			}
		}
	}

	private static function queue_batch_field_operation( $form, $entry, $field, $item_index = '', &$current_entry = array(), $current_fields = array() ) {

		if ( is_array( $field->fields ) ) {
			$field_id = (string) $field->id;
			if ( isset( $entry[ $field_id ] ) && is_array( $entry[ $field_id ] ) ) {
				foreach ( $entry[ $field_id ] as $i => $values ) {
					$new_item_index = $item_index . '_' . $i;
					$values['id']   = $entry['id'];
					foreach ( $field->fields as $sub_field ) {
						self::queue_batch_field_operation( $form, $values, $sub_field, $new_item_index, $current_entry[ $field_id ][ $i ], $current_fields );
					}
				}
			}
		}

		$inputs = $field->get_entry_inputs();
		if ( is_array( $inputs ) ) {
			foreach ( $field->inputs as $input ) {
				$input_id = (string) $input['id'];
				$input_value = isset( $entry[ (string) $input_id ] ) ? $entry[ (string) $input_id ] : '';
				$current_value = isset( $current_entry[ (string) $input_id ] ) ? $current_entry[ (string) $input_id ] : '';
				if ( empty( $current_entry ) || $input_value != $current_value ) {
					$lead_detail_id = $current_fields ? GFFormsModel::get_lead_detail_id( $current_fields, $input_id, $item_index ) : 0;
					$result         = GFFormsModel::queue_batch_field_operation( $form, $entry, $field, $lead_detail_id, $input_id, $input_value, $item_index );
					if ( false === $result ) {
						return new WP_Error( 'update_input_value_failed', __( 'There was a problem while updating one of the input values for the entry', 'gravityforms' ) );
					}
					foreach ( $current_fields as $current_field ) {
						if ( $current_field->meta_key == $input_id && $current_field->item_index == $item_index ) {
							$current_field->update = true;
						}
					}
				}

				unset( $current_entry[ $input_id ] );
			}

		} else {
			$field_id    = (string) $field->id;
			$field_value = isset( $entry[ (string) $field_id ] ) ? $entry[ (string) $field_id ] : '';
			$current_value = isset( $current_entry[ (string) $field_id ] ) ? $current_entry[ (string) $field_id ] : '';
			if ( empty( $current_entry ) || $field_value != $current_value ) {
				$lead_detail_id = $current_fields ? GFFormsModel::get_lead_detail_id( $current_fields, $field_id, $item_index ) : 0;
				$result         = GFFormsModel::queue_batch_field_operation( $form, $entry, $field, $lead_detail_id, $field_id, $field_value, $item_index );
				if ( false === $result ) {
					return new WP_Error( 'update_field_values_failed', __( 'There was a problem while updating the field values', 'gravityforms' ) );
				}
				foreach ( $current_fields as $current_field ) {
					if ( $current_field->meta_key == $field_id && $current_field->item_index == $item_index ) {
						$current_field->update = true;
					}
				}
			}
			unset( $current_entry[ $field_id ] );
		}

		return $current_entry;
	}

	/**
	 * Adds a single Entry object.
	 *
	 * Intended to be used for importing an entry object. The usual hooks that are triggered while saving entries are not fired here.
	 * Checks that the form id, field ids and entry meta exist and ignores legacy values (i.e. values for fields that no longer exist).
	 *
	 * @since  1.8
	 * @access public
	 * @global $wpdb
	 * @global $current_user
	 *
	 * @uses GFAPI::form_id_exists()
	 * @uses GFFormsModel::get_ip()
	 * @uses GFFormsModel::get_current_page_url()
	 * @uses GFCommon::get_currency()
	 * @uses GFFormsModel::get_lead_table_name()
	 * @uses GF_Field::get_entry_inputs()
	 * @uses GFFormsModel::update_lead_field_value()
	 * @uses GFFormsModel::get_entry_meta()
	 * @uses GFAPI::get_entry()
	 *
	 * @param array $entry The Entry Object.
	 *
	 * @return int|WP_Error Either the new Entry ID or a WP_Error instance.
	 */
	public static function add_entry( $entry ) {
		global $wpdb;

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		if ( version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '<' ) ) {
			return GF_Forms_Model_Legacy::add_entry( $entry );
		}

		if ( ! is_array( $entry ) ) {
			return new WP_Error( 'invalid_entry_object', __( 'The entry object must be an array', 'gravityforms' ) );
		}

		// Make sure the form id exists.
		$form_id = rgar( $entry, 'form_id' );
		if ( empty( $form_id ) ) {
			return new WP_Error( 'empty_form_id', __( 'The form id must be specified', 'gravityforms' ) );
		}

		if ( false === self::form_id_exists( $form_id ) ) {
			return new WP_Error( 'invalid_form_id', __( 'The form for this entry does not exist', 'gravityforms' ) );
		}

		$form = GFFormsModel::get_form_meta( $form_id );

		// Use values in the entry object if present
		$post_id        = isset( $entry['post_id'] ) ? intval( $entry['post_id'] ) : 'NULL';
		$date_created   = isset( $entry['date_created'] ) && $entry['date_created'] != '' ? sprintf( "'%s'", esc_sql( $entry['date_created'] ) ) : 'utc_timestamp()';
		$date_updated   = isset( $entry['date_updated'] ) && $entry['date_updated'] != '' ? sprintf( "'%s'", esc_sql( $entry['date_updated'] ) ) : 'utc_timestamp()';
		$is_starred     = isset( $entry['is_starred'] ) ? $entry['is_starred'] : 0;
		$is_read        = isset( $entry['is_read'] ) ? $entry['is_read'] : 0;
		$request_ip     = rgars( $form, 'personalData/preventIP' ) ? '' : GFFormsModel::get_ip();
		$ip             = isset( $entry['ip'] ) ? $entry['ip'] : $request_ip;
		$source_url     = isset( $entry['source_url'] ) ? $entry['source_url'] : esc_url_raw( GFFormsModel::get_current_page_url() );
		$user_agent     = isset( $entry['user_agent'] ) ? sanitize_text_field( $entry['user_agent'] ) : 'API';
		$currency       = isset( $entry['currency'] ) ? $entry['currency'] : GFCommon::get_currency();
		$payment_status = isset( $entry['payment_status'] ) ? sprintf( "'%s'", esc_sql( $entry['payment_status'] ) ) : 'NULL';
		$payment_date   = strtotime( rgar( $entry, 'payment_date' ) ) ? sprintf( "'%s'", gmdate( 'Y-m-d H:i:s', strtotime( "{$entry['payment_date']}" ) ) ) : 'NULL';
		$payment_amount = isset( $entry['payment_amount'] ) ? (float) $entry['payment_amount'] : 'NULL';
		$payment_method = isset( $entry['payment_method'] ) ? $entry['payment_method'] : '';
		$transaction_id = isset( $entry['transaction_id'] ) ? sprintf( "'%s'", esc_sql( $entry['transaction_id'] ) ) : 'NULL';
		$is_fulfilled   = isset( $entry['is_fulfilled'] ) ? intval( $entry['is_fulfilled'] ) : 'NULL';
		$status         = isset( $entry['status'] ) ? $entry['status'] : 'active';
		$source_id      = isset( $entry['source_id'] ) ? absint( $entry['source_id'] ) : 'NULL';

		global $current_user;
		$user_id = isset( $entry['created_by'] ) ? absint( $entry['created_by'] ) : '';
		if ( empty( $user_id ) ) {
			$user_id = $current_user && $current_user->ID ? absint( $current_user->ID )  : 'NULL';
		}

		$transaction_type = isset( $entry['transaction_type'] ) ? intval( $entry['transaction_type'] ) : 'NULL';

		$entry_table = GFFormsModel::get_entry_table_name();
		$result     = $wpdb->query(
			$wpdb->prepare(
				"
                INSERT INTO $entry_table
                (form_id, post_id, date_created, date_updated, is_starred, is_read, ip, source_url, user_agent, currency, payment_status, payment_date, payment_amount, transaction_id, is_fulfilled, created_by, transaction_type, status, payment_method, source_id)
                VALUES
                (%d, {$post_id}, {$date_created}, {$date_updated}, %d,  %d, %s, %s, %s, %s, {$payment_status}, {$payment_date}, {$payment_amount}, {$transaction_id}, {$is_fulfilled}, {$user_id}, {$transaction_type}, %s, %s, {$source_id})
                ", $form_id, $is_starred, $is_read, $ip, $source_url, $user_agent, $currency, $status, $payment_method
			)
		);
		if ( false === $result ) {
			return new WP_Error( 'insert_entry_properties_failed', __( 'There was a problem while inserting the entry properties', 'gravityforms' ), $wpdb->last_error );
		}
		// Reading newly created lead id.
		$entry_id    = $wpdb->insert_id;
		$entry['id'] = $entry_id;

		// only save field values for fields that currently exist in the form
		GFFormsModel::begin_batch_field_operations();

		foreach ( $form['fields'] as $field ) {
			/* @var GF_Field $field */
			if ( $field->displayOnly ) {
				continue;
			}
			self::queue_batch_field_operation( $form, $entry, $field );
		}

		GFFormsModel::commit_batch_field_operations();

		// add save the entry meta values - only for the entry meta currently available for the form, ignore the rest
		$entry_meta = GFFormsModel::get_entry_meta( $form_id );
		if ( is_array( $entry_meta ) ) {
			foreach ( array_keys( $entry_meta ) as $key ) {
				if ( isset( $entry[ $key ] ) ) {
					gform_update_meta( $entry_id, $key, $entry[ $key ], $form['id'] );
				}
			}
		}

		// Refresh the entry
		$entry = GFAPI::get_entry( $entry['id'] );

		/**
		 * Fires after the Entry is added using the API.
		 *
		 * @since  1.9.14.26
		 *
		 * @param array $entry The Entry Object added.
		 * @param array $form  The Form Object added.
		 */
		do_action( 'gform_post_add_entry', $entry, $form );

		return $entry_id;
	}

	/**
	 * Deletes a single Entry.
	 *
	 * @since  1.8
	 * @access public
	 *
	 * @uses GFFormsModel::get_lead()
	 * @uses GFFormsModel::delete_lead()
	 *
	 * @param int $entry_id The ID of the Entry object.
	 *
	 * @return bool|WP_Error Either true for success or a WP_Error instance.
	 */
	public static function delete_entry( $entry_id ) {

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		$entry = GFFormsModel::get_entry( $entry_id );
		if ( empty( $entry ) ) {
			return new WP_Error( 'invalid_entry_id', sprintf( __( 'Invalid entry id: %s', 'gravityforms' ), $entry_id ), $entry_id );
		}
		GFFormsModel::delete_entry( $entry_id );

		return true;
	}

	/**
	 * Updates a single property of an entry.
	 *
	 * @since  1.8.3.1
	 * @access public
	 *
	 * @uses GFFormsModel::update_lead_property()
	 *
	 * @param int    $entry_id The ID of the Entry object.
	 * @param string $property The property of the Entry object to be updated.
	 * @param mixed  $value    The value to which the property should be set.
	 *
	 * @return bool Whether the entry property was updated successfully.
	 */
	public static function update_entry_property( $entry_id, $property, $value ) {
		if ( gf_upgrade()->get_submissions_block() ) {
			return false;
		}
		return GFFormsModel::update_entry_property( $entry_id, $property, $value );
	}

	/**
	 * Updates a single field of an entry.
	 *
	 * @since  1.9
	 * @access public
	 *
	 * @param int    $entry_id   The ID of the Entry object.
	 * @param string $input_id   The id of the input to be updated. For single input fields such as text, paragraph,
	 *                           website, drop down etc... this will be the same as the field ID. For multi input
	 *                           fields such as name, address, checkboxes, etc... the input id will be in the format
	 *                           {FIELD_ID}.{INPUT NUMBER}. ( i.e. "1.3" ). The $input_id can be obtained by inspecting
	 *                           the key for the specified field in the $entry object.
	 * @param mixed  $value      The value to which the field should be set.
	 * @param string $item_index The item index if the field is inside a Repeater.
	 *
	 * @return bool|array Whether the entry property was updated successfully. If there's an error getting the entry,
	 *                    the entry object.
	 */
	public static function update_entry_field( $entry_id, $input_id, $value, $item_index = '' ) {
		global $wpdb;

		if ( gf_upgrade()->get_submissions_block() ) {
			return false;
		}

		if ( version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '<' ) ) {
			return GF_Forms_Model_Legacy::update_entry_field( $entry_id, $input_id, $value );
		}

		$entry = self::get_entry( $entry_id );
		if ( is_wp_error( $entry ) ) {
			return $entry;
		}

		$form = self::get_form( $entry['form_id'] );
		if ( ! $form ) {
			return false;
		}

		$field = self::get_field( $form, $input_id );

		$entry_meta_table_name = GFFormsModel::get_entry_meta_table_name();
		$result                = true;

		// If it's a Repeater field.
		if ( $field instanceof GF_Field_Repeater && isset( $field->fields ) && is_array( $field->fields ) ) {
			if ( isset( $entry[ $input_id ] ) ) {
				// delete all values in the repeater field.
				$result = GFFormsModel::update_entry_field_value( $form, $entry, $field, 0, $input_id, '' );
			}
			if ( true !== $result ) {
				return $result;
			}

			foreach ( $value as $i => $sub_values ) {
				$new_item_index = $item_index . '_' . $i;
				foreach ( $sub_values as $key => $sub_value ) {
					$result = self::update_entry_field( $entry_id, $key, $sub_value, $new_item_index );

					if ( true !== $result ) {
						return $result;
					}
				}
			}
		} else {
			$sql = $wpdb->prepare( "SELECT id FROM {$entry_meta_table_name} WHERE entry_id=%d AND meta_key=%s", $entry_id, $input_id );
			if ( $item_index ) {
				$sql .= $wpdb->prepare( ' AND item_index=%s', $item_index );
			}

			$lead_detail_id = $wpdb->get_var( $sql );

			if ( ! isset( $entry[ $input_id ] ) || ( $value === 0 && $entry[ $input_id ] !== '0' ) || $entry[ $input_id ] != $value ) {
				$result = GFFormsModel::update_entry_field_value( $form, $entry, $field, $lead_detail_id, $input_id, $value, $item_index );
			}
		}

		return $result;
	}

	// ENTRY NOTES ------------------------------------------------

	/**
	 * Get notes based on search criteria.
	 *
	 * @since 2.4.18
	 *
	 * @param array $search_criteria Array of search criteria.
	 * @param array $sorting Sort key and direction.
	 * @return array|bool
	 */
	public static function get_notes( $search_criteria = array(), $sorting = null ) {

		if ( ! $sorting ) {
			$sorting = array(
				'key'        => 'id',
				'direction'  => 'ASC',
				'is_numeric' => true,
			);
		}

		$notes = GFFormsModel::get_notes( $search_criteria, $sorting );

		if ( empty( $notes ) ) {
			return false;
		}

		return $notes;
	}

	/**
	 * Get note by ID.
	 *
	 * @since 2.4.18
	 *
	 * @param int $note_id ID of the note to retrieve.
	 * @return array|WP_Error
	 */
	public static function get_note( $note_id ) {
		$note = GFFormsModel::get_notes( array( 'id' => $note_id ) );

		if ( empty( $note ) ) {
			return new WP_Error( 'note_not_found', __( 'Note not found', 'gravityforms' ) );
		}

		return $note[0];
	}

	/**
	 * Create one note for an entry.
	 *
	 * @since 2.4.18
	 *
	 * @param int    $entry_id ID of the entry to add the note to.
	 * @param int    $user_id ID of the user to associate with the note.
	 * @param string $user_name Name of the user to associate with the note.
	 * @param string $note Text of the note.
	 * @param string $note_type Note type.
	 * @param null   $sub_type Not sub-type.
	 * @return array|int|void|WP_Error
	 */
	public static function add_note( $entry_id, $user_id, $user_name, $note, $note_type = 'user', $sub_type = null ) {
		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		if ( ! self::entry_exists( $entry_id ) ) {
			return new WP_Error( 'invalid_entry', __( 'Invalid entry', 'gravityforms' ), $entry_id );
		}

		if ( empty( $note ) || ! is_string( $note ) ) {
			return new WP_Error( 'invalid_note', __( 'Invalid or empty note', 'gravityforms' ), $entry_id );
		}

		$new_note = GFFormsModel::add_note( intval( $entry_id ), $user_id, $user_name, wp_kses_post( $note ), sanitize_text_field( $note_type ), sanitize_text_field( $sub_type ) );

		return $new_note;
	}

	/**
	 * Delete one note.
	 *
	 * @since 2.4.18
	 *
	 * @param int $note_id ID of the note to delete.
	 * @return int|WP_Error ID of the deleted note.
	 */
	public static function delete_note( $note_id ) {
		$result = GFFormsModel::delete_note( $note_id );

		if ( ! $result ) {
			return new WP_Error( 'invalid_note', __( 'Invalid note', 'gravityforms' ), $note_id );
		}

		return $result;
	}

	/**
	 * Update a note.
	 *
	 * @since 2.4.18
	 *
	 * @param array $note {
	 * 		Note data to update.
	 *
	 *		@type int    $entry_id     ID of the entry associated with the note.
	 *		@type int    $user_id      ID of the user associated with the note.
	 * 		@type string $user_name    Name of the user associated with the note.
	 *		@type string $date_created Date and time the note was created, in SQL datetime format.
	 *		@type string $value        The text of the note.
	 *		@type string $note_type    The note type.
	 *		@type string $sub_type     The note subtype.
	 * }
	 * @param int   $note_id ID of the note to update.
	 * @return bool|WP_Error
	 */
	public static function update_note( $note, $note_id = '' ) {
		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		if ( ! is_array( $note ) || empty( $note ) ) {
			return new WP_Error( 'invalid_note_format', __( 'Invalid note format', 'gravityforms' ) );
		}

		if ( empty( $note_id ) ) {
			if ( rgar( $note, 'id' ) ) {
				$note_id = absint( $note['id'] );
			}
		} else {
			$note_id = absint( $note_id );
		}

		if ( empty( $note_id ) ) {
			return new WP_Error( 'missing_note_id', __( 'Missing note id', 'gravityforms' ) );
		}

		// make sure the note exists.
		$current_note = self::get_note( $note_id );
		if ( ! $current_note || is_wp_error( $current_note ) ) {
			return new WP_Error( 'note_not_found', __( 'Note not found', 'gravityforms' ) );
		}

		$note_properties = array(
			'id',
			'entry_id',
			'user_id',
			'user_name',
			'date_created',
			'value',
			'note_type',
			'sub_type',
		);

		$current_note_array = (array) $current_note;

		foreach ( $note_properties as $property ) {
			if ( ! isset( $note[ $property ] ) ) {
				$note[ $property ] = $current_note_array[ $property ];
			}
		}

		$result = GFFormsModel::update_note( $note['id'], $note['entry_id'], $note['user_id'], $note['user_name'], $note['date_created'], $note['value'], $note['note_type'], $note['sub_type'] );

		return $result;
	}

	// FORM SUBMISSIONS -------------------------------------------

	/**
	 * Submits a form. Use this function to send input values through the complete form submission process.
	 * Supports field validation, notifications, confirmations, multiple-pages and save & continue.
	 *
	 * Example usage:
	 * $input_values['input_1']   = 'Single line text';
	 * $input_values['input_2_3'] = 'First name';
	 * $input_values['input_2_6'] = 'Last name';
	 * $input_values['input_5']   = 'A paragraph of text.';
	 * //$input_values['gform_save'] = true; // support for save and continue
	 *
	 * $result = GFAPI::submit_form( 52, $input_values );
	 *
	 * Example output for a successful submission:
	 * 'is_valid' => boolean true
	 * 'page_number' => int 0
	 * 'source_page_number' => int 1
	 * 'confirmation_message' => string 'confirmation message [snip]'
	 *
	 * Example output for failed validation:
	 * 'is_valid' => boolean false
	 * 'validation_messages' =>
	 *      array (size=1)
	 *          2 => string 'This field is required. Please enter the first and last name.'
	 *	'page_number' => int 1
	 *  'source_page_number' => int 1
	 *	'confirmation_message' => string ''
	 *
	 *
	 * Example output for save and continue:
	 * 'is_valid' => boolean true
	 * 'page_number' => int 1
	 * 'source_page_number' => int 1
	 * 'confirmation_message' => string 'Please use the following link to return to your form from any computer. [snip]'
	 * 'resume_token' => string '045f941cc4c04d479556bab1db6d3495'
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAPI::get_form()
	 * @uses GFCommon::get_base_path()
	 * @uses GFFormDisplay::process_form()
	 * @uses GFFormDisplay::replace_save_variables()
	 *
	 * @param int $form_id The Form ID
	 * @param array $input_values An array of values. Not $_POST, that will be automatically merged with the $input_values.
	 * @param array $field_values Optional.
	 * @param int $target_page Optional.
	 * @param int $source_page Optional.
	 *
	 * @return array|WP_Error An array containing the result of the submission.
	 */
	public static function submit_form( $form_id, $input_values, $field_values = array(), $target_page = 0, $source_page = 1 ) {

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		$form_id = absint( $form_id );
		$form    = self::get_submission_form( $form_id );

		if ( is_wp_error( $form ) ) {
			return $form;
		}

		self::hydrate_post( $form_id, $input_values, $field_values, $target_page, $source_page );

		// Ensure that confirmation handler doesn't send a redirect header or add redirect JavaScript.
		add_filter( 'gform_suppress_confirmation_redirect', '__return_true' );

		try {
			require_once GFCommon::get_base_path() . '/form_display.php';
			GFFormDisplay::process_form( $form_id, GFFormDisplay::SUBMISSION_INITIATED_BY_API );
		} catch ( Exception $ex ) {
			remove_filter( 'gform_suppress_confirmation_redirect', '__return_true' );
			remove_filter( 'gform_pre_validation', array( 'GFAPI', 'submit_form_filter_gform_pre_validation' ), 50 );
			return new WP_Error( 'error_processing_form', __( 'There was an error while processing the form:', 'gravityforms' ) . ' ' . $ex->getCode() . ' ' . $ex->getMessage() );
		}

		remove_filter( 'gform_suppress_confirmation_redirect', '__return_true' );

		remove_filter( 'gform_pre_validation', array( 'GFAPI', 'submit_form_filter_gform_pre_validation' ), 50 );


		if ( empty( GFFormDisplay::$submission ) ) {
			return new WP_Error( 'error_processing_form', __( 'There was an error while processing the form:', 'gravityforms' ) );
		}

		$submissions_array = GFFormDisplay::$submission;

		$submission_details = $submissions_array[ $form_id ];

		$result = array();

		$result['is_valid'] = $submission_details['is_valid'];

		if ( $result['is_valid'] == false ) {
			$result['validation_messages'] = self::get_field_validation_errors( $submission_details['form'] );
		}

		$result['form']               = $submission_details['form'];
		$result['page_number']        = $submission_details['page_number'];
		$result['source_page_number'] = $submission_details['source_page_number'];

		if ( $submission_details['is_valid'] ) {
			$confirmation_message = $submission_details['confirmation_message'];

			if ( is_array( $confirmation_message ) ) {
				if ( isset( $confirmation_message['redirect'] ) ) {
					$result['confirmation_message'] = '';
					$result['confirmation_redirect'] = $confirmation_message['redirect'];
					$result['confirmation_type'] = 'redirect';
				} else {
					$result['confirmation_message'] = $confirmation_message;
				}
			} else {
				$result['confirmation_message'] = $confirmation_message;
				$result['confirmation_type'] = 'message';
			}

			$result['entry_id'] = rgars( $submission_details, 'lead/id' );
			$result['is_spam']  = rgars( $submission_details, 'is_spam' );
		}

		if ( isset( $submission_details['resume_token'] ) ) {
			$result['resume_token'] = $submission_details['resume_token'];

			$form = self::get_form( $form_id );

			$result['confirmation_message'] = GFFormDisplay::replace_save_variables( $result['confirmation_message'], $form, $result['resume_token'] );
		}

		return $result;
	}

	/**
	 * Validates the field values.
	 *
	 * @since 2.6.4
	 *
	 * @param int   $form_id      The ID of the form this submission belongs to.
	 * @param array $input_values Optional. An associative array containing the values to be validated using the field input names as the keys. Will be merged into the $_POST.
	 * @param array $field_values Optional. An array of dynamic population parameter keys with their corresponding values used to populate the fields. Overwrites `$_POST['gform_field_values']`.
	 * @param int   $target_page  Optional. For multi-page forms; indicates which page would be loaded next if the current page passes validation. Overwrites `$_POST[ 'gform_target_page_number_' . $form_id ]`.
	 * @param int   $source_page  Optional. For multi-page forms; indicates which page was active when the values were submitted for validation. Overwrites `$_POST[ 'gform_source_page_number_' . $form_id ]`.
	 *
	 * @return WP_Error|array
	 */
	public static function validate_form( $form_id, $input_values = array(), $field_values = array(), $target_page = 0, $source_page = 1 ) {

		$form_id = absint( $form_id );
		$form    = self::get_submission_form( $form_id );

		if ( is_wp_error( $form ) ) {
			return $form;
		}

		if ( GFCommon::form_requires_login( $form ) && ! is_user_logged_in() ) {
			return new WP_Error( 'login_required', __( 'You must be logged in to use this form.', 'gravityforms' ) );
		}

		self::hydrate_post( $form_id, $input_values, $field_values, $target_page, $source_page );

		// Support validation of multi-file enabled fields by getting the details from the gform_uploaded_files input.
		GFFormsModel::set_uploaded_files( $form_id );

		$failed_validation_page = $source_page;

		require_once GFCommon::get_base_path() . '/form_display.php';
		GFFormDisplay::$submission_initiated_by = GFFormDisplay::SUBMISSION_INITIATED_BY_API_VALIDATION;

		$is_valid = GFFormDisplay::validate( $form, $field_values, $source_page, $failed_validation_page );
		remove_filter( 'gform_pre_validation', array( 'GFAPI', 'submit_form_filter_gform_pre_validation' ), 50 );

		$result = array(
			'is_valid'            => $is_valid,
			'validation_messages' => array(),
			'page_number'         => $is_valid ? $target_page : $failed_validation_page,
			'source_page_number'  => $source_page,
			'form'                => $form,
		);

		if ( $is_valid ) {
			if ( $target_page === 0 ) {
				$result['is_spam'] = GFCommon::is_spam_entry( GFFormsModel::create_lead( $form ), $form );
			}

			return $result;
		}

		$form_restriction_error = rgars( GFFormDisplay::$submission, $form_id . '/form_restriction_error' );
		if ( $form_restriction_error ) {
			return new WP_Error( 'form_restriction_error', $form_restriction_error );
		}

		$result['validation_messages'] = self::get_field_validation_errors( $form );

		return $result;
	}

	/**
	 * Validates the submitted value of the specified field.
	 *
	 * @since 2.7
	 * @since 2.8.7 Added the gform_pre_validation filter.
	 *
	 * @param int   $form_id      The ID of the form this submission belongs to.
	 * @param int   $field_id     The ID of the field to be validated.
	 * @param array $input_values Optional. An associative array containing the values to be validated using the field input names as the keys. Will be merged into the $_POST.
	 *
	 * @return WP_Error|array
	 */
	public static function validate_field( $form_id, $field_id, $input_values = array() ) {
		$form = self::get_submission_form( $form_id );
		if ( is_wp_error( $form ) ) {
			return $form;
		}

		$gform_pre_validation_args = array( 'gform_pre_validation', $form_id );
		if ( gf_has_filter( $gform_pre_validation_args ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Executing functions hooked to gform_pre_validation.' );
			/**
			 * Allows the form to be modified before the submission is validated.
			 *
			 * @since 1.7
			 * @since 1.9 Added the form specific version.
			 *
			 * @param array $form The form for the submission to be validated.
			 */
			$form = gf_apply_filters( $gform_pre_validation_args, $form );
			GFCommon::log_debug( __METHOD__ . '(): Completed gform_pre_validation.' );
		}


		$field = self::get_field( $form, $field_id );
		if ( ! $field ) {
			return new WP_Error( 'field_not_found', __( 'Field not found.', 'gravityforms' ) );
		}

		require_once GFCommon::get_base_path() . '/form_display.php';
		if ( ! GFFormDisplay::is_field_validation_supported( $field ) ) {
			return new WP_Error( 'not_supported', __( 'Field does not support validation.', 'gravityforms' ) );
		}

		self::hydrate_post( $form_id, $input_values, array(), 0, $field->pageNumber );

		// Ensure the state input is populated.
		self::submit_form_filter_gform_pre_validation( $form );

		return GFFormDisplay::validate_field( $field, $form, 'api-validate' );
	}

	/**
	 * Returns the form to be used to process the submission or an error if the form doesn't exist or isn't accepting submissions.
	 *
	 * @since 2.6.4
	 *
	 * @param int $form_id The ID of the form this submission belongs to.
	 *
	 * @return array|WP_Error
	 */
	private static function get_submission_form( $form_id ) {
		$form = GFAPI::get_form( $form_id );

		if ( empty( $form ) || ! $form['is_active'] || $form['is_trash'] ) {
			return new WP_Error( 'form_not_found', __( 'Your form could not be found', 'gravityforms' ) );
		}

		if ( ! GFCommon::form_has_fields( $form ) ) {
			return new WP_Error( 'no_fields', __( "Your form doesn't have any fields.", 'gravityforms' ) );
		}

		return $form;
	}

	/**
	 * Populates the $_POST with the form submission values.
	 *
	 * @since 2.6.4
	 *
	 * @param int   $form_id      The ID of the form this submission belongs to.
	 * @param array $input_values An associative array containing the submitted values using the field input names as the keys.
	 * @param array $field_values An array of dynamic population parameter keys with their corresponding values used to populate the fields.
	 * @param int   $target_page  Indicates which page would be loaded next if the current page passes validation.
	 * @param int   $source_page  Indicates which page was active when the values were submitted.
	 */
	private static function hydrate_post( $form_id, $input_values, $field_values, $target_page, $source_page ) {
		if ( ! isset( $_POST ) ) {
			$_POST = array();
		}

		if ( ! empty( $input_values ) ) {
			$_POST = array_merge_recursive( $_POST, $input_values );
		}

		self::normalize_post_keys();

		$_POST[ 'is_submit_' . $form_id ]                = true;
		$_POST['gform_submit']                           = $form_id;
		$_POST[ 'gform_target_page_number_' . $form_id ] = absint( $target_page );
		$_POST[ 'gform_source_page_number_' . $form_id ] = absint( $source_page );
		$_POST['gform_field_values']                     = $field_values;

		// Adds the state to the $_POST, if missing.
		add_filter( 'gform_pre_validation', array( 'GFAPI', 'submit_form_filter_gform_pre_validation' ), 50 );
	}

	/**
	 * Ensures the $_POST input names use underscores (e.g. input_1_1) instead of the periods used on the front-end (e.g. input_1.1).
	 *
	 * @since 2.6.4
	 */
	private static function normalize_post_keys() {
		$_POST = array_combine( array_map( function ( $key ) {
			return str_replace( '.', '_', $key );
		}, array_keys( $_POST ) ), array_values( $_POST ) );
	}

	/**
	 * Creates an array using the field IDs as keys to the validation error messages.
	 *
	 * @since 2.6.4
	 *
	 * @param array $form The form that was validated.
	 *
	 * @return array
	 */
	private static function get_field_validation_errors( $form ) {
		$errors = array();

		foreach ( $form['fields'] as $field ) {
			if ( $field->failed_validation ) {
				$errors[ (string) $field->id ] = $field->validation_message;
			}
		}

		return $errors;
	}

	/**
	 * Ensure that the state field is set when the form is submitted via GFAPI::submit_form()
	 * or via the POST forms/[id]/submissions REST API endpoint.
	 *
	 * @since 2.4.11
	 *
	 * @param array $form
	 *
	 * @return array
	 */
	public static function submit_form_filter_gform_pre_validation( $form ) {
		$name = 'state_' . absint( $form['id'] );
		if ( ! isset( $_POST[ $name ] ) ) {
			$field_values   = rgpost( 'gform_field_values' );
			$_POST[ $name ] = GFFormDisplay::get_state( $form, $field_values );
		}

		return $form;
	}

	// FEEDS ------------------------------------------------------

	/**
	 * Returns all the feeds for the given criteria.
	 *
	 * @since 1.8
	 * @since 2.4.24 Updated $is_active to support using null to return both active and inactive feeds.
	 * @since 2.6.1  Updated $form_ids to support an array of IDs.
	 * @since 2.7.17 Added support for decrypting settings fields.
	 *
	 * @param mixed          $feed_ids   The ID of the Feed or an array of Feed IDs.
	 * @param null|int|int[] $form_ids   The ID of the Form to which the Feeds belong or array of Form IDs.
	 * @param null|string    $addon_slug The slug of the add-on to which the Feeds belong.
	 * @param bool|null      $is_active  Indicates if only active or inactive feeds should be returned. Use null to return both.
	 *
	 * @return array|WP_Error Either an array of Feed objects or a WP_Error instance.
	 */
	public static function get_feeds( $feed_ids = null, $form_ids = null, $addon_slug = null, $is_active = true ) {
		global $wpdb;

		$table = $wpdb->prefix . 'gf_addon_feed';

		if ( ! GFCommon::table_exists( $table ) ) {
			return self::get_missing_table_wp_error( $table );
		}

		$where_arr = array();
		if ( null !== $is_active ) {
			$where_arr[] = $wpdb->prepare( 'is_active=%d', $is_active );
		}
		if ( false === empty( $form_ids ) ) {
			if ( ! is_array( $form_ids ) ) {
				$where_arr[] = $wpdb->prepare( 'form_id=%d', $form_ids );
			} else {
				$in_str_arr  = array_fill( 0, count( $form_ids ), '%d' );
				$in_str      = join( ',', $in_str_arr );
				$where_arr[] = $wpdb->prepare( "form_id IN ($in_str)", $form_ids );
			}
		}
		if ( false === empty( $addon_slug ) ) {
			$where_arr[] = $wpdb->prepare( 'addon_slug=%s', $addon_slug );
		}
		if ( false === empty( $feed_ids ) ) {
			if ( ! is_array( $feed_ids ) ) {
				$where_arr[] = $wpdb->prepare( 'id=%d', $feed_ids );
			} else {
				$in_str_arr  = array_fill( 0, count( $feed_ids ), '%d' );
				$in_str      = join( ',', $in_str_arr );
				$where_arr[] = $wpdb->prepare( "id IN ($in_str)", $feed_ids );
			}
		}

		$sql = "SELECT * FROM {$table}";

		if ( ! empty( $where_arr ) ) {
			$sql .= ' WHERE ' . join( ' AND ', $where_arr );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $sql, ARRAY_A );
		if ( empty( $results ) ) {
			return new WP_Error( 'not_found', __( 'Feed not found', 'gravityforms' ) );
		}

		foreach ( $results as &$result ) {
			$result['meta'] = self::get_encryptor()->decrypt_feed_meta( json_decode( $result['meta'], true ) );
		}

		return $results;
	}

	/**
	 * Encrypts feed meta fields based on feed settings fields configuratino and returns the resulting feed meta array.
	 *
	 * @since 2.7.17
	 *
	 * @param array  $feed_meta  The feed meta array to encrypt.
	 * @param string $addon_slug The slug of the add-on to which the feed belongs.
	 *
	 * @return array Returns the feed meta arra with the fields that should be encrypted.
	 */
	public static function encrypt_feed_meta( $feed_meta, $addon_slug ) {

		require_once( GFCommon::get_base_path() . '/includes/addon/class-gf-addon.php' );
		$addon = GFAddon::get_addon_by_slug( $addon_slug );
		if ( ! is_a( $addon, 'GFAddon' ) ) {
			return $feed_meta;
		}

		return self::get_encryptor()->encrypt_feed_meta( $feed_meta, $addon->get_fields_to_encrypt() );
	}

	/**
	 * The encryption service object.
	 *
	 * @since 2.7.17
	 *
	 * @var \Gravity_Forms\Gravity_Forms\Settings\GF_Settings_Encryption The encryption service object.
	 */
	private static $_encryptor;

	/**
	 * Gets the encryption service object.
	 *
	 * @since 2.7.17
	 *
	 * @return \Gravity_Forms\Gravity_Forms\Settings\GF_Settings_Encryption An instance of the encryption service object.
	 */
	public static function get_encryptor() {
		if ( ! self::$_encryptor ) {
			require_once( GFCommon::get_base_path() . '/includes/settings/class-gf-settings-service-provider.php' );
			self::$_encryptor = GFForms::get_service_container()->get( \Gravity_Forms\Gravity_Forms\Settings\GF_Settings_Service_Provider::SETTINGS_ENCRYPTION );
		}

		return self::$_encryptor;
	}

	/**
	 * Sets the encryption service object to be used by GFAPI
	 *
	 * @since 2.7.17
	 *
	 * @param Gravity_Forms\Gravity_Forms\Settings\GF_Settings_Encryption $encryptor The encryption service object to be used.
	 *
	 * @return void
	 */
	public static function set_encryptor( $encryptor ) {
		self::$_encryptor = $encryptor;
	}

	/**
	 * Returns a specific feed.
	 *
	 * @since 2.4.24
	 *
	 * @param int $feed_id The ID of the feed to retrieve.
	 *
	 * @return array|WP_Error
	 */
	public static function get_feed( $feed_id ) {
		$feeds = self::get_feeds( $feed_id, null, null, null );
		if ( is_wp_error( $feeds ) ) {
			return $feeds;
		}

		return $feeds[0];
	}

	/**
	 * Deletes a single Feed.
	 *
	 * @since  1.8
	 * @access public
	 * @global $wpdb
	 *
	 * @param int $feed_id The ID of the Feed to delete.
	 *
	 * @return bool|WP_Error True if successful, or a WP_Error instance.
	 */
	public static function delete_feed( $feed_id ) {
		global $wpdb;

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		$table = $wpdb->prefix . 'gf_addon_feed';

		if ( ! GFCommon::table_exists( $table ) ) {
			return self::get_missing_table_wp_error( $table );
		}

		$sql = $wpdb->prepare( "DELETE FROM {$table} WHERE id=%d", $feed_id );

		$results = $wpdb->query( $sql );
		if ( false === $results ) {
			return new WP_Error( 'error_deleting', sprintf( __( 'There was an error while deleting feed id %s', 'gravityforms' ), $feed_id ), $wpdb->last_error );
		}

		if ( 0 === $results ) {
			return new WP_Error( 'not_found', sprintf( __( 'Feed id %s not found', 'gravityforms' ), $feed_id ) );
		}

		return true;
	}

	/**
	 * Updates a feed.
	 *
	 * @since Unknown
	 * @since 2.7.17 Added support for encrypting settings fields.
	 *
	 * @param int   $feed_id   The ID of the feed being updated.
	 * @param array $feed_meta The feed meta to replace the existing feed meta.
	 * @param null  $form_id   The ID of the form that the feed is associated with
	 *
	 * @return int|WP_Error The number of rows updated or a WP_Error instance
	 */
	public static function update_feed( $feed_id, $feed_meta, $form_id = null ) {
		global $wpdb;

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		$lookup_result = self::get_feeds( $feed_id, $form_id );

		if ( is_wp_error( $lookup_result ) ) {
			return $lookup_result;
		}

		$feed_meta = self::encrypt_feed_meta( $feed_meta, $lookup_result[0]['addon_slug'] );

		$feed_meta_json = json_encode( $feed_meta );
		$table          = $wpdb->prefix . 'gf_addon_feed';
		if ( empty( $form_id ) ) {
			$sql = $wpdb->prepare( "UPDATE {$table} SET meta= %s WHERE id=%d", $feed_meta_json, $feed_id );
		} else {
			$sql = $wpdb->prepare( "UPDATE {$table} SET form_id = %d, meta= %s WHERE id=%d", $form_id, $feed_meta_json, $feed_id );
		}

		$results = $wpdb->query( $sql );

		if ( false === $results ) {
			return new WP_Error( 'error_updating', sprintf( __( 'There was an error while updating feed id %s', 'gravityforms' ), $feed_id ), $wpdb->last_error );
		}

		return $results;
	}

	/**
	 * Adds a feed with the given Feed object.
	 *
	 * @since  1.8
	 * @since 2.7.17 Added support for encrypting settings fields.
	 *
	 * @access public
	 * @global $wpdb
	 *
	 * @param int    $form_id    The ID of the form to which the feed belongs.
	 * @param array  $feed_meta  The Feed Object.
	 * @param string $addon_slug The slug of the add-on to which the feeds belong.
	 *
	 * @return int|WP_Error Either the ID of the newly created feed or a WP_Error instance.
	 */
	public static function add_feed( $form_id, $feed_meta, $addon_slug ) {
		global $wpdb;

		if ( gf_upgrade()->get_submissions_block() ) {
			return new WP_Error( 'submissions_blocked', __( 'Submissions are currently blocked due to an upgrade in progress', 'gravityforms' ) );
		}

		$table = $wpdb->prefix . 'gf_addon_feed';

		if ( ! GFCommon::table_exists( $table ) ) {
			return self::get_missing_table_wp_error( $table );
		}

		if ( $form_id !== 0 && $form_id !== '0' && ! self::form_id_exists( $form_id ) ) {
			return new WP_Error( 'not_found', __( 'Form not found', 'gravityforms' ) );
		}

		$feed_meta = self::encrypt_feed_meta( $feed_meta, $addon_slug );

		$feed_meta_json = json_encode( $feed_meta );
		$sql            = $wpdb->prepare( "INSERT INTO {$table} (form_id, meta, addon_slug) VALUES (%d, %s, %s)", $form_id, $feed_meta_json, $addon_slug );

		$results = $wpdb->query( $sql );

		if ( false === $results ) {
			return new WP_Error( 'error_inserting', __( 'There was an error while inserting a feed', 'gravityforms' ), $wpdb->last_error );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Updates the specified feed with the given property value.
	 *
	 * @since 2.4.24
	 *
	 * @param int    $feed_id        The ID of the feed being updated.
	 * @param string $property_name  The name of the property (column) being updated.
	 * @param mixed  $property_value The new value of the specified property.
	 *
	 * @return bool|WP_Error
	 */
	public static function update_feed_property( $feed_id, $property_name, $property_value ) {
		return GFFormsModel::update_feed_property( $feed_id, $property_name, $property_value );
	}

	/**
	 * Returns the missing_table WP_Error.
	 *
	 * @since 2.4.22
	 *
	 * @param string $table The name of the table which does not exist.
	 *
	 * @return WP_Error
	 */
	private static function get_missing_table_wp_error( $table ) {
		return new WP_Error( 'missing_table', sprintf( __( 'The %s table does not exist.', 'gravityforms' ), $table ) );
	}

	/**
	 * Triggers processing of non-payment add-on feeds for the given entry.
	 *
	 * @since 2.9.2
	 *
	 * @param array  $entry      The entry to be processed.
	 * @param array  $form       The form the entry belongs to.
	 * @param string $addon_slug A specific add-on slug, or an empty string for all non-payment add-on feeds to be processed.
	 * @param bool   $reset_meta Indicates if the processed feeds meta for the entry should be reset.
	 *
	 * @return false|array
	 */
	public static function maybe_process_feeds( $entry, $form, $addon_slug = '', $reset_meta = false ) {
		if ( ! class_exists( 'GFFeedAddOn' ) || empty( $entry['id'] ) ) {
			return false;
		}

		$addons = GFFeedAddOn::get_registered_feed_addons();

		if ( ! empty( $addon_slug ) ) {
			$addon = rgar( $addons, $addon_slug );
			if ( empty( $addon ) || $addon instanceof GFPaymentAddOn ) {
				return false;
			}

			if ( $reset_meta ) {
				self::update_processed_feeds_meta( $entry['id'], $addon_slug, null );
			}

			$entry = $addon->maybe_process_feed( $entry, $form );
		} else {
			foreach ( $addons as $slug => $addon ) {
				if ( $addon instanceof GFPaymentAddOn ) {
					continue;
				}

				if ( $reset_meta ) {
					self::update_processed_feeds_meta( $entry['id'], $slug, null );
				}

				$entry = $addon->maybe_process_feed( $entry, $form );
			}
		}

		gf_feed_processor()->save()->dispatch();

		return $entry;
	}

	/**
	 * Returns the processed feeds meta for the specified entry.
	 *
	 * @since 2.9.2
	 *
	 * @param int    $entry_id   The ID of the entry the meta is to be retrieved for.
	 * @param string $addon_slug An add-on slug to return the IDs for a specific add-on or an empty string to return the meta for all add-ons.
	 *
	 * @return array
	 */
	public static function get_processed_feeds_meta( $entry_id, $addon_slug = '' ) {
		$meta = gform_get_meta( $entry_id, 'processed_feeds' );

		if ( empty( $addon_slug ) ) {
			return is_array( $meta ) ? $meta : array();
		}

		return rgar( $meta, $addon_slug, array() );
	}

	/**
	 * Updates or deletes the processed feeds meta for the specified entry.
	 *
	 * @since 2.9.2
	 *
	 * @param int            $entry_id   The ID of the entry the meta is to be updated for.
	 * @param string         $addon_slug An add-on slug when updating the meta for a specific add-on or an empty string to update the meta for all add-ons.
	 * @param int|array|null $value      The ID of a processed feed for a specific add-on, an array of processed feed IDs for a specific add-on, an array using add-on slugs as the keys to arrays of processed feed IDs, or null to clear the meta.
	 * @param null|int       $form_id    The form ID of the entry (optional, saves extra query if passed when creating the metadata).
	 *
	 * @return void
	 */
	public static function update_processed_feeds_meta( $entry_id, $addon_slug, $value, $form_id = null ) {
		if ( empty( $addon_slug ) ) {
			if ( empty( $value ) ) {
				gform_delete_meta( $entry_id, 'processed_feeds' );
			} elseif ( ! isset( $value[0] ) ) {
				gform_update_meta( $entry_id, 'processed_feeds', $value, $form_id );
			}

			return;
		}

		$meta = self::get_processed_feeds_meta( $entry_id );

		if ( empty( $value ) ) {
			if ( empty( $meta ) ) {
				gform_delete_meta( $entry_id, 'processed_feeds' );

				return;
			}
			unset( $meta[ $addon_slug ] );
		} elseif ( is_array( $value ) ) {
			$meta[ $addon_slug ] = $value;
		} else {
			$meta[ $addon_slug ][] = $value;
		}

		gform_update_meta( $entry_id, 'processed_feeds', $meta, $form_id );
	}

	// NOTIFICATIONS ----------------------------------------------

	/**
	 * Triggers sending of active notifications for the given form, entry, and event.
	 *
	 * @since Unknown
	 * @since 2.6.9 Added support for async processing of notifications.
	 *
	 * @param array  $form  The Form Object associated with the notification.
	 * @param array  $entry The Entry Object associated with the triggered event.
	 * @param string $event Optional. The event that's firing the notification. Defaults to 'form_submission'.
	 * @param array  $data  Optional. Array of data which can be used in the notifications via the generic {object:property} merge tag. Defaults to empty array.
	 *
	 * @return array
	 */
	public static function send_notifications( $form, $entry, $event = 'form_submission', $data = array() ) {

		if ( rgempty( 'notifications', $form ) || ! is_array( $form['notifications'] ) ) {
			return array();
		}

		$form_id  = absint( rgar( $form, 'id' ) );
		$entry_id = absint( rgar( $entry, 'id' ) );
		GFCommon::log_debug( __METHOD__ . "(): Gathering notifications for {$event} event for entry #{$entry_id}." );

		$notifications_to_send = array();

		// Running through filters that disable form submission notifications.
		foreach ( $form['notifications'] as $notification ) {
			if ( rgar( $notification, 'event' ) != $event ) {
				continue;
			}

			if ( $event == 'form_submission' ) {
				/**
				 * Disables user notifications.
				 *
				 * @since Unknown
				 *
				 * @param bool  false  Determines if the notification will be disabled. Set to true to disable the notification.
				 * @param array $form  The Form Object that triggered the notification event.
				 * @param array $entry The Entry Object that triggered the notification event.
				 */
				if ( rgar( $notification, 'type' ) == 'user' && gf_apply_filters( array( 'gform_disable_user_notification', $form_id ), false, $form, $entry ) ) {
					GFCommon::log_debug( __METHOD__ . "(): Notification is disabled by gform_disable_user_notification hook, not including notification (#{$notification['id']} - {$notification['name']})." );
					// Skip user notification if it has been disabled by a hook.
					continue;
					/**
					 * Disables admin notifications.
					 *
					 * @since Unknown
					 *
					 * @param bool  false  Determines if the notification will be disabled. Set to true to disable the notification.
					 * @param array $form  The Form Object that triggered the notification event.
					 * @param array $entry The Entry Object that triggered the notification event.
					 */
				} elseif ( rgar( $notification, 'type' ) == 'admin' && gf_apply_filters( array( 'gform_disable_admin_notification', $form_id ), false, $form, $entry ) ) {
					GFCommon::log_debug( __METHOD__ . "(): Notification is disabled by gform_disable_admin_notification hook, not including notification (#{$notification['id']} - {$notification['name']})." );
					// Skip admin notification if it has been disabled by a hook.
					continue;
				}
			}

			/**
			 * Disables notifications.
			 *
			 * @since 2.3.6.6 Added the $data param.
			 * @since Unknown
			 *
			 * @param bool  false  Determines if the notification will be disabled. Set to true to disable the notification.
			 * @param array $form  The Form Object that triggered the notification event.
			 * @param array $entry The Entry Object that triggered the notification event.
			 * @param array $data  Array of data which can be used in the notifications via the generic {object:property} merge tag. Defaults to empty array.
			 */
			if ( gf_apply_filters( array( 'gform_disable_notification', $form_id ), false, $notification, $form, $entry, $data ) ) {
				GFCommon::log_debug( __METHOD__ . "(): Notification is disabled by gform_disable_notification hook, not including notification (#{$notification['id']} - {$notification['name']})." );
				// Skip notifications if it has been disabled by a hook
				continue;
			}

			$notifications_to_send[] = $notification['id'];
		}

		if ( empty( $notifications_to_send ) ) {
			GFCommon::log_debug( __METHOD__ . "(): Aborting. No notifications to process for {$event} event for entry #{$entry_id}." );

			return $notifications_to_send;
		}

		/**
		 * @var Async\GF_Notifications_Processor $processor
		 */
		$processor       = GFForms::get_service_container()->get( Async\GF_Background_Process_Service_Provider::NOTIFICATIONS );
		$is_asynchronous = $processor->is_enabled( $notifications_to_send, $form, $entry, $event, $data );

		if ( $is_asynchronous ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Adding %d notification(s) to the async processing queue for entry #%d.', count( $notifications_to_send ), $entry_id ) );

			$processor->push_to_queue( array(
				'notifications' => $notifications_to_send,
				'form_id'       => $form_id,
				'entry_id'      => $entry_id,
				'event'         => $event,
				'data'          => $data,
			) );
			$processor->save()->dispatch();
		} else {
			GFCommon::send_notifications( $notifications_to_send, $form, $entry, true, $event, $data );
		}

		return $notifications_to_send;
	}


	// PERMISSIONS ------------------------------------------------
	/**
	 * Checks the permissions for the current user. Returns true if the current user has any of the specified capabilities.
	 *
	 * IMPORTANT: Call this before calling any of the other API Functions as permission checks are not performed at lower levels.
	 *
	 * @since  1.8.5.10
	 * @access public
	 *
	 * @uses GFCommon::current_user_can_any()
	 *
	 * @param array|string $capabilities An array of capabilities, or a single capability
	 *
	 * @return bool Returns true if the current user has any of the specified capabilities
	 */
	public static function current_user_can_any( $capabilities ) {
		return GFCommon::current_user_can_any( $capabilities );
	}

	// FIELDS -----------------------------------------------------

	/**
	 * Returns an array containing the form fields of the specified type or types.
	 *
	 * @since  1.9.9.8
	 * @access public
	 *
	 * @param array        $form           The Form Object.
	 * @param array|string $types          The field types to get. Multiple field types as an array or a single type in a string.
	 * @param bool         $use_input_type Optional. Defaults to false.
	 *
	 * @uses GFFormsModel::get_fields_by_type()
	 *
	 * @return GF_Field[]
	 */
	public static function get_fields_by_type( $form, $types, $use_input_type = false ) {
		return GFFormsModel::get_fields_by_type( $form, $types, $use_input_type );
	}

	/**
	 * Returns the field object for the requested field or input ID from the supplied or specified form.
	 *
	 * @since  2.3
	 * @access public
	 *
	 * @param array|int  $form_or_id The Form Object or ID.
	 * @param string|int $field_id   The field or input ID.
	 *
	 * @uses   GFFormsModel::get_field()
	 *
	 * @return GF_Field|false
	 */
	public static function get_field( $form_or_id, $field_id ) {
		$field = GFFormsModel::get_field( $form_or_id, $field_id );

		return $field ? $field : false;
	}

	// HELPERS ----------------------------------------------------

	/**
	 * Checks whether a form ID exists.
	 *
	 * @since 1.8
	 * @since 2.4.24 Updated to use GFFormsModel::id_exists_in_table().
	 */
	public static function form_id_exists( $form_id ) {
		return GFFormsModel::id_exists_in_table( $form_id, GFFormsModel::get_form_table_name() );
	}

	/**
	 * Checks if an entry exists for the supplied ID.
	 *
	 * @since 2.4.6
	 *
	 * @param int $entry_id The ID to be checked.
	 *
	 * @return bool
	 */
	public static function entry_exists( $entry_id ) {
		return GFFormsModel::entry_exists( $entry_id );
	}

	/**
	 * Checks if a feed exists for the supplied ID.
	 *
	 * @since 2.4.24
	 *
	 * @param int $feed_id The ID to be checked.
	 *
	 * @return bool
	 */
	public static function feed_exists( $feed_id ) {
		return GFFormsModel::id_exists_in_table( $feed_id, GFFormsModel::get_addon_feed_table_name() );
	}

	/**
	 * Write an error message to the Gravity Forms API log.
	 *
	 * @since 2.4.11
	 *
	 * @param string $message The message to be logged.
	 */
	public static function log_error( $message ) {
		if ( class_exists( 'GFLogging' ) ) {
			GFLogging::include_logger();
			GFLogging::log_message( 'gravityformsapi', $message, KLogger::ERROR );
		}
	}

	/**
	 * Write a debug message to the Gravity Forms API log.
	 *
	 * @since 2.4.11
	 *
	 * @param string $message The message to be logged.
	 */
	public static function log_debug( $message ) {
		if ( class_exists( 'GFLogging' ) ) {
			GFLogging::include_logger();
			GFLogging::log_message( 'gravityformsapi', $message, KLogger::DEBUG );
		}
	}

	/**
	 * Make sure the form title is unique.
	 *
	 * @since 2.5
	 *
	 * @param string     $title
	 * @param int|string $form_id
	 *
	 * @return string
	 */
	public static function unique_title( $title, $form_id = '' ) {
		return GFFormsModel::maybe_increment_title( $title, $form_id );
	}

}
