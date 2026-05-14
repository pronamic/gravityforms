<?php
/**
 * AJAX endpoint for starting bulk actions.
 *
 * @package Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints
 * @since   next
 */

namespace Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints;

use GFCommon;
use GFAPI;
use Gravity_Forms\Gravity_Forms\Bulk_Actions\GF_Entry_Bulk_Action_Processor;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class GF_Bulk_Action_Endpoint_Start
 *
 * Handles AJAX requests to start bulk actions.
 *
 * @since 2.10.2
 */
class GF_Bulk_Action_Endpoint_Start {

	const ACTION_NAME = 'gf_start_bulk_action';

	const BACKGROUND_THRESHOLD = 100;

	const BACKGROUND_ACTIONS_ENTRY = array( 'delete', 'trash', 'spam', 'restore', 'unspam', 'mark_read', 'mark_unread' );

	const BACKGROUND_ACTIONS_FORM = array( 'delete_entries', 'delete' );

	const BACKGROUND_ACTIONS = array( 'delete', 'trash', 'spam', 'restore', 'unspam', 'delete_entries', 'mark_read', 'mark_unread' );

	/**
	 * Gets the background processing threshold.
	 *
	 * @since 2.10.2
	 *
	 * @return int The threshold for background processing.
	 */
	public static function get_background_threshold() {
		/**
		 * Filters the threshold for triggering background processing of bulk actions.
		 *
		 * When the number of entries exceeds this threshold, bulk actions will be
		 * processed in the background instead of synchronously.
		 *
		 * @since 2.10.2
		 *
		 * @param int $threshold The threshold value. Default 100.
		 */
		return apply_filters( 'gform_bulk_action_threshold', self::BACKGROUND_THRESHOLD );
	}

	public function handle() {
		check_ajax_referer( 'gf_bulk_action', 'nonce' );

		if ( ! GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gravityforms' ) ) );
		}

		$bulk_action = sanitize_text_field( rgpost( 'bulk_action' ) );
		$action_type = sanitize_text_field( rgpost( 'action_type' ) );
		$origin_page = sanitize_text_field( rgpost( 'origin_page' ) );

		if ( ! in_array( $bulk_action, self::BACKGROUND_ACTIONS, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action.', 'gravityforms' ) ) );
		}

		$status = get_option( 'gf_bulk_action_status', array() );
		if ( ! empty( $status ) && rgar( $status, 'status' ) === 'processing' ) {
			wp_send_json_error( array( 'message' => __( 'A bulk operation is already in progress. Please wait for it to complete.', 'gravityforms' ) ) );
		}

		if ( $action_type === 'form' ) {
			$this->handle_form_action( $bulk_action, $origin_page );
		} else {
			$this->handle_entry_action( $bulk_action, $origin_page );
		}
	}

	private function handle_entry_action( $bulk_action, $origin_page ) {
		$form_id    = absint( rgpost( 'form_id' ) );
		$entry_ids  = rgpost( 'entry_ids' );
		$select_all = rgpost( 'select_all' ) === 'true';

		if ( empty( $form_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'gravityforms' ) ) );
		}

		if ( $select_all ) {
			$search_criteria = rgpost( 'search_criteria' );
			if ( is_string( $search_criteria ) ) {
				$search_criteria = json_decode( stripslashes( $search_criteria ), true );
			}
			$entry_ids = GFAPI::get_entry_ids( $form_id, $search_criteria );
		} elseif ( is_string( $entry_ids ) ) {
			$entry_ids = json_decode( stripslashes( $entry_ids ), true );
		}

		if ( empty( $entry_ids ) || ! is_array( $entry_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No entries selected.', 'gravityforms' ) ) );
		}

		$total = count( $entry_ids );

		$status = array(
			'form_id'      => $form_id,
			'bulk_action'  => $bulk_action,
			'action_type'  => 'entry',
			'total'        => $total,
			'processed'    => 0,
			'status'       => 'processing',
			'started_at'   => time(),
			'completed_at' => null,
			'origin_url'   => 'admin.php?page=gf_entries&id=' . $form_id,
			'origin_page'  => $origin_page ?: 'entry_list',
		);
		update_option( 'gf_bulk_action_status', $status );

		require_once GF_PLUGIN_DIR_PATH . 'includes/bulk-actions/class-gf-entry-bulk-action-processor.php';
		$processor = GF_Entry_Bulk_Action_Processor::get_instance();
		$this->reset_processor_if_cancelled( $processor );

		$chunks = array_chunk( $entry_ids, self::get_background_threshold() );

		foreach ( $chunks as $chunk ) {
			foreach ( $chunk as $entry_id ) {
				$processor->push_to_queue( array(
					'entry_id'    => absint( $entry_id ),
					'form_id'     => $form_id,
					'bulk_action' => $bulk_action,
				) );
			}
			$processor->save();
		}

		$processor->dispatch();

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Started bulk %s for form #%d with %d entries.', $bulk_action, $form_id, $total ) );

		wp_send_json_success( array(
			'total'       => $total,
			'bulk_action' => $bulk_action,
			'action_type' => 'entry',
		) );
	}

	private function handle_form_action( $bulk_action, $origin_page ) {
		$form_ids = rgpost( 'form_ids' );

		if ( is_string( $form_ids ) ) {
			$form_ids = json_decode( stripslashes( $form_ids ), true );
		}

		if ( empty( $form_ids ) || ! is_array( $form_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No forms selected.', 'gravityforms' ) ) );
		}

		switch ( $bulk_action ) {
			case 'delete_entries':
				$this->handle_delete_entries_action( $form_ids, $origin_page );
				break;

			case 'delete':
				$this->handle_delete_forms_action( $form_ids, $origin_page );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Invalid action.', 'gravityforms' ) ) );
		}
	}

	private function handle_delete_entries_action( $form_ids, $origin_page ) {
		$all_entry_ids = array();

		foreach ( $form_ids as $form_id ) {
			$entry_ids = GFAPI::get_entry_ids( absint( $form_id ) );
			if ( ! empty( $entry_ids ) ) {
				foreach ( $entry_ids as $entry_id ) {
					$all_entry_ids[] = array(
						'entry_id' => absint( $entry_id ),
						'form_id'  => absint( $form_id ),
					);
				}
			}
		}

		if ( empty( $all_entry_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No entries found in the selected forms.', 'gravityforms' ) ) );
		}

		$total = count( $all_entry_ids );

		if ( $total <= self::get_background_threshold() ) {
			wp_send_json_success( array(
				'below_threshold' => true,
				'total'           => $total,
			) );
		}

		$status = array(
			'form_id'      => null,
			'form_ids'     => $form_ids,
			'bulk_action'  => 'delete_entries',
			'action_type'  => 'form',
			'total'        => $total,
			'processed'    => 0,
			'status'       => 'processing',
			'started_at'   => time(),
			'completed_at' => null,
			'origin_url'   => 'admin.php?page=gf_edit_forms',
			'origin_page'  => $origin_page ?: 'form_list',
		);
		update_option( 'gf_bulk_action_status', $status );

		require_once GF_PLUGIN_DIR_PATH . 'includes/bulk-actions/class-gf-entry-bulk-action-processor.php';
		$processor = GF_Entry_Bulk_Action_Processor::get_instance();
		$this->reset_processor_if_cancelled( $processor );

		$chunks = array_chunk( $all_entry_ids, self::get_background_threshold() );

		foreach ( $chunks as $chunk ) {
			foreach ( $chunk as $entry_data ) {
				$processor->push_to_queue( array(
					'entry_id'    => $entry_data['entry_id'],
					'form_id'     => $entry_data['form_id'],
					'bulk_action' => 'delete',
				) );
			}
			$processor->save();
		}

		$processor->dispatch();

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Started bulk delete_entries for %d forms (%d entries).', count( $form_ids ), $total ) );

		wp_send_json_success( array(
			'total'       => $total,
			'bulk_action' => 'delete_entries',
			'action_type' => 'form',
		) );
	}

	private function handle_delete_forms_action( $form_ids, $origin_page ) {
		$all_entry_ids = array();
		$form_ids_int  = array();

		foreach ( $form_ids as $form_id ) {
			$form_id_int    = absint( $form_id );
			$form_ids_int[] = $form_id_int;
			$entry_ids      = GFAPI::get_entry_ids( $form_id_int );

			if ( ! empty( $entry_ids ) ) {
				foreach ( $entry_ids as $entry_id ) {
					$all_entry_ids[] = array(
						'entry_id' => absint( $entry_id ),
						'form_id'  => $form_id_int,
					);
				}
			}
		}

		$entry_count = count( $all_entry_ids );
		$form_count  = count( $form_ids_int );
		$total       = $entry_count + $form_count;

		if ( $entry_count <= self::get_background_threshold() ) {
			wp_send_json_success( array(
				'below_threshold' => true,
				'total'           => $entry_count,
			) );
		}

		$status = array(
			'form_id'      => null,
			'form_ids'     => $form_ids_int,
			'bulk_action'  => 'delete',
			'action_type'  => 'form',
			'total'        => $total,
			'processed'    => 0,
			'status'       => 'processing',
			'started_at'   => time(),
			'completed_at' => null,
			'origin_url'   => 'admin.php?page=gf_edit_forms',
			'origin_page'  => $origin_page ?: 'form_list',
		);
		update_option( 'gf_bulk_action_status', $status );

		require_once GF_PLUGIN_DIR_PATH . 'includes/bulk-actions/class-gf-entry-bulk-action-processor.php';
		$processor = GF_Entry_Bulk_Action_Processor::get_instance();
		$this->reset_processor_if_cancelled( $processor );

		$chunks = array_chunk( $all_entry_ids, self::get_background_threshold() );

		foreach ( $chunks as $chunk ) {
			foreach ( $chunk as $entry_data ) {
				$processor->push_to_queue( array(
					'entry_id'    => $entry_data['entry_id'],
					'form_id'     => $entry_data['form_id'],
					'bulk_action' => 'delete',
				) );
			}
			$processor->save();
		}

		foreach ( $form_ids_int as $form_id ) {
			$processor->push_to_queue( array(
				'form_id'     => $form_id,
				'bulk_action' => 'delete_form',
			) );
		}
		$processor->save();

		$processor->dispatch();

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Started bulk form delete for %d forms (%d entries).', $form_count, $entry_count ) );

		wp_send_json_success( array(
			'total'       => $total,
			'bulk_action' => 'delete',
			'action_type' => 'form',
		) );
	}

	/**
	 * Resets the processor if it was previously cancelled.
	 *
	 * @since 2.10.2
	 *
	 * @param GF_Entry_Bulk_Action_Processor $processor The processor instance.
	 */
	private function reset_processor_if_cancelled( $processor ) {
		if ( $processor->is_cancelled() ) {
			$processor->clear_queue();
			$processor->resume( false );
		}
	}
}
