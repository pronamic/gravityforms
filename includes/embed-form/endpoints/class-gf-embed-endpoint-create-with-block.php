<?php

namespace Gravity_Forms\Gravity_Forms\Embed_Form\Endpoints;

/**
 * AJAX Endpoint for creating a new post with a specific block already added.
 *
 * @since   2.6
 *
 * @package Gravity_Forms\Gravity_Forms\Embed_Form\Endpoints
 */
class GF_Embed_Endpoint_Create_With_Block {

	// Strings
	const ACTION_NAME = 'gf_embed_create_post_with_block';

	// Request Params
	const PARAM_FORM_ID    = 'form_id';
	const PARAM_POST_TYPE  = 'post_type';
	const PARAM_POST_TITLE = 'post_title';

	/**
	 * Handle the AJAX request.
	 *
	 * @since 2.6
	 *
	 * @return void
	 */
	public function handle() {
		check_ajax_referer( self::ACTION_NAME );

		$post_type  = rgpost( self::PARAM_POST_TYPE );
		$form_id    = rgpost( self::PARAM_FORM_ID );
		$post_title = rgpost( self::PARAM_POST_TITLE );

		if ( empty( $post_type ) || empty( $form_id ) ) {
			wp_send_json_error( 'Request must include a post_type and form_id.', 400 );
		}

		$post_data = array(
			'post_title'   => $post_title,
			'post_type'    => $post_type,
			'post_content' => $this->get_content_for_form( $form_id ),
		);

		$new_id = wp_insert_post( $post_data );

		wp_send_json_success( array( 'ID' => $new_id ) );
	}

	/**
	 * Get the properly-formatted comment string for the block we're inserting.
	 *
	 * @since 2.6
	 *
	 * @param $form_id
	 *
	 * @return string
	 */
	private function get_content_for_form( $form_id ) {
		$attrs = array(
			'formId' => $form_id
		);

		return get_comment_delimited_block_content( 'gravityforms/form', $attrs, '' );
	}

}