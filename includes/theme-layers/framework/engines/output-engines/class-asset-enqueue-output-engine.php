<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Output_Engines;

use GFAPI;

/**
 * Handles enqueuing various script and style assets.
 *
 * @since 2.7
 */
class Asset_Enqueue_Output_Engine extends Output_Engine {

	private static $groups = array(
		'reset'      => array(),
		'foundation' => array(),
		'framework'  => array(),
		'theme'      => array(),
	);

	protected $type = 'enqueued_asset';
	protected $styles;
	protected $scripts;


	/**
	 * Setter for styles.
	 *
	 * @since 2.7
	 *
	 * @param array $styles
	 */
	public function set_styles( array $styles ) {
		$this->styles = $styles;
	}

	/**
	 * Setter for scripts.
	 *
	 * @since 2.7
	 *
	 * @param array $scripts
	 */
	public function set_scripts( array $scripts ) {
		$this->scripts = $scripts;
	}

	/**
	 * Handle output by enqueuing the scripts and styles.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function output() {
		$self = $this;

		// Enqueue scripts and styles for blocks.
		add_action( 'gform_post_enqueue_scripts', function ( $found_forms, $found_blocks, $post ) use ( $self ) {
			foreach ( $found_blocks as $block ) {
				$settings = $self->get_settings( $block['attrs']['formId'] );
				$form     = \GFFormsModel::get_form( $block['attrs']['formId'] );
				$styles   = call_user_func_array( $self->styles, array( $form, false, $settings, $block['attrs'] ) );
				$scripts  = call_user_func_array( $self->scripts, array( $form, false, $settings, $block['attrs'] ) );

				$this->process_form_assets( $styles, $scripts );
			}

		}, 999, 3 );

		// Enqueue scripts and styles for forms that aren't in blocks.
		add_action( 'gform_enqueue_scripts', function ( $form, $ajax ) use ( $self ) {
			$page_instance  = isset( $form['page_instance'] ) ? $form['page_instance'] : - 1;
			$settings       = $this->get_settings( $form['id'] );
			$block_settings = $this->get_block_settings( $form['id'], $page_instance );
			$styles         = call_user_func_array( $self->styles, array( $form, $ajax, $settings, $block_settings ) );
			$scripts        = call_user_func_array( $self->scripts, array( $form, $ajax, $settings, $block_settings ) );

			$this->process_form_assets( $styles, $scripts );

		}, 999, 2 );

		add_action( 'gform_enqueue_scripts', function () use ( $self ) {
			global $wp_styles;
			$queued = $wp_styles->queue;
			usort( $queued, array( $self, 'sort_enqueues_by_group' ) );
			$wp_styles->queue = $queued;

			return;
		}, 1000, 0 );
	}

	public function sort_enqueues_by_group( $a, $b ) {
		$comp_keys = array_keys( self::$groups );

		// Setting these to -1 ensures our assets get enqueued after core/wp/other styles.
		$a_key = - 1;
		$b_key = - 1;

		// Our core assets always need to come first within their respective groups.
		$always_first = array(
			'gravity_forms_orbital_theme',
			'gravity_forms_theme_foundation',
			'gravity_forms_theme_framework',
			'gravity_forms_theme_reset',
		);

		// Loop through each asset in a group and find the correct positioning key to use for it.
		foreach ( self::$groups as $group => $entries ) {
			if ( in_array( $a, $entries ) ) {
				$a_key = array_search( $group, $comp_keys );
			}

			if ( in_array( $b, $entries ) ) {
				$b_key = array_search( $group, $comp_keys );
			}

			// Both have been located, break out of the loop to save performance.
			if ( $a_key > -1 && $b_key > -1 ) {
				break;
			}
		}

		// Assets are in same group, but $a is a core asset. Move it up.
		if ( $a_key == $b_key && in_array( $a, $always_first ) ) {
			return - 1;
		}

		// Assets are in same group, but $b is a core asset. Move it up.
		if ( $a_key == $b_key && in_array( $b, $always_first ) ) {
			return 1;
		}

		// Non-gf assets, or assets are in the same group and don't need to be ordered.
		if ( $a_key == $b_key ) {

			// In PHP < 7.0, usort does odd things to compared values. Get original position to avoid rearraging them.
			global $wp_styles;
			$queued     = $wp_styles->queue;
			$a_orig_key = array_search( $a, $queued );
			$b_orig_key = array_search( $b, $queued );

			return $a_orig_key < $b_orig_key ? - 1 : 1;
		}

		// Return sorting value based on group assets are in.
		return $a_key < $b_key ? - 1 : 1;
	}

	/**
	 * Enqueue the styles and scripts for a form.
	 *
	 * @since 2.7.4
	 *
	 * @param array $styles  Styles to enqueue
	 * @param array $scripts Scripts to enqueue
	 */
	public function process_form_assets( $styles, $scripts ) {
		foreach ( $scripts as $script_args ) {
			if ( ! is_array( $script_args ) ) {
				$script_args = array( $script_args );
			}

			call_user_func_array( 'wp_enqueue_script', $script_args );
		}

		$this->process_styles( $styles );
	}

	private function process_styles( $styles ) {
		foreach( $styles as $key => $style_args ) {
			if ( ! is_numeric( $key ) ) {
				$group = $key;

				if ( array_key_exists( $group, self::$groups ) ) {
					$items                  = wp_list_pluck( $style_args, 0 );
					self::$groups[ $group ] = array_merge( self::$groups[ $group ], $items );
				}

				$this->process_styles( $style_args );
				continue;
			}

			if ( ! is_array( $style_args ) ) {
				$style_args = array( $style_args );
			}

			call_user_func_array( 'wp_enqueue_style', $style_args );
		}

	}

}
