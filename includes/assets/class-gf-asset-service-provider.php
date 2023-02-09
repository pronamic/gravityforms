<?php

namespace Gravity_Forms\Gravity_Forms\Assets;

use Gravity_Forms\Gravity_Forms\Assets\Theme_Dependencies\GF_Theme_Script_Dependencies;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

use Gravity_Forms\Gravity_Forms\Assets\Admin_Dependencies\GF_Admin_Script_Dependencies;
use Gravity_Forms\Gravity_Forms\Assets\Admin_Dependencies\GF_Admin_Style_Dependencies;

/**
 * Class GF_Asset_Service_Provider
 *
 * Service provider for assets.
 *
 * @package Gravity_Forms\Gravity_Forms\Merge_Tags;
 */
class GF_Asset_Service_Provider extends GF_Service_Provider {

	const HASH_MAP          = 'hash_map';
	const ASSET_PROCESSOR   = 'asset_processor';
	const STYLE_DEPS        = 'gf_global_style_deps';
	const SCRIPT_DEPS       = 'gf_global_script_deps';
	const SCRIPT_DEPS_THEME = 'gf_global_script_deps_theme';
	const SVG_OPTIONS       = 'gf_svg_options';

	private $plugin_dir;

	public function __construct( $plugin_dir ) {
		$this->plugin_dir = $plugin_dir;
	}

	/**
	 * Register services to the container.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . '/class-gf-asset-processor.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/class-gf-dependencies.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/admin-dependencies/class-gf-admin-script-dependencies.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/admin-dependencies/class-gf-admin-style-dependencies.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/theme-dependencies/class-gf-theme-script-dependencies.php' );

		$container->add( self::HASH_MAP, function () {
			if ( ! file_exists( \GFCommon::get_base_path() . '/assets/js/dist/assets.php' ) ) {
				return array();
			}

			$map = require( \GFCommon::get_base_path() . '/assets/js/dist/assets.php' );

			return rgar( $map, 'hash_map', array() );
		} );

		$container->add( self::ASSET_PROCESSOR, function () use ( $container ) {
			$basepath   = \GFCommon::get_base_path();
			$asset_path = sprintf( '%s/assets/js/dist/', $basepath );

			return new GF_Asset_Processor( $container->get( self::HASH_MAP ), $asset_path );
		} );

		$container->add( self::STYLE_DEPS, function () {
			return new GF_Admin_Style_Dependencies();
		} );

		$container->add( self::SCRIPT_DEPS, function () {
			return new GF_Admin_Script_Dependencies();
		} );

		$container->add( self::SCRIPT_DEPS_THEME, function () {
			return new GF_Theme_Script_Dependencies();
		} );

		$this->svg_delivery( $container );
	}

	public function init( GF_Service_Container $container ) {
		add_action( 'init', function () use ( $container ) {
			$container->get( self::ASSET_PROCESSOR )->process_assets();
		}, 9999 );

		add_action( 'admin_enqueue_scripts', function () use ( $container ) {
			$container->get( self::STYLE_DEPS )->enqueue();
			$container->get( self::SCRIPT_DEPS )->enqueue();

			// Styles and scripts required for the tooltips.
			wp_enqueue_style( 'gform_font_awesome' );
			wp_enqueue_script( 'gform_tooltip_init' );
		} );

		add_action( 'gform_enqueue_scripts', function () use ( $container ) {
			$container->get( self::SCRIPT_DEPS_THEME )->enqueue();
		} );

		add_filter( 'gform_noconflict_styles', function ( $styles ) use ( $container ) {
			return array_merge( $styles, $container->get( self::STYLE_DEPS )->get_items() );
		}, 1 );
	}

	private function svg_delivery( GF_Service_Container $container ) {
		$default_path = sprintf( '%s/assets/img/base', untrailingslashit( $this->plugin_dir ) );

		/**
		 * Allows users to filter the path used to glob the available SVGs to use for display in themes.
		 *
		 * @since 2.7
		 *
		 * @param string $path The default orbital theme svg path within our plugin.
		 *
		 * @return string
		 */
		$svg_path = apply_filters( 'gform_svg_theme_path', $default_path );

		$svgs = array();

		foreach ( \GFCommon::glob( '*.svg', trailingslashit( $svg_path ) ) as $filename ) {
			$key          = pathinfo( $filename, PATHINFO_FILENAME );
			$svgs[ $key ] = file_get_contents( $filename );
		}

		/**
		 * Allows users to filter the SVG options available to output in themes.
		 *
		 * @since 2.7
		 *
		 * @param array $svgs The current SVG options.
		 *
		 * @return array
		 */
		$svgs = apply_filters( 'gform_svg_theme_options', $svgs );

		$container->add( self::SVG_OPTIONS, $svgs );
	}

}
