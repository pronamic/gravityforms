<?php

namespace Gravity_Forms\Gravity_Forms\Blocks;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\Blocks\Config\GF_Blocks_Config;
use Gravity_Forms\Gravity_Forms\Blocks\GF_Block_Attributes;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

use GFFormDisplay;
use GFCommon;

/**
 * Class GF_Blocks_Service_Provider
 *
 * Service provider for the Blocks Service.
 *
 * @package Gravity_Forms\Gravity_Forms\Blocks;
 */
class GF_Blocks_Service_Provider extends GF_Service_Provider {

	// Configs
	const BLOCKS_CONFIG = 'blocks_config';

	// Attributes
	const FORM_BLOCK_ATTRIBUTES = 'form_block_attributes';

	const BLOCK_ATTRIBUTES = 'block_attributes';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::BLOCKS_CONFIG => GF_Blocks_Config::class,
	);

	/**
	 * Register services to the container.
	 *
	 * @since
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {

		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-block-attributes.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-blocks-config.php' );

		$container->add( self::FORM_BLOCK_ATTRIBUTES, function () {
			require_once( GFCommon::get_base_path() . '/form_display.php' );
			$global_styles = GFFormDisplay::validate_form_styles( apply_filters( 'gform_default_styles', false ) );

			return array(
				'formId'                       =>
					array(
						'type' => 'string',
					),
				'title'                        =>
					array(
						'type'    => 'boolean',
						'default' => true,
					),
				'description'                  =>
					array(
						'type'    => 'boolean',
						'default' => true,
					),
				'ajax'                         =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
				'tabindex'                     =>
					array(
						'type' => 'string',
					),
				'fieldValues'                  =>
					array(
						'type' => 'string',
					),
				'formPreview'                  =>
					array(
						'type'    => 'boolean',
						'default' => true,
					),
				'imgPreview'                   =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
				'theme'                        =>
					array(
						'type'    => 'string',
						'default' => '',
					),
				'inputSize'                    =>
					array(
						'type'    => 'string',
						'default' => rgar( $global_styles, 'inputSize' ) ? $global_styles['inputSize'] : 'md',
					),
				'inputBorderRadius'            =>
					array(
						'type'    => 'string',
						'default' => rgar( $global_styles, 'inputBorderRadius' ) ? $global_styles['inputBorderRadius'] : 3,
					),
				'inputBorderColor'             =>
					array(
						'type'    => 'string',
						'default' => rgar( $global_styles, 'inputBorderColor' ) ? $global_styles['inputBorderColor'] : '#686e77',
					),
				'inputBackgroundColor'         =>
					array(
						'type'    => 'string',
						'default' => rgar( $global_styles, 'inputBackgroundColor' ) ? $global_styles['inputBackgroundColor'] : '#fff',
					),
				'inputColor'                   =>
					array(
						'type'    => 'string',
						'default' => rgar( $global_styles, 'inputColor' ) ? $global_styles['inputColor'] : '#112337',
					),
				'inputPrimaryColor'            =>
					array(
						'type'    => 'string',
						// Setting this to empty allows us to set this to what the appropriate default
						// should be from within the block. When empty, it defaults to:
						// buttonPrimaryBackgroundColor
						'default' => rgar( $global_styles, 'inputPrimaryColor' ) ? $global_styles['inputPrimaryColor'] : '', // #204ce5
					),
				'labelFontSize'                =>
					array(
						'type'    => 'string',
						'default' => rgar( $global_styles, 'labelFontSize' ) ? $global_styles['labelFontSize'] : 14,
					),
				'labelColor'                   =>
					array(
						'type'    => 'string',
						'default' => rgar( $global_styles, 'labelColor' ) ? $global_styles['labelColor'] : '#112337',
					),
				'descriptionFontSize'          =>
					array(
						'type'    => 'string',
						'default' => rgar( $global_styles, 'descriptionFontSize' ) ? $global_styles['descriptionFontSize'] : 13,
					),
				'descriptionColor'             =>
					array(
						'type'    => 'string',
						'default' => rgar( $global_styles, 'descriptionColor' ) ? $global_styles['descriptionColor'] : '#585e6a',
					),
				'buttonPrimaryBackgroundColor' =>
					array(
						'type'    => 'string',
						'default' => rgar( $global_styles, 'buttonPrimaryBackgroundColor' ) ? $global_styles['buttonPrimaryBackgroundColor'] : '#204ce5',
					),
				'buttonPrimaryColor'           =>
					array(
						'type'    => 'string',
						'default' => rgar( $global_styles, 'buttonPrimaryColor' ) ? $global_styles['buttonPrimaryColor'] : '#fff',
					),
			);
		} );

		$this->add_configs( $container );
		$this->block_attributes( $container );
	}

	/**
	 * Initialize any actions or hooks.
	 *
	 * @since
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	public function init( GF_Service_Container $container ) {

		add_action( 'gform_post_enqueue_scripts', function( $found_forms, $found_blocks, $post ) use ( $container ) {
			foreach( $found_blocks as $block ) {
				$attributes = $block['attrs'];
				$container->get( self::BLOCK_ATTRIBUTES )->store( $attributes );
			}
		}, -10, 3 );
	}

	/**
	 * For each config defined in $configs, instantiate and add to container.
	 *
	 * @since
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	private function add_configs( GF_Service_Container $container ) {
		foreach ( $this->configs as $name => $class ) {
			$container->add( $name, function () use ( $container, $class ) {
				if ( $class == GF_Blocks_Config::class ) {
					return new $class( $container->get( GF_Config_Service_Provider::DATA_PARSER ), $container->get( self::FORM_BLOCK_ATTRIBUTES ) );
				}

				return new $class( $container->get( GF_Config_Service_Provider::DATA_PARSER ) );
			} );

			$container->get( GF_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $container->get( $name ) );
		}
	}

	/**
	 * Register Block services.
	 *
	 * @since 2.7.4
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	private function block_attributes( GF_Service_Container $container ) {
		$container->add( self::BLOCK_ATTRIBUTES, function () use ( $container ) {
			return new GF_Block_Attributes();
		} );
	}

}

