<?php
if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_REST_Feed_Properties_Controller extends GF_REST_Feeds_Controller {

	/**
	 * The base of this controller's route.
	 *
	 * @since 2.4.24
	 *
	 * @var string
	 */
	public $rest_base = 'feeds/(?P<feed_id>[\d]+)/properties';

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since 2.4.24
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_items' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( true ),
			),
		) );
	}

	/**
	 * Updates the specified feed with the given properties.
	 *
	 * @since 2.4.24
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_items( $request ) {
		$properties = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $properties ) ) {
			return $properties;
		}

		$result = $this->update_feed_properties( $request['feed_id'], $properties );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( __( 'Feed updated successfully', 'gravityforms' ), 200 );
	}

	/**
	 * Retrieves the properties from the request body.
	 *
	 * @since 2.4.24
	 *
	 * @param WP_REST_Request $request Request object
	 *
	 * @return WP_Error|array
	 */
	protected function prepare_item_for_database( $request ) {
		$properties = $request->get_json_params();
		if ( empty( $properties ) ) {
			return new WP_Error( 'missing_properties', __( 'Invalid JSON. Properties should be sent as key value pairs.', 'gravityforms' ), array( 'status' => 400 ) );
		}

		return $properties;
	}

	/**
	 * Get the query params for collections
	 *
	 * @since 2.4.24
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array();
	}

	/**
	 * Get the Feed schema, conforming to JSON Schema.
	 *
	 * @since 2.4.24
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'feed',
			'type'       => 'object',
			'properties' => array(
				'id'         => array(
					'description' => __( 'Unique identifier for the feed.', 'gravityforms' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'form_id'    => array(
					'description' => __( 'The Form ID for the feed.', 'gravityforms' ),
					'type'        => 'integer',
				),
				'is_active'  => array(
					'description' => __( 'Indicates if the feed is active or inactive.', 'gravityforms' ),
					'type'        => 'boolean',
				),
				'feed_order' => array(
					'description' => __( 'The position of the feed on the feeds list page and when processed; for add-ons which support feed ordering.', 'gravityforms' ),
					'type'        => 'integer',
				),
				'meta'       => array(
					'description' => __( 'The JSON string containing the feed meta.', 'gravityforms' ),
					'type'        => 'object',
				),
				'addon_slug' => array(
					'description' => __( 'The add-on the feed belongs to.', 'gravityforms' ),
					'type'        => 'string',
				),
			),
		);
	}

}
