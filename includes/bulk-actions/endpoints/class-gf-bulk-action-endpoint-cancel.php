<?php
/**
 * AJAX endpoint for cancelling bulk actions.
 *
 * @package Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints
 * @since 2.10.3
 */

namespace Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints;

use GFCommon;
use Gravity_Forms\Gravity_Forms\Bulk_Actions\GF_Entry_Bulk_Action_Processor;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class GF_Bulk_Action_Endpoint_Cancel
 *
 * Handles AJAX requests to cancel bulk actions.
 *
 * @since 2.10.2
 */
class GF_Bulk_Action_Endpoint_Cancel {

	const ACTION_NAME = 'gf_bulk_action_cancel';

	public function handle() {
		check_ajax_referer( 'gf_bulk_action', 'nonce' );

		$status = get_option( 'gf_bulk_action_status', array() );

		if ( ! $this->current_user_can_manage_status( $status ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gravityforms' ) ) );
		}

		if ( empty( $status ) || rgar( $status, 'status' ) !== 'processing' ) {
			wp_send_json_error( array( 'message' => __( 'No active bulk operation to cancel.', 'gravityforms' ) ) );
		}

		$bulk_action = rgar( $status, 'bulk_action' );

		require_once GF_PLUGIN_DIR_PATH . 'includes/bulk-actions/class-gf-entry-bulk-action-processor.php';
		$processor = GF_Entry_Bulk_Action_Processor::get_instance();
		$processor->cancel();
		$processor->clear_queue();

		delete_option( 'gf_bulk_action_status' );

		GFCommon::log_debug( __METHOD__ . sprintf( '(): Cancelled bulk %s operation.', $bulk_action ) );

		wp_send_json_success(
			array(
				'message'   => __( 'Bulk operation cancelled.', 'gravityforms' ),
				'processed' => rgar( $status, 'processed', 0 ),
				'total'     => rgar( $status, 'total', 0 ),
			)
		);
	}

	/**
	 * Determines if the current user can cancel the current background action.
	 *
	 * @since 3.0.3
	 *
	 * @param array $status The stored bulk action status.
	 *
	 * @return bool
	 */
	protected function current_user_can_manage_status( $status ) {
		if ( empty( $status ) ) {
			return GFCommon::current_user_can_any( array( 'gravityforms_delete_entries', 'gravityforms_delete_forms', 'gravityforms_edit_entries' ) );
		}

		$capability = GF_Bulk_Action_Endpoint_Start::get_required_capability( rgar( $status, 'action_type', 'entry' ), rgar( $status, 'bulk_action' ) );

		return $capability && GFCommon::current_user_can_any( $capability );
	}
}
