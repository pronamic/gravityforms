<?php
/**
 * AJAX endpoint for checking bulk action status.
 *
 * @package Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints
 * @since   next
 */

namespace Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints;

use GFCommon;
use Gravity_Forms\Gravity_Forms\Bulk_Actions\GF_Entry_Bulk_Action_Processor;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class GF_Bulk_Action_Endpoint_Status
 *
 * Handles AJAX requests to check bulk action progress.
 *
 * @since 2.10.2
 */
class GF_Bulk_Action_Endpoint_Status {

	const ACTION_NAME = 'gf_bulk_action_status';

	const STUCK_TIMEOUT = 1800; // 30 minutes

	public function handle() {
		check_ajax_referer( 'gf_bulk_action', 'nonce' );

		if ( ! GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gravityforms' ) ) );
		}

		$status = get_option( 'gf_bulk_action_status', array() );

		require_once GF_PLUGIN_DIR_PATH . 'includes/bulk-actions/class-gf-entry-bulk-action-processor.php';
		$processor = GF_Entry_Bulk_Action_Processor::get_instance();
		$has_queue = $processor->is_queued();

		if ( $this->is_stuck( $status, $processor ) ) {
			$processor->clear_queue();
			delete_option( 'gf_bulk_action_status' );
			wp_send_json_success( array( 'active' => false ) );
		}

		if ( empty( $status ) ) {
			if ( $has_queue ) {
				$processor->clear_queue();
			}

			wp_send_json_success( array(
				'active' => false,
			) );
		}

		$is_processing = rgar( $status, 'status' ) === 'processing';

		wp_send_json_success( array(
			'active'      => $is_processing || $has_queue,
			'pending'     => $has_queue && ! $processor->is_processing(),
			'cancelled'   => rgar( $status, 'status' ) === 'cancelled',
			'form_id'     => rgar( $status, 'form_id' ),
			'bulk_action' => rgar( $status, 'bulk_action' ),
			'action_type' => rgar( $status, 'action_type', 'entry' ),
			'total'       => rgar( $status, 'total', 0 ),
			'processed'   => rgar( $status, 'processed', 0 ),
			'status'      => rgar( $status, 'status' ),
			'origin_url'  => rgar( $status, 'origin_url' ),
			'origin_page' => rgar( $status, 'origin_page' ),
		) );
	}

	/**
	 * Checks if a bulk action is stuck.
	 *
	 * @since 2.10.2
	 *
	 * @param array                          $status    The bulk action status.
	 * @param GF_Entry_Bulk_Action_Processor $processor The processor instance.
	 *
	 * @return bool
	 */
	private function is_stuck( $status, $processor ) {
		if ( empty( $status ) || rgar( $status, 'status' ) !== 'processing' ) {
			return false;
		}

		if ( $processor->is_processing() ) {
			return false;
		}

		$started_at = rgar( $status, 'started_at', 0 );
		$elapsed    = time() - $started_at;

		return $elapsed > self::STUCK_TIMEOUT;
	}
}
