<?php

namespace Gravity_Forms\Gravity_Forms\Form_Editor;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\Form_Editor\Choices_UI\Config\GF_Choices_UI_Config;
use Gravity_Forms\Gravity_Forms\Form_Editor\Choices_UI\Config\GF_Choices_UI_Config_I18N;
use Gravity_Forms\Gravity_Forms\Form_Editor\Save_Form\Config\GF_Form_Editor_Form_Save_Config;
use Gravity_Forms\Gravity_Forms\Form_Editor\Save_Form\Endpoints\GF_Save_Form_Endpoint_Form_Editor;
use Gravity_Forms\Gravity_Forms\Form_Editor\Renderer\GF_Form_Editor_Renderer;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Util\GF_Util_Service_Provider;
use Gravity_Forms\Gravity_Forms\Save_Form\GF_Save_Form_Service_Provider;

/**
 * Class GF_Embed_Service_Provider
 *
 * Service provider for the Form Editor Services.
 *
 * @package Gravity_Forms\Gravity_Forms\Form_Editor;
 */
class GF_Form_Editor_Service_Provider extends GF_Service_Provider {

	// Configs
	const CHOICES_UI_CONFIG       = 'embed_config';
	const CHOICES_UI_CONFIG_I18N  = 'embed_config_i18n';
	const FORM_EDITOR_SAVE_CONFIG = 'form_editor_save_config';
	const FORM_EDITOR_RENDERER    = 'form_editor_renderer';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::CHOICES_UI_CONFIG       => GF_Choices_UI_Config::class,
		self::CHOICES_UI_CONFIG_I18N  => GF_Choices_UI_Config_I18N::class,
		self::FORM_EDITOR_SAVE_CONFIG => GF_Form_Editor_Form_Save_Config::class,
	);

	// Configs names, used as keys for the configuration classes in the service container.



	// Endpoint names, used as keys for the endpoint classes in the service container.
	// keys are the same names for the ajax actions.
	const ENDPOINT_FORM_EDITOR_SAVE = 'form_editor_save_form';

	/**
	 * The endpoint class names and their corresponding string keys in the service container.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $endpoints = array(
		self::ENDPOINT_FORM_EDITOR_SAVE => GF_Save_Form_Endpoint_Form_Editor::class,
	);

	public function register( GF_Service_Container $container ) {
		// Choices UI Configs
		require_once( plugin_dir_path( __FILE__ ) . '/choices-ui/config/class-gf-choices-ui-config.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/choices-ui/config/class-gf-choices-ui-config-i18n.php' );
		// Form Saver Configs
		require_once plugin_dir_path( __FILE__ ) . 'save-form/config/class-gf-form-editor-form-save-config.php';
		require_once plugin_dir_path( __FILE__ ) . 'save-form/endpoints/class-gf-save-form-endpoint-form-editor.php';
		// Editor Renderers.
		require_once plugin_dir_path( __FILE__ ) . 'renderer/class-gf-form-editor-renderer.php';

		$this->add_configs( $container );
		$this->add_endpoints( $container );
		$container->add( self::FORM_EDITOR_RENDERER, new GF_Form_Editor_Renderer() );
	}

	/**
	 * For each config defined in $configs, instantiate and add to container.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	private function add_configs( GF_Service_Container $container ) {
		$deps = array(
			GF_Config_Service_Provider::DATA_PARSER => $container->get( GF_Config_Service_Provider::DATA_PARSER ),
			GF_Util_Service_Provider::GF_FORMS      => $container->get( GF_Util_Service_Provider::GF_FORMS ),
			GF_Util_Service_Provider::GF_API        => $container->get( GF_Util_Service_Provider::GF_API ),
		);
		foreach ( $this->configs as $name => $class ) {
			$container->add(
				$name,
				function () use ( $container, $class, $deps ) {
					return new $class( $container->get( GF_Config_Service_Provider::DATA_PARSER ), $deps );
				}
			);

			$container->get( GF_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $container->get( $name ) );
		}
	}


	/**
	 * Register Form Saving Endpoints.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	private function add_endpoints( GF_Service_Container $container ) {
		foreach ( $this->endpoints as $name => $class ) {
			$container->add(
				$name,
				function () use ( $container, $class ) {
					return new $class(
						array(
							GF_Save_Form_Service_Provider::GF_FORM_CRUD_HANDLER => $container->get( GF_Save_Form_Service_Provider::GF_FORM_CRUD_HANDLER ),
							GF_Util_Service_Provider::GF_FORMS_MODEL => $container->get( GF_Util_Service_Provider::GF_FORMS_MODEL ),
							GF_Util_Service_Provider::GF_FORMS => $container->get( GF_Util_Service_Provider::GF_FORMS ),
						)
					);
				}
			);
		}
	}

	/**
	 * Initialize any actions or hooks required for handling form saving..
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 */
	public function init( GF_Service_Container $container ) {

		add_filter(
			'gform_is_form_editor',
			function ( $is_editor ) {
				if ( GF_Save_Form_Endpoint_Form_Editor::ACTION_NAME === rgpost( 'action' ) ) {
					return true;
				}

				return $is_editor;
			}
		);

		add_filter(
			'gform_ajax_actions',
			function( $ajax_actions ) {
				$ajax_actions[] = GF_Save_Form_Endpoint_Form_Editor::ACTION_NAME;

				return $ajax_actions;
			}
		);

		add_action(
			'wp_ajax_' . GF_Save_Form_Endpoint_Form_Editor::ACTION_NAME,
			function () use ( $container ) {
				$container->get( self::ENDPOINT_FORM_EDITOR_SAVE )->handle();
			}
		);

	}

}
