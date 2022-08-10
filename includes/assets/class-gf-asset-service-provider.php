<?php

namespace Gravity_Forms\Gravity_Forms\Assets;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

use Gravity_Forms\Gravity_Forms\Asset\Admin_Dependencies\GF_Admin_Script_Dependencies;
use Gravity_Forms\Gravity_Forms\Asset\Admin_Dependencies\GF_Admin_Style_Dependencies;

/**
 * Class GF_Asset_Service_Provider
 *
 * Service provider for assets.
 *
 * @package Gravity_Forms\Gravity_Forms\Merge_Tags;
 */
class GF_Asset_Service_Provider extends GF_Service_Provider {

	const HASH_MAP        = 'hash_map';
	const ASSET_PROCESSOR = 'asset_processor';
	const STYLE_DEPS      = 'gf_global_style_deps';
	const SCRIPT_DEPS     = 'gf_global_script_deps';

	/**
	 * Register services to the container.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . '/class-gf-asset-processor.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/admin-dependencies/class-gf-admin-dependencies.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/admin-dependencies/class-gf-admin-script-dependencies.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/admin-dependencies/class-gf-admin-style-dependencies.php' );

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

		$container->add( self::STYLE_DEPS, function() {
			return new GF_Admin_Style_Dependencies();
		} );

		$container->add( self::SCRIPT_DEPS, function() {
			return new GF_Admin_Script_Dependencies();
		} );
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

		add_filter( 'gform_noconflict_styles', function ( $styles ) use ( $container ) {
			return array_merge( $styles, $container->get( self::STYLE_DEPS )->get_items() );
		}, 1 );
	}

}