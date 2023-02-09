<?php

namespace Gravity_Forms\Gravity_Forms\Blocks;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\Blocks\Config\GF_Blocks_Config;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

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
		// Configs
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-blocks-config.php' );

		$container->add( self::FORM_BLOCK_ATTRIBUTES, function () {
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
						'default' => 'gravity',
					),
				'inputSize'                    =>
					array(
						'type'    => 'string',
						'default' => 'md',
					),
				'inputBorderRadius'            =>
					array(
						'type'    => 'string',
						'default' => 3,
					),
				'inputBorderColor'             =>
					array(
						'type'    => 'string',
						'default' => '#686e77',
					),
				'inputBackgroundColor'         =>
					array(
						'type'    => 'string',
						'default' => '#fff',
					),
				'inputColor'                   =>
					array(
						'type'    => 'string',
						'default' => '#112337',
					),
				'labelFontSize'                =>
					array(
						'type'    => 'string',
						'default' => 14,
					),
				'labelColor'                   =>
					array(
						'type'    => 'string',
						'default' => '#112337',
					),
				'descriptionFontSize'          =>
					array(
						'type'    => 'string',
						'default' => 13,
					),
				'descriptionColor'             =>
					array(
						'type'    => 'string',
						'default' => '#585e6a',
					),
				'buttonPrimaryBackgroundColor' =>
					array(
						'type'    => 'string',
						'default' => '#204ce5',
					),
				'buttonPrimaryColor'           =>
					array(
						'type'    => 'string',
						'default' => '#fff',
					),
			);
		} );

		$this->add_configs( $container );
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
		// add hooks or filters here.
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

}

