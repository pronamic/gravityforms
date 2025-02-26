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
	 * Array of no conflict styles.
	 *
	 * @since 2.9.0
	 *
	 * @var array Array of style to be added to the no conflict style list.
	 */
	private $_no_conflict_styles = array();

	/**
	 * Adds a style handle to the list of no conflict styles.
	 *
	 * @since 2.9.0
	 *
	 * @param string $handle Style to be added to the no conflict list.
	 *
	 * @return void
	 */
	public function add_no_conflict_style( $handle ) {
		$this->_no_conflict_styles[] = $handle;
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

		// Don't enqueue assets if we're doing an AJAX request.
		if ( defined( 'DOING_AJAX' ) &&  DOING_AJAX ) {
			return;
		}

		// Enqueue styles for admin pages other than the form editor and block editor.
		add_action( 'admin_enqueue_scripts', function() use ( $self ) {

			// Ignore block editor pages because they are handled separately below.
			if ( \GFCommon::is_block_editor_page() ) {
				return;
			}

			// Handle pages where there is a form context.
			$form = $settings = array();
			if ( in_array( \GFForms::get_page(), array( 'form_editor', 'entry_detail', 'entry_detail_edit' ) ) ) {
				$form = GFAPI::get_form( rgget( 'id' ) );
				$settings = $self->get_settings( $form['id'] );
			}

			$self->enqueue_form_styles( $form, $settings );
		}, 1000 );

		// Enqueue styles for the site & block editor.
		add_action( 'enqueue_block_assets', function() use ( $self ) {
			if ( is_admin() ) {
				$self->enqueue_form_styles();
			}
		}, 1000 );

		// Enqueue styles in form preview.
		add_filter( 'gform_preview_styles', function( $styles, $form ) use ( $self ) {
			global $wp_styles;

			$settings = $this->get_settings( $form['id'] );
			$self->enqueue_form_assets( $form, false, $settings, array() );
			return array_unique( array_merge( $styles, $wp_styles->queue ) );
		}, 10, 2 );

		// Enqueue scripts and styles anywhere a form is loaded (admin or front end). Except for the block editor and form editor, which are handled above.
		add_action( 'gform_enqueue_scripts', function( $form, $ajax ) use ( $self ) {
			$settings = $this->get_settings( $form['id'] );
			$style_settings = $this->parse_form_style( $form );
			$self->enqueue_form_assets( $form, $ajax, $settings, $style_settings );
		}, 1000, 4 );

		// Adds theme layer styles to the no conflict list so that they get enqueued when no conflict mode is enabled.
		add_filter( 'gform_noconflict_styles', function ( $styles ) {
			return array_unique( array_merge( $styles, $this->_no_conflict_styles ) );
		});
	}

	/**
	 * Enqueues the scripts and styles for a form in the appropriate order and group.
	 *
	 * @since 2.9.0
	 *
	 * @param array $form           The form to enqueue scripts and styles for.
	 * @param bool  $ajax           Whether the form is being loaded via AJAX.
	 * @param array $settings       The settings for the form.
	 * @param array $style_settings The custom styles defined when embedding a form via the block editor or via the shortcode.
	 */
	protected function enqueue_form_assets( $form, $ajax, $settings, $style_settings ) {

		$styles   = call_user_func_array( $this->styles, array( $form, $ajax, $settings, $style_settings ) );
		$scripts  = call_user_func_array( $this->scripts, array( $form, $ajax, $settings, $style_settings ) );
		$this->process_form_assets( $styles, $scripts );

		$this->sort_enqueued_styles();
	}

	/**
	 * Enqueue the styles for a form in the appropriate order and group.
	 *
	 * @since 2.9.0
	 *
	 * @param array $form     The form to enqueue styles for. Optional. Some pages such as the block editor page won't have a form context.
	 * @param array $settings The settings for the form.
	 */
	protected function enqueue_form_styles( $form = array(), $settings = array() ) {
		$styles = call_user_func_array( $this->styles, array( $form, false, $settings ) );
		$this->process_styles( $styles );

		$this->sort_enqueued_styles();
	}

	/**
	 * Sorts enqueued styles by group. See {@see Asset_Enqueue_Output_Engine::sort_enqueues_by_group()} for more information.
	 *
	 * @since 2.9.0
	 */
	public function sort_enqueued_styles() {
		global $wp_styles;

		// Sort styles by group.
		$queued = $wp_styles->queue;
		usort( $queued, array( $this, 'sort_enqueues_by_group' ) );
		$wp_styles->queue = $queued;
	}

	/**
	 * Sorts enqueued styles by group. Core styles are always first within their respective groups.
	 * Groups are "reset", "foundation", "framework", and "theme". Groups are sorted in that order, and within each group, core styles are always first followed by other styles.
	 * For example, if an add-on has a style in the "foundation" and "framework" groups, styles will be sorted in the following order:
	 * 1. Core reset style
	 * 2. Core foundation style
	 * 3. Add-on foundation style
	 * 4. Core framework style
	 * 5. Add-on framework style
	 * 6. Core theme style
	 *
	 * @since 2.7.4
	 *
	 * @param string $a Style handle.
	 * @param string $b Style handle.
	 *
	 * @return int
	 */
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
	 * @param array $styles  Styles to enqueue.
	 * @param array $scripts Scripts to enqueue.
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

	/**
	 * Enqueue the styles of a form.
	 *
	 * @since Unknown
	 *
	 * @param array $styles An array of style slugs to be enqued.
	 */
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

			$this->add_no_conflict_style( $style_args[0] );
		}

	}

}
