<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\GF_Theme_Layer;
use Gravity_Forms\Gravity_Forms_Conversational_Forms\Style_Layers\GFCF_Style_Layers_Provider;

\GFForms::include_addon_framework();

/**
 * Add-on class used to register our Theme Layers and pieces of architecture.
 *
 * @since 2.7
 */
class GF_Theme_Layers extends \GFAddOn {

	protected $_slug        = 'gf_theme_layers';
	protected $_full_path   = __FILE__;
	protected $_title       = 'Gravity Forms Theme Layers';
	protected $_short_title = 'Theme Layers';

	/**
	 * @var object|null $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * @var GF_Service_Container
	 */
	protected $container;

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @since 2.7
	 *
	 * @return GF_Conversational_Forms $_instance An instance of this class.
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function render_uninstall() {
		return '';
	}

	public function set_logging_supported( $plugins ) {
		return $plugins;
	}

	/**
	 * Add the form settings tab.
	 *
	 * @since 2.7
	 *
	 * @param $tabs
	 * @param $form_id
	 *
	 * @return array
	 */
	public function add_form_settings_menu( $tabs, $form_id ) {
		/**
		 * @var GF_Theme_Layer[]
		 */
		$theme_layers = \GFForms::get_service_container()->get( GF_Theme_Layers_Provider::THEME_LAYERS );

		foreach ( $theme_layers as $layer ) {
			/**
			 * @var GF_Theme_Layer $layer
			 */
			if ( empty( $layer->get_definitions()['settings'] ) ) {
				continue;
			}

			$tabs[] = array(
				'name'  => $layer->name(),
				'label' => $layer->short_title(),
				'icon'  => $layer->icon(),
				'query' => array(
					'theme_layer' => $layer->name(),
					'subview'     => $this->_slug,
				),
			);
		}

		return $tabs;
	}

	/**
	 * Form settings fields.
	 *
	 * @since 2.7
	 *
	 * @param $form
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		return array();
	}
}