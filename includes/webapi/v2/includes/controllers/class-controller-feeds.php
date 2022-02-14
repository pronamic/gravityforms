<?php
if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_REST_Feeds_Controller extends GF_REST_Form_Feeds_Controller {

	/**
	 * @since 2.4
	 *
	 * @var string
	 */
	public $rest_base = 'feeds';

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since 2.4
	 *
	 */
	public function register_routes() {

		$namespace = $this->namespace;

		$base = $this->rest_base;

		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
		) );

		register_rest_route( $namespace, '/' . $base . '/(?P<feed_id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(),
			),
			array(
				'methods'             => 'PUT,PATCH',
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( false ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(),
			),
		) );

	}

	/**
	 * Get a collection of feeds.
	 *
	 * @since 2.4
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$feed_ids = $request['include'];

		if ( ! empty( $feed_ids ) ) {
			if ( ! is_array( $feed_ids ) ) {
				$feed_ids = array( $feed_ids );
			}
			$feed_ids = array_map( 'absint', $feed_ids );
		}

		$addon_slug = $request['addon'];

		$feeds = GFAPI::get_feeds( $feed_ids, null, $addon_slug );

		return new WP_REST_Response( $feeds, 200 );
	}

	/**
	 * Get one item from the collection
	 *
	 * @since 2.4
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		$feed_id = $request->get_param( 'feed_id' );

		$feed = GFAPI::get_feed( $feed_id );

		if ( is_wp_error( $feed ) ) {
			return new WP_Error( 'gf_feed_invalid_id', __( 'Invalid feed id.', 'gravityforms' ), array( 'status' => 404 ) );
		}

		return $this->prepare_item_for_response( $feed, $request );
	}

	/**
	 * Create one item from the collection
	 *
	 * @since 2.4
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Request
	 */
	public function create_item( $request ) {
		return parent::create_item( $request );
	}

	/**
	 * Update one item from the collection
	 *
	 * @since 2.4
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		if ( ! GFAPI::feed_exists( $request['feed_id'] ) ) {
			return new WP_Error( 'gf_feed_invalid_id', __( 'Invalid feed id.', 'gravityforms' ), array( 'status' => 404 ) );
		}

		$properties = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $properties ) ) {
			return $properties;
		}

		unset( $properties['id'] );

		$result = $this->update_feed_properties( $request['feed_id'], $properties );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->prepare_item_for_response( GFAPI::get_feed( $request['feed_id'] ), $request );
	}

	/**
	 * Prepares the item for the update operation.
	 *
	 * @since 2.4.24
	 *
	 * @param WP_REST_Request $request Request object
	 *
	 * @return WP_Error|array
	 */
	protected function prepare_item_for_database( $request ) {
		if ( $request->get_method() !== 'PATCH' ) {
			return parent::prepare_item_for_database( $request );
		}

		$properties = $request->get_json_params();

		if ( empty( $properties ) ) {
			return new WP_Error( 'missing_properties', __( 'Invalid JSON. Properties should be sent as key value pairs.', 'gravityforms' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $properties['meta'] ) ) {
			$feed               = GFAPI::get_feed( $request['feed_id'] );
			$properties['meta'] = $this->patch_array_recursive( $feed['meta'], $properties['meta'] );
		}

		return $properties;
	}

	/**
	 * Updates the specified feed with the given property values.
	 *
	 * @since 2.4.24
	 *
	 * @param int   $feed_id    The ID of the feed being updated.
	 * @param array $properties The feed properties being updated.
	 *
	 * @return bool|WP_Error
	 */
	protected function update_feed_properties( $feed_id, $properties ) {
		foreach ( $properties as $key => $value ) {
			$result = GFAPI::update_feed_property( $feed_id, $key, $value );
			if ( is_wp_error( $result ) ) {
				return new WP_Error(
					$result->get_error_code(),
					$result->get_error_message(),
					array( 'status' => $this->get_error_status( $result ) )
				);
			}
		}

		return true;
	}

	/**
	 * Delete one item from the collection
	 *
	 * @since 2.4
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$feed_id = $request['feed_id'];

		$feed = GFAPI::get_feed( $feed_id );
		if ( is_wp_error( $feed ) ) {
			return new WP_Error( 'gf_feed_invalid_id', __( 'Invalid feed id.', 'gravityforms' ), array( 'status' => 404 ) );
		}

		$result = GFAPI::delete_feed( $feed_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$previous = $this->prepare_item_for_response( $feed, $request );
		$response = new WP_REST_Response();
		$response->set_data( array( 'deleted' => true, 'previous' => $previous->get_data() ) );

		return $response;
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @since 2.4
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {

		/**
		 * Filters the capability required to get feeds via the REST API.
		 *
		 * @since 2.4
		 *
		 * @param string|array    $capability The capability required for this endpoint.
		 * @param WP_REST_Request $request    Full data about the request.
		 */
		$capability = apply_filters( 'gform_rest_api_capability_get_feeds', 'gravityforms_edit_forms', $request );

		return $this->current_user_can_any( $capability, $request );
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @since 2.4
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @since 2.4
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {

		/**
		 * Filters the capability required to create feeds via the REST API.
		 *
		 * @since 2.4
		 *
		 * @param string|array    $capability The capability required for this endpoint.
		 * @param WP_REST_Request $request    Full data about the request.
		 */
		$capability = apply_filters( 'gform_rest_api_capability_post_feeds', 'gravityforms_edit_forms', $request );

		return $this->current_user_can_any( $capability, $request );
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @since 2.4
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {

		/**
		 * Filters the capability required to update feeds via the REST API.
		 *
		 * @since 2.4
		 *
		 * @param string|array    $capability The capability required for this endpoint.
		 * @param WP_REST_Request $request    Full data about the request.
		 */
		$capability = apply_filters( 'gform_rest_api_capability_put_feeds', 'gravityforms_edit_forms', $request );

		return $this->current_user_can_any( $capability, $request );
	}

	/**
	 * Check if a given request has access to delete a specific item
	 *
	 * @since 2.4
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function delete_item_permissions_check( $request ) {

		/**
		 * Filters the capability required to delete feeds via the REST API.
		 *
		 * @since 2.4
		 *
		 * @param string|array    $capability The capability required for this endpoint.
		 * @param WP_REST_Request $request    Full data about the request.
		 */
		$capability = apply_filters( 'gform_rest_api_capability_delete_feeds', 'gravityforms_edit_forms', $request );

		return $this->current_user_can_any( $capability, $request );
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @since 2.4
	 *
	 * @param mixed           $item    WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response Returns the item wrapped in a WP_REST_Response object
	 */
	public function prepare_item_for_response( $item, $request ) {

		$response = new WP_REST_Response( $item, 200 );
		return $response;
	}

}
