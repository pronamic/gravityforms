<?php

namespace Gravity_Forms\Gravity_Forms\Embed_Form\Endpoints;

/**
 * AJAX Endpoint for getting posts based on search params.
 *
 * @since   2.6
 *
 * @package Gravity_Forms\Gravity_Forms\Embed_Form\Endpoints
 */
class GF_Embed_Endpoint_Get_Posts {

	// Strings
	const ACTION_NAME = 'gf_embed_query_posts';

	// Parameters
	const PARAM_OFFSET    = 'offset';
	const PARAM_COUNT     = 'count';
	const PARAM_STATUS    = 'status';
	const PARAM_SEARCH    = 'search';
	const PARAM_POST_TYPE = 'post_type';

	// Defaults
	const DEFAULT_OFFSET    = 0;
	const DEFAULT_COUNT     = 10;
	const DEFAULT_STATUS    = 'publish';
	const DEFAULT_SEARCH    = '';
	const DEFAULT_POST_TYPE = 'post';

	/**
	 * Handle the AJAX request.
	 *
	 * @since 2.6
	 *
	 * @return void
	 */
	public function handle() {
		check_ajax_referer( self::ACTION_NAME );

		$offset    = rgpost( self::PARAM_OFFSET ) ? rgpost( self::PARAM_OFFSET ) : self::DEFAULT_OFFSET;
		$count     = rgpost( self::PARAM_COUNT ) ? rgpost( self::PARAM_COUNT ) : self::DEFAULT_COUNT;
		$status    = rgpost( self::PARAM_STATUS ) ? rgpost( self::PARAM_STATUS ) : self::DEFAULT_STATUS;
		$search    = rgpost( self::PARAM_SEARCH ) ? rgpost( self::PARAM_SEARCH ) : self::DEFAULT_SEARCH;
		$post_type = rgpost( self::PARAM_POST_TYPE ) ? rgpost( self::PARAM_POST_TYPE ) : self::DEFAULT_POST_TYPE;

		$args = array(
			'post_type'      => $post_type,
			'post_status'    => $status,
			'posts_per_page' => $count,
			'offset'         => $offset,
			's'              => $search,
		);

		$query = new \WP_Query( $args );

		$posts = $query->get_posts();

		array_walk( $posts, function ( &$post ) {
			$post = array(
				'value' => $post->ID,
				'label' => $post->post_title,
			);
		} );

		wp_send_json_success( $posts );
	}

}
