<?php
/**
 * Background processor for personal data retention policies.
 *
 * @package Gravity_Forms\Gravity_Forms\Personal_Data
 * @since   3.1.1
 */

namespace Gravity_Forms\Gravity_Forms\Personal_Data;

use Gravity_Forms\Gravity_Forms\Async\GF_Background_Process;
use GFAPI;
use GFCommon;
use GFFormsModel;
use GF_Query;
use GF_Query_Column;
use GF_Query_Condition;
use GF_Query_Literal;
use GF_Query_Series;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

if ( ! class_exists( 'Gravity_Forms\Gravity_Forms\Async\GF_Background_Process' ) ) {
	require_once GF_PLUGIN_DIR_PATH . 'includes/async/class-gf-background-process.php';
}

/**
 * Class GF_Personal_Data_Processor
 *
 * Processes form entry retention (trash/delete) in background batches.
 *
 * @since 3.1.1
 */
class GF_Personal_Data_Processor extends GF_Background_Process {

	/**
	 * Action name used for the background process identifier.
	 *
	 * @since 3.1.1
	 *
	 * @var string
	 */
	protected $action = 'gf_personal_data';

	/**
	 * Singleton instance.
	 *
	 * @since 3.1.1
	 *
	 * @var GF_Personal_Data_Processor|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @since 3.1.1
	 *
	 * @return GF_Personal_Data_Processor
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Processes one retention batch for trash and delete, then re-queues if more work remains.
	 *
	 * Logic mirrors the former GF_Personal_Data::cron_task() implementation, with a
	 * batch size limit and self-chaining so large backlogs do not time out.
	 *
	 * @since 3.1.1
	 *
	 * @param mixed $item Queue item. May contain `skipped_ids` of entries excluded by gform_entry_ids_automatic_deletion.
	 *
	 * @return false Always false so this queue item is considered complete.
	 */
	protected function task( $item ) {

		/**
		 * Filters the number of entries processed per personal data retention background batch.
		 *
		 * @since 3.1.1
		 *
		 * @param int $batch_size Number of entries per batch. Default 1000.
		 */
		$batch_size = max( 1, (int) apply_filters( 'gform_personal_data_cron_batch_size', 1000 ) );

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Starting to process personal data batch. Batch size: %d', $batch_size ) );

		$form_ids = GFFormsModel::get_form_ids( null );
		$forms    = empty( $form_ids ) ? array() : GFFormsModel::get_form_meta_by_id( $form_ids );

		$trash_form_ids   = array();
		$trash_conditions = array();

		$delete_form_ids   = array();
		$delete_conditions = array();

		foreach ( $forms as $form ) {

			$retention_policy = rgars( $form, 'personalData/retention/policy', 'retain' );

			if ( $retention_policy === 'retain' ) {
				continue;
			}

			$form_conditions = array();

			$retention_days = rgars( $form, 'personalData/retention/retain_entries_days' );

			$delete_timestamp = time() - ( DAY_IN_SECONDS * $retention_days );

			$delete_date = gmdate( 'Y-m-d H:i:s', $delete_timestamp );

			$form_conditions[] = new GF_Query_Condition(
				new GF_Query_Column( 'date_created' ),
				GF_Query_Condition::LT,
				new GF_Query_Literal( $delete_date )
			);

			$form_conditions[] = new GF_Query_Condition(
				new GF_Query_Column( 'form_id' ),
				GF_Query_Condition::EQ,
				new GF_Query_Literal( $form['id'] )
			);

			if ( $retention_policy === 'trash' ) {
				$trash_form_ids[]   = $form['id'];
				$trash_conditions[] = call_user_func_array(
					array(
						'GF_Query_Condition',
						'_and',
					),
					$form_conditions
				);
			} elseif ( $retention_policy === 'delete' ) {
				$delete_form_ids[]   = $form['id'];
				$delete_conditions[] = call_user_func_array(
					array(
						'GF_Query_Condition',
						'_and',
					),
					$form_conditions
				);
			}
		}

		$more_to_process = false;
		$skipped_ids     = rgempty( 'skipped_ids', $item ) ? array() : $item['skipped_ids'];

		if ( ! empty( $trash_conditions ) ) {

			$query = new GF_Query();

			$all_trash_conditions = array();

			$all_trash_conditions[] = call_user_func_array( array( 'GF_Query_Condition', '_or' ), $trash_conditions );

			$all_trash_conditions[] = new GF_Query_Condition(
				new GF_Query_Column( 'status' ),
				GF_Query_Condition::NEQ,
				new GF_Query_Literal( 'trash' )
			);

			$all_trash_conditions = call_user_func_array( array( 'GF_Query_Condition', '_and' ), $all_trash_conditions );

			$entry_ids = $query->from( $trash_form_ids )->where( $all_trash_conditions )->limit( $batch_size )->get_ids();

			if ( count( $entry_ids ) === $batch_size ) {
				$more_to_process = true;
			}

			foreach ( $entry_ids as $entry_id ) {
				GFAPI::update_entry_property( $entry_id, 'status', 'trash' );
				GFCommon::log_debug( __METHOD__ . '(): Moving entry #' . $entry_id . ' to trash' );
			}
		}

		if ( ! empty( $delete_conditions ) ) {

			$query = new GF_Query();

			$all_delete_conditions = $this->get_delete_conditions( $delete_conditions, $skipped_ids );

			$entry_ids = $query->from( $delete_form_ids )->where( $all_delete_conditions )->limit( $batch_size )->get_ids();

			if ( count( $entry_ids ) === $batch_size ) {
				$more_to_process = true;
			}

			/**
			 * Allows the array of entry IDs to be modified before automatically deleting according to the
			 * personal data retention policy.
			 *
			 * @since 2.4
			 *
			 * @param int[] $entry_ids The array of entry IDs to delete.
			 */
			$filtered_ids = apply_filters( 'gform_entry_ids_automatic_deletion', $entry_ids );

			// Adding newly skipped ids to the main list of skipped ids.
			$skipped_ids = array_merge( $skipped_ids, array_diff( $entry_ids, $filtered_ids ) );

			foreach ( $filtered_ids as $entry_id ) {
				GFAPI::delete_entry( $entry_id );
			}
		}

		if ( $more_to_process ) {
			$this->push_to_queue( array( 'skipped_ids' => array_unique( $skipped_ids ) ) )->save();
		}

		return false;
	}

	/**
	 * Builds the combined query conditions for automatic deletion.
	 *
	 * ORs the per-form retention conditions and excludes entry IDs skipped by
	 * gform_entry_ids_automatic_deletion on earlier batches.
	 *
	 * @since 3.1.1
	 *
	 * @param GF_Query_Condition[] $delete_conditions Per-form delete conditions.
	 * @param int[]                $skipped_ids       Entry IDs to exclude from the query.
	 *
	 * @return GF_Query_Condition
	 */
	private function get_delete_conditions( $delete_conditions, $skipped_ids ) {
		$all_delete_conditions = array( call_user_func_array( array( 'GF_Query_Condition', '_or' ), $delete_conditions ) );

		if ( ! empty( $skipped_ids ) ) {
			$literals = array();
			foreach ( $skipped_ids as $id ) {
				$literals[] = new GF_Query_Literal( intval( $id ) );
			}

			$all_delete_conditions[] = new GF_Query_Condition(
				new GF_Query_Column( 'id' ),
				GF_Query_Condition::NIN,
				new GF_Query_Series( $literals )
			);
		}

		return call_user_func_array( array( 'GF_Query_Condition', '_and' ), $all_delete_conditions );
	}
}
