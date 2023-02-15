<?php

namespace Gravity_Forms\Gravity_Forms\Form_Display;

use Gravity_Forms\Gravity_Forms\Form_Display\Full_Screen\Full_Screen_Handler;
use Gravity_Forms\Gravity_Forms\Form_Display\Block_Styles\Block_Styles_Handler;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Query\GF_Query_Service_Provider;
use Gravity_Forms\Gravity_Forms\Query\JSON_Handlers\GF_Query_JSON_Handler;
use Gravity_Forms\Gravity_Forms\Query\JSON_Handlers\GF_String_JSON_Handler;
use \GFCommon;
use \GFForms;

/**
 * Class GF_Form_Display_Service_Provider
 *
 * Service provider for the Form_Display Service.
 *
 * @package Gravity_Forms\Gravity_Forms\Form_Display;
 */
class GF_Form_Display_Service_Provider extends GF_Service_Provider {

	const FULL_SCREEN_HANDLER   = 'full_screen_handler';
	const BLOCK_STYLES_HANDLER  = 'block_styles_handler';
	const BLOCK_STYLES_DEFAULTS = 'block_styles_defaults';

	/**
	 * Register services to the container.
	 *
	 * @since 2.7
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . '/full-screen/class-full-screen-handler.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/block-styles/views/class-form-view.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/block-styles/views/class-confirmation-view.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/block-styles/block-styles-handler.php' );

		$container->add( self::FULL_SCREEN_HANDLER, function() use ( $container ) {
			// Use string handler for now to avoid JSON query issues on old platforms.
			$handler = $container->get( GF_Query_Service_Provider::JSON_STRING_HANDLER );

			return new Full_Screen_Handler( $handler );
		});

		$container->add( self::BLOCK_STYLES_DEFAULTS, function() {
			return array(
				'theme'                        => 'gravity',
				'inputSize'                    => 'md',
				'inputBorderRadius'            => 3,
				'inputBorderColor'             => '#686e77',
				'inputBackgroundColor'         => '#fff',
				'inputColor'                   => '#112337',
				'labelFontSize'                => 14,
				'labelColor'                   => '#112337',
				'descriptionFontSize'          => 13,
				'descriptionColor'             => '#585e6a',
				'buttonPrimaryBackgroundColor' => '#204ce5',
				'buttonPrimaryColor'           => '#fff',
			);
		});

		$container->add( self::BLOCK_STYLES_HANDLER, function() use ( $container ) {
			return new Block_Styles_Handler( $container->get( self::BLOCK_STYLES_DEFAULTS ) );
		});
	}

	/**
	 * Initiailize any actions or hooks.
	 *
	 * @since
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	public function init( GF_Service_Container $container ) {
		add_filter( 'template_include', function ( $template ) use ( $container ) {
			return $container->get( self::FULL_SCREEN_HANDLER )->load_full_screen_template( $template );
		} );

		add_action( 'init', function () use ( $container ) {
			$container->get( self::BLOCK_STYLES_HANDLER )->handle();
		}, 0, 0 );

		add_action( 'gform_enqueue_scripts', array( $this, 'register_theme_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_theme_styles' ) );
	}

	public function register_theme_styles() {
		$base_url = GFCommon::get_base_url();
		$version  = GFForms::$version;
		$min      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		wp_register_style( 'gravity_forms_theme_reset', "{$base_url}/assets/css/dist/gravity-forms-theme-reset{$min}.css", array(), $version );
		wp_register_style( 'gravity_forms_theme_foundation', "{$base_url}/assets/css/dist/gravity-forms-theme-foundation{$min}.css", array(), $version );
		wp_register_style( 'gravity_forms_theme_framework', "{$base_url}/assets/css/dist/gravity-forms-theme-framework{$min}.css", array(
			'gravity_forms_theme_reset',
			'gravity_forms_theme_foundation'
		), $version );
		wp_register_style( 'gravity_forms_orbital_theme', "{$base_url}/assets/css/dist/gravity-forms-orbital-theme{$min}.css", array( 'gravity_forms_theme_framework' ), $version );
	}

}

