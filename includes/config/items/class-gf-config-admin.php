<?php

namespace Gravity_Forms\Gravity_Forms\Config\Items;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Collection;
use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Configurator;

/**
 * Config items for Admin I18N
 *
 * @since 2.6
 */
class GF_Config_Admin extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Whether we should enqueue this data.
	 *
	 * @since 2.6
	 *
	 * @return bool|mixed
	 */
	public function should_enqueue() {
		return is_admin();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		$data = array(
			'data' => array(
				'is_block_editor' => \GFCommon::is_block_editor_page(),
				'gf_page'         => \GFForms::get_page(),
			),
			'i18n' => array(
				'form_admin'   => array(
					'toggle_feed_inactive' => esc_html__( 'Inactive', 'gravityforms' ),
					'toggle_feed_active'   => esc_html__( 'Active', 'gravityforms' ),
				),
				'shortcode_ui' => array(
					'edit_form'   => esc_html__( 'Edit Form', 'gravityforms' ),
					'insert_form' => esc_html__( 'Insert Form', 'gravityforms' ),
				),
			),
			'bulk_actions' => $this->get_bulk_action_config(),
		);

		$data = $this->add_entry_list_data( $data );

		return $data;
	}

	private function get_bulk_action_config() {
		return array(
			'nonce'              => wp_create_nonce( 'gf_bulk_action' ),
			'threshold'          => \Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints\GF_Bulk_Action_Endpoint_Start::get_background_threshold(),
			'backgroundActions'  => \Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints\GF_Bulk_Action_Endpoint_Start::BACKGROUND_ACTIONS,
			'pollInterval'       => 1500,
			'labels' => array(
				'delete' => array(
					'title'    => esc_html__( 'Bulk Delete', 'gravityforms' ),
					'starting' => esc_html__( 'Starting bulk delete...', 'gravityforms' ),
					'progress' => esc_html__( 'Deleting Gravity Forms entries...', 'gravityforms' ),
					'complete' => esc_html__( 'Deletion complete!', 'gravityforms' ),
					'error'    => esc_html__( 'Error starting bulk delete.', 'gravityforms' ),
				),
				'trash'  => array(
					'title'    => esc_html__( 'Bulk Trash', 'gravityforms' ),
					'starting' => esc_html__( 'Starting bulk trash...', 'gravityforms' ),
					'progress' => esc_html__( 'Trashing Gravity Forms entries...', 'gravityforms' ),
					'complete' => esc_html__( 'Entries moved to trash!', 'gravityforms' ),
					'error'    => esc_html__( 'Error starting bulk trash.', 'gravityforms' ),
				),
				'spam'   => array(
					'title'    => esc_html__( 'Bulk Mark as Spam', 'gravityforms' ),
					'starting' => esc_html__( 'Starting bulk spam marking...', 'gravityforms' ),
					'progress' => esc_html__( 'Marking Gravity Forms entries as spam...', 'gravityforms' ),
					'complete' => esc_html__( 'Entries marked as spam!', 'gravityforms' ),
					'error'    => esc_html__( 'Error starting bulk spam.', 'gravityforms' ),
				),
				'restore' => array(
					'title'    => esc_html__( 'Bulk Restore', 'gravityforms' ),
					'starting' => esc_html__( 'Starting bulk restore...', 'gravityforms' ),
					'progress' => esc_html__( 'Restoring Gravity Forms entries...', 'gravityforms' ),
					'complete' => esc_html__( 'Entries restored!', 'gravityforms' ),
					'error'    => esc_html__( 'Error starting bulk restore.', 'gravityforms' ),
				),
				'unspam' => array(
					'title'    => esc_html__( 'Bulk Unmark as Spam', 'gravityforms' ),
					'starting' => esc_html__( 'Starting bulk unspam...', 'gravityforms' ),
					'progress' => esc_html__( 'Restoring Gravity Forms entries from spam...', 'gravityforms' ),
					'complete' => esc_html__( 'Entries unmarked as spam!', 'gravityforms' ),
					'error'    => esc_html__( 'Error starting bulk unspam.', 'gravityforms' ),
				),
				'delete_entries' => array(
					'title'    => esc_html__( 'Bulk Delete Entries', 'gravityforms' ),
					'starting' => esc_html__( 'Starting bulk entry deletion...', 'gravityforms' ),
					'progress' => esc_html__( 'Deleting Gravity Forms entries...', 'gravityforms' ),
					'complete' => esc_html__( 'Entry deletion complete!', 'gravityforms' ),
					'error'    => esc_html__( 'Error starting bulk entry deletion.', 'gravityforms' ),
				),
				'mark_read' => array(
					'title'    => esc_html__( 'Bulk Mark as Read', 'gravityforms' ),
					'starting' => esc_html__( 'Starting bulk mark as read...', 'gravityforms' ),
					'progress' => esc_html__( 'Marking Gravity Forms entries as read...', 'gravityforms' ),
					'complete' => esc_html__( 'Entries marked as read!', 'gravityforms' ),
					'error'    => esc_html__( 'Error starting bulk mark as read.', 'gravityforms' ),
				),
				'mark_unread' => array(
					'title'    => esc_html__( 'Bulk Mark as Unread', 'gravityforms' ),
					'starting' => esc_html__( 'Starting bulk mark as unread...', 'gravityforms' ),
					'progress' => esc_html__( 'Marking Gravity Forms entries as unread...', 'gravityforms' ),
					'complete' => esc_html__( 'Entries marked as unread!', 'gravityforms' ),
					'error'    => esc_html__( 'Error starting bulk mark as unread.', 'gravityforms' ),
				),
			),
			'messages' => array(
				'inProgress'           => esc_html__( 'Bulk action in progress...', 'gravityforms' ),
				'continueInBackground' => esc_html__( 'Continue in Background', 'gravityforms' ),
				'cancel'               => esc_html__( 'Cancel', 'gravityforms' ),
				'cancelling'           => esc_html__( 'Cancelling...', 'gravityforms' ),
				'operationCancelled'   => esc_html__( 'Operation cancelled.', 'gravityforms' ),
				'viewDetails'          => esc_html__( 'View Details', 'gravityforms' ),
				'close'                => esc_html__( 'Close', 'gravityforms' ),
				'processedCount'       => esc_html__( '%1$d of %2$d items processed.', 'gravityforms' ),
				'goToPage'             => esc_html__( 'Go to %s', 'gravityforms' ),
			),
			'pageLabels' => array(
				'entry_list' => esc_html__( 'Entries', 'gravityforms' ),
				'form_list'  => esc_html__( 'Forms', 'gravityforms' ),
			),
			'entryActions'  => \Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints\GF_Bulk_Action_Endpoint_Start::BACKGROUND_ACTIONS_ENTRY,
			'formActions'   => \Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints\GF_Bulk_Action_Endpoint_Start::BACKGROUND_ACTIONS_FORM,
		);
	}

	private function add_entry_list_data( $data ) {
		if ( \GFForms::get_page() !== 'entry_list' ) {
			return $data;
		}

		$form_id = absint( rgget( 'id' ) );
		if ( ! $form_id ) {
			return $data;
		}

		$data['form_id'] = $form_id;

		$filter          = rgget( 'filter' );
		$search          = rgget( 's' );
		$field_id        = rgget( 'field_id' );
		$operator        = rgget( 'operator' );
		$search_criteria = array();

		if ( $filter === 'trash' || $filter === 'spam' ) {
			$search_criteria['status'] = $filter;
		} elseif ( $filter === 'star' ) {
			$search_criteria['field_filters'][] = array( 'key' => 'is_starred', 'value' => true );
		} elseif ( $filter === 'unread' ) {
			$search_criteria['field_filters'][] = array( 'key' => 'is_read', 'value' => false );
		} else {
			$search_criteria['status'] = 'active';
		}

		if ( $search && $field_id ) {
			$search_criteria['field_filters'][] = array(
				'key'      => $field_id,
				'value'    => $search,
				'operator' => $operator ? $operator : 'contains',
			);
		}

		$data['search_criteria'] = $search_criteria;

		return $data;
	}
}
