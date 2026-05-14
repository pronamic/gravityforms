<?php
/**
 * Background processor for bulk entry actions.
 *
 * @package Gravity_Forms\Gravity_Forms\Bulk_Actions
 * @since   next
 */

namespace Gravity_Forms\Gravity_Forms\Bulk_Actions;

use Gravity_Forms\Gravity_Forms\Async\GF_Background_Process;
use GFCommon;
use GFFormsModel;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

if ( ! class_exists( 'Gravity_Forms\Gravity_Forms\Async\GF_Background_Process' ) ) {
	require_once GF_PLUGIN_DIR_PATH . 'includes/async/class-gf-background-process.php';
}

/**
 * Class GF_Entry_Bulk_Action_Processor
 *
 * Handles background processing of bulk entry actions.
 *
 * @since 2.10.2
 */
class GF_Entry_Bulk_Action_Processor extends GF_Background_Process {

	protected $action = 'gf_entry_bulk_action';

	private static $instance = null;

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	protected function task( $item ) {
		$entry_id    = rgar( $item, 'entry_id' );
		$form_id     = rgar( $item, 'form_id' );
		$bulk_action = rgar( $item, 'bulk_action' );

		if ( empty( $bulk_action ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Skipping task with missing bulk_action.' );
			$this->increment_processed();
			return false;
		}

		if ( $bulk_action === 'delete_form' ) {
			return $this->process_delete_form( $form_id );
		}

		if ( empty( $entry_id ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Skipping task with missing entry_id.' );
			$this->increment_processed();
			return false;
		}

		$entry = \GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Entry #%d not found, skipping.', $entry_id ) );
			$this->increment_processed();
			return false;
		}

		switch ( $bulk_action ) {
			case 'delete':
				GFFormsModel::delete_entry( $entry_id );
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Deleted entry #%d.', $entry_id ) );
				break;

			case 'trash':
				\GFAPI::update_entry_property( $entry_id, 'status', 'trash' );
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Trashed entry #%d.', $entry_id ) );
				break;

			case 'restore':
				\GFAPI::update_entry_property( $entry_id, 'status', 'active' );
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Restored entry #%d.', $entry_id ) );
				break;

			case 'spam':
				\GFAPI::update_entry_property( $entry_id, 'status', 'spam' );
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Marked entry #%d as spam.', $entry_id ) );
				break;

			case 'unspam':
				\GFAPI::update_entry_property( $entry_id, 'status', 'active' );
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Unmarked entry #%d as spam.', $entry_id ) );
				break;

			case 'mark_read':
				\GFAPI::update_entry_property( $entry_id, 'is_read', 1 );
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Marked entry #%d as read.', $entry_id ) );
				break;

			case 'mark_unread':
				\GFAPI::update_entry_property( $entry_id, 'is_read', 0 );
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Marked entry #%d as unread.', $entry_id ) );
				break;

			default:
				GFCommon::log_debug( __METHOD__ . sprintf( '(): Unknown action "%s" for entry #%d.', $bulk_action, $entry_id ) );
				break;
		}

		$this->increment_processed();

		return false;
	}

	private function process_delete_form( $form_id ) {
		if ( empty( $form_id ) ) {
			GFCommon::log_debug( __METHOD__ . '(): Skipping task with missing form_id.' );
			$this->increment_processed();
			return false;
		}

		$form = \GFAPI::get_form( $form_id );

		if ( ! $form ) {
			GFCommon::log_debug( __METHOD__ . sprintf( '(): Form #%d not found, skipping.', $form_id ) );
			$this->increment_processed();
			return false;
		}

		GFFormsModel::delete_form( $form_id );
		GFCommon::log_debug( __METHOD__ . sprintf( '(): Permanently deleted form #%d.', $form_id ) );

		$this->increment_processed();

		return false;
	}

	private function increment_processed() {
		$status = get_option( 'gf_bulk_action_status', array() );

		if ( ! empty( $status ) && rgar( $status, 'status' ) === 'processing' ) {
			$status['processed'] = rgar( $status, 'processed', 0 ) + 1;
			update_option( 'gf_bulk_action_status', $status );
		}
	}

	protected function complete() {
		parent::complete();

		$status = get_option( 'gf_bulk_action_status', array() );

		if ( ! empty( $status ) ) {
			$status['status']       = 'completed';
			$status['completed_at'] = time();
			update_option( 'gf_bulk_action_status', $status );
		}

		GFCommon::log_debug( __METHOD__ . '(): Bulk action process completed.' );
	}

	protected function get_action_for_log() {
		return ' for bulk entry action';
	}
}
