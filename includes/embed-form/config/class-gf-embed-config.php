<?php

namespace Gravity_Forms\Gravity_Forms\Embed_Form\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;

/**
 * Config items for the Embed Form UI.
 *
 * @since 2.6
 */
class GF_Embed_Config extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'components' => array(
				'embed_form' => array(
					'urls' => $this->get_urls(),
					'data' => array(
						'form_id'    => array(
							'value'   => rgget( 'id' ),
							'default' => 1,
						),
						'post_types' => array(
							'value'   => $this->get_available_post_types(),
							'default' => $this->placeholder_post_types(),
						),
						'items'      => array(
							'value'   => $this->get_items_by_type(),
							'default' => $this->placeholder_items(),
						),
					),
				),
			),
		);
	}

	/**
	 * Get the various URLs for the Embed UI.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	private function get_urls() {
		$posts     = get_posts( array( 'post_type' => 'any', 'post_status' => 'publish', 'posts_per_page' => 1 ) );
		$edit_link = '';

		if ( ! empty( $posts ) ) {
			$first     = $posts[0];
			$edit_link = get_edit_post_link( $first->ID, 'url' );
			$edit_link = preg_replace( '/(post=)([0-9]+)/', 'post=%1$s', $edit_link );
		}

		return [
			'edit_post'      => [
				'value'   => $edit_link,
				'default' => 'https://gravity.loc/wp-admin/post.php?post=%1$s&action=edit',
			],
			'shortcode_docs' => 'https://docs.gravityforms.com/shortcodes/',
		];
	}

	/**
	 * Get the Post Types data for the Embed UI.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	private function get_available_post_types() {
		$types = array(
			array(
				'slug'  => 'page',
				'label' => get_post_type_object( 'page' )->labels->singular_name,
			),
			array(
				'slug'  => 'post',
				'label' => get_post_type_object( 'post' )->labels->singular_name,
			),
		);

		/**
		 * Allows users to modify the post types sent as selectable options in the Embed UI.
		 *
		 * @since 2.6
		 *
		 * @param array $types
		 *
		 * @return array
		 */
		return apply_filters( 'gform_embed_post_types', $types );
	}

	/**
	 * Get the items to localize for each post type.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	private function get_items_by_type() {
		$types = $this->get_available_post_types();
		$data  = array();
		foreach ( $types as $type ) {
			$slug  = $type['slug'];
			$label = $type['label'];

			$items = get_posts( array( 'post_type' => $slug, 'posts_per_page' => 5 ) );
			array_walk( $items, function ( &$item ) {
				$item = array(
					'value' => $item->ID,
					'label' => $item->post_title,
				);
			} );

			$data[ $slug ]['entries'] = $items;
			$data[ $slug ]['count']   = $this->get_total_posts_by_type( $slug );
		}

		return $data;
	}

	/**
	 * Get the totals for the given post type.
	 *
	 * @since 2.6
	 *
	 * @param string $type - The Post Type to query for.
	 *
	 * @return array
	 */
	private function get_total_posts_by_type( $type ) {
		$args = array(
			'post_type'   => $type,
			'post_status' => 'publish',
		);

		$query = new \WP_Query( $args );

		return $query->found_posts;
	}

	/**
	 * Get the placeholder post type values for use in Mocks.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	private function placeholder_post_types() {
		return array(
			array( 'slug' => 'page', 'label' => __( 'Page', 'gravityforms' ) ),
			array( 'slug' => 'post', 'label' => __( 'Post', 'gravityforms' ) ),
		);
	}

	/**
	 * Get the placeholder post items for use in Mocks.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	private function placeholder_items() {
		return array(
			'post' => array(
				'count'   => 2,
				'entries' => array(
					array(
						'value' => 1,
						'label' => 'Post One',
					),
					array(
						'value' => 2,
						'label' => 'Post Two',
					),
				),
			),
			'page' => array(
				'count'   => 25,
				'entries' => array(
					array(
						'value' => 3,
						'label' => 'Page Three',
					),
					array(
						'value' => 4,
						'label' => 'Page Four',
					),
					array(
						'value' => 5,
						'label' => 'Page Five',
					),
					array(
						'value' => 6,
						'label' => 'Page Six',
					),
					array(
						'value' => 7,
						'label' => 'Page Seven',
					),
				),
			)
		);
	}

}
