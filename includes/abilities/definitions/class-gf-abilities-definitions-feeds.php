<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Defines feed abilities.
 *
 * @since 3.1.0
 */
class GF_Abilities_Definitions_Feeds {

	/**
	 * Get feed ability definitions.
	 *
	 * @since 3.1.0
	 *
	 * @return array[]
	 */
	public static function get_definitions() {
		return array(
			array(
				'name' => 'gravityforms/feeds-list',
				'args' => array(
					'label'            => __( 'List Feeds', 'gravityforms' ),
					'description'      => __( 'Returns feeds for a form, optionally filtered by add-on slug and active status. Feeds represent integrations (e.g., email marketing, payment processing) connected to a form.', 'gravityforms' ),
					'summary'          => __( 'See a form\'s connected integrations (email, payment, etc.).', 'gravityforms' ),
					'category'         => 'gravityforms-feeds',
					'execute_callback' => array( GF_Abilities_Handler_Feeds::class, 'list_feeds' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'default'              => (object) array(),
						'properties'           => array(
							'form_id'    => array( 'type' => 'integer', 'description' => __( 'Filter feeds by form ID.', 'gravityforms' ) ),
							'addon_slug' => array( 'type' => 'string', 'description' => __( 'Filter feeds by add-on slug (e.g., \'gravityformsmailchimp\').', 'gravityforms' ) ),
							'is_active'  => array( 'type' => 'boolean', 'description' => __( 'Filter by active status. Defaults to true.', 'gravityforms' ), 'default' => true ),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'id'         => array( 'type' => 'string' ),
								'form_id'    => array( 'type' => 'string' ),
								'addon_slug' => array( 'type' => 'string' ),
								'is_active'  => array( 'type' => 'string' ),
								'meta'       => array( 'type' => 'object', 'description' => __( 'Feed configuration settings (add-on specific).', 'gravityforms' ) ),
							),
						),
					),
					'readonly'         => true,
					'idempotent'       => true,
				),
			),
			array(
				'name' => 'gravityforms/feeds-create',
				'args' => array(
					'label'            => __( 'Create Feed', 'gravityforms' ),
					'description'      => __( 'Creates a new feed (add-on integration) for a form. Requires the form ID, feed configuration meta, and the add-on slug.', 'gravityforms' ),
					'summary'          => __( 'Connect a new integration to a form.', 'gravityforms' ),
					'category'         => 'gravityforms-feeds',
					'execute_callback' => array( GF_Abilities_Handler_Feeds::class, 'create_feed' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'form_id', 'feed_meta', 'addon_slug' ),
						'properties'           => array(
							'form_id'    => array( 'type' => 'integer', 'description' => __( 'The ID of the form to attach the feed to.', 'gravityforms' ) ),
							'feed_meta'  => array( 'type' => 'object', 'description' => __( 'Feed configuration (add-on specific).', 'gravityforms' ) ),
							'addon_slug' => array( 'type' => 'string', 'description' => __( 'The slug of the add-on this feed belongs to.', 'gravityforms' ) ),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'feed_id' => array( 'type' => 'integer', 'description' => __( 'The ID of the newly created feed.', 'gravityforms' ) ),
						),
					),
					'destructive'      => true,
				),
			),
			array(
				'name' => 'gravityforms/feeds-update',
				'args' => array(
					'label'            => __( 'Update Feed', 'gravityforms' ),
					'description'      => __( 'Updates an existing feed\'s configuration.', 'gravityforms' ),
					'summary'          => __( 'Change a form\'s integration settings.', 'gravityforms' ),
					'category'         => 'gravityforms-feeds',
					'execute_callback' => array( GF_Abilities_Handler_Feeds::class, 'update_feed' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'feed_id', 'feed_meta' ),
						'properties'           => array(
							'feed_id'   => array( 'type' => 'integer', 'description' => __( 'The feed ID to update.', 'gravityforms' ) ),
							'feed_meta' => array( 'type' => 'object', 'description' => __( 'Updated feed configuration.', 'gravityforms' ) ),
							'form_id'   => array( 'type' => 'integer', 'description' => __( 'Optional. Update the form association.', 'gravityforms' ) ),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => GF_Ability_Schemas::success_output_schema(),
					'destructive'      => true,
					'idempotent'       => false,
				),
			),
			array(
				'name' => 'gravityforms/feeds-delete',
				'args' => array(
					'label'            => __( 'Delete Feed', 'gravityforms' ),
					'description'      => __( 'Permanently deletes a feed.', 'gravityforms' ),
					'summary'          => __( 'Permanently delete a form\'s integration.', 'gravityforms' ),
					'category'         => 'gravityforms-feeds',
					'execute_callback' => array( GF_Abilities_Handler_Feeds::class, 'delete_feed' ),
					'capability'       => 'gravityforms_edit_forms',
					'input_schema'     => array(
						'type'                 => 'object',
						'required'             => array( 'feed_id', 'confirmation' ),
						'properties'           => array(
							'feed_id'      => array( 'type' => 'integer', 'description' => __( 'The ID of the feed to delete.', 'gravityforms' ) ),
							'confirmation' => array(
								'type'        => 'string',
								'description' => __( 'Echo back the feed ID to confirm permanent deletion.', 'gravityforms' ),
							),
						),
						'additionalProperties' => false,
					),
					'output_schema'    => GF_Ability_Schemas::success_output_schema(),
					'destructive'      => true,
				),
			),
		);
	}
}
