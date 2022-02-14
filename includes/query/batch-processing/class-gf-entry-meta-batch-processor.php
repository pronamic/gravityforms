<?php

namespace Gravity_Forms\Gravity_Forms\Query\Batch_Processing;

class GF_Entry_Meta_Batch_Processor {

	/**
	 * Stores the entry meta information for batch processing.
	 *
	 * begin_batch_entry_meta_operations() should be called before adding anything to this array
	 * then commit_batch_entry_meta_operations() to save it.
	 *
	 * @since 2.5.16
	 *
	 * @var array
	 */
	private static $_batch_entry_meta_updates = array();

	/**
	 * Checks if any entry meta updates have been registered for entry meta batch processing.
	 *
	 * @since 2.5.16
	 *
	 * @return bool
	 */
	public static function has_batch_entry_meta_operations() {
		return ! empty( self::$_batch_entry_meta_updates );
	}

	/**
	 * Flushes the contents of the entry meta batch data to begin a new batch operation.
	 *
	 * @since 2.5.16
	 */
	public static function begin_batch_entry_meta_operations() {
		self::$_batch_entry_meta_updates = array();
	}

	/**
	 * Queues an item into the batch entry meta operations array.
	 *
	 * @since 2.5.16
	 *
	 * @param array   $form             The current form being processed.
	 * @param array   $entry            The current entry  being processed.
	 * @param string  $entry_meta_key   The entry meta key.
	 * @param mixed   $entry_meta_value The entry meta value.
	 *
	 * @return bool
	 */
	public static function queue_batch_entry_meta_operation( $form, $entry, $entry_meta_key, $entry_meta_value ) {

		$entry_id = rgar( $entry, 'id' );
		$form_id  = rgar( $form, 'id' );

		if ( ! $entry_id || ! $form_id || ! $entry_meta_key ) {
			return false;
		}

		self::$_batch_entry_meta_updates[] = array(
			'meta_value' => $entry_meta_value,
			'meta_key'   => $entry_meta_key,
			'form_id'    => $form_id,
			'entry_id'   => $entry_id,
		);

		return true;
	}

	/**
	 * Commits the contents of the entry meta batch data to the database.
	 *
	 * @since 2.5.16
	 *
	 * @return array An associative array that contains the results of the operation as the value and the operation as the key.
	 */
	public static function commit_batch_entry_meta_operations() {

		global $wpdb;
		$meta_table = \GFFormsModel::get_entry_meta_table_name();

		$results['updates'] = array();

		if ( empty( self::$_batch_entry_meta_updates ) ) {
			return $results;
		}

		$meta_keys                      = array_column( self::$_batch_entry_meta_updates, 'meta_key' );
		$prepare_statement_placeholders = '(' . implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) ) . ')';
		$sql                            = call_user_func_array(
			array( $wpdb, 'prepare' ),
			array_merge(
				array( "SELECT id, meta_key FROM {$meta_table} WHERE meta_key in " . $prepare_statement_placeholders ),
				$meta_keys
			)
		);
		$existing_rows                  = $wpdb->get_results( $sql, ARRAY_A );
		$update_rows                    = array();
		foreach ( $existing_rows as $row ) {
			$update_rows[ $row['meta_key'] ] = $row['id'];
		}

		$values = array();
		foreach ( self::$_batch_entry_meta_updates as $update ) {
			$update['meta_value'] = maybe_serialize( $update['meta_value'] );
			$values[]             = $wpdb->prepare( '(%s,%s,%s,%s,%s)', rgar( $update_rows, $update['meta_key'] ), $update['meta_key'], $update['meta_value'], $update['form_id'], $update['entry_id'] );
		}
		$values_str = join( ',', $values );

		$update_sql = "INSERT INTO {$meta_table} (id,meta_key,meta_value,form_id,entry_id)
					VALUES {$values_str}
					ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value);";

		$result = $wpdb->query( $update_sql );

		if ( $result === false ) {
			$result = new \WP_Error( 'update_error', $wpdb->last_error );
		}

		$results['updates']              = $result;
		self::$_batch_entry_meta_updates = array();

		return $results;
	}

	/**
	 * Returns the count of pending update operations.
	 *
	 * @since 2.5.16
	 *
	 * @return int
	 */
	public static function get_pending_operations_count() {
		return count( self::$_batch_entry_meta_updates );
	}
}
