<?php

namespace Gravity_Forms\Gravity_Forms\Template_Library\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Config_Data_Parser;
use Gravity_Forms\Gravity_Forms\License\GF_License_API_Connector;
use Gravity_Forms\Gravity_Forms\Template_Library\Endpoints\GF_Create_Form_Template_Library_Endpoint;
use Gravity_Forms\Gravity_Forms\Template_Library\Templates\GF_Templates_Store;

/**
 * Config items for Template_Library.
 *
 * @since
 */
class GF_Template_Library_Config extends GF_Config {

	/**
	 * The object name for this config.
	 *
	 * @since 2.7
	 *
	 * @var string
	 */
	protected $name = 'gform_admin_config';

	/**
	 * The ID of the script to localize the data to.
	 *
	 * @since 2.7
	 *
	 * @var string
	 */
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * The templates' data store to retrieve the templates' data from.
	 *
	 * @since 2.7
	 *
	 * @var GF_Templates_Store $templates_repos
	 */
	protected $templates_store;

	/**
	 * The license API connector to get license information.
	 *
	 * @since 2.7
	 *
	 * @var GF_License_API_Connector
	 */
	protected $license_api;

	/**
	 * Config class constructore.
	 *
	 * @since 2.7
	 *
	 * @param GF_Config_Data_Parser $parser          Data Parser.
	 * @param GF_Templates_Store    $templates_store The templates' data store to retrieve the templates' data from.
	 */
	public function __construct( GF_Config_Data_Parser $parser, GF_Templates_Store $templates_store, GF_License_API_Connector $license_api ) {
		parent::__construct( $parser );
		$this->templates_store = $templates_store;
		$this->license_api     = $license_api;
	}

	public function should_enqueue() {
		$current_page = trim( strtolower( rgget( 'page' ) ) );
		$gf_pages     = array( 'gf_edit_forms', 'gf_new_form' );

		return in_array( $current_page, $gf_pages );
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		$license_info = $this->license_api->check_license();

		return array(
			'components' => array(
				'template_library' => array(
					'endpoints' => $this->get_endpoints(),
					'i18n'      => array(
						'description'                => __( 'Form Description', 'gravityforms' ),
						'title'                      => __( 'Form Title', 'gravityforms' ),
						'titlePlaceholder'           => __( 'Enter the form title', 'gravityforms' ),
						'required'                   => __( 'Required', 'gravityforms' ),
						'useTemplate'                => __( 'Use Template', 'gravityforms' ),
						'closeButton'                => __( 'Close', 'gravityforms' ),
						/* translators: title of template */
						'useTemplateWithTitle'       => __( 'Use Template %s', 'gravityforms' ),
						'createActiveText'           => __( 'Creating Form', 'gravityforms' ),
						'missingTitle'               => __( 'Please enter a valid form title.', 'gravityforms' ),
						'duplicateTitle'             => __( 'Please enter a unique form title.', 'gravityforms' ),
						'failedRequest'              => __( 'There was an issue creating your form.', 'gravityforms' ),
						'failedRequestDialogTitle'   => __( 'Import failed.', 'gravityforms' ),
						'importErrorCloseText'       => __( 'Close.', 'gravityforms' ),
						/* translators: title of template */
						'previewWithTitle'           => __( 'Preview %s', 'gravityforms' ),
						'cancel'                     => __( 'Cancel', 'gravityforms' ),
						'blankForm'                  => __( 'Blank Form', 'gravityforms' ),
						'createForm'                 => __( 'Create Blank Form', 'gravityforms' ),
						'blankFormTitle'             => __( 'New Blank Form', 'gravityforms' ),
						'blankFormDescription'       => __( 'A new blank form', 'gravityforms' ),
						'formDescriptionPlaceHolder' => __( 'A form description goes here', 'gravityforms' ),
						'heading'                    => __( 'Explore Form Templates', 'gravityforms' ),
						'subheading'                 => __( 'Quickly create an amazing form by using a pre-made template, or start from scratch to tailor your form to your specific needs.', 'gravityforms' ),
						'upgradeTag'                 => __( 'Upgrade', 'gravityforms' ),
						/* translators: %1$s is anchor opening tag, %2$s is anchor closing tag */
						'upgradeAlert'               => sprintf( __( 'This template uses Add-ons not included in your current license plan. %1$sUpgrade%2$s'), '<a href="' . $license_info->get_upgrade_link() . '" target="_blank" rel="noopener noreferrer">', '</a>' ),
					),
					'data'      => array(
						'thumbnail_url' => \GFCommon::get_image_url( 'template-library/' ),
						'layout'        => 'full-screen',
						'templates'     => array_values( $this->get_templates() ),
						'licenseType'   => $license_info->get_data_value( 'product_code' ),
						'defaults'      => array(
							'isLibraryOpen'             => rgget( 'page' ) === 'gf_new_form',
							'flyoutOpen'                => false,
							'flyoutFooterButtonLabel'   => '',
							'flyoutTitleValue'          => '',
							'flyoutDescriptionValue'    => '',
							'selectedTemplate'          => '',
							'flyoutTitleErrorState'     => false,
							'flyoutTitleErrorMessage'   => '',
							'importError'               => false,
							'flyoutPrimaryLoadingState' => false,
						),
					),
				),
			),
		);
	}

	/**
	 * Returns the endpoints for handling form creation in the template library.
	 *
	 * @since 2.7
	 *
	 * @return \array[][]
	 */
	private function get_endpoints() {
		return array(
			'create_from_template' => array(
				'action' => array(
					'value'   => GF_Create_Form_Template_Library_Endpoint::ACTION_NAME,
					'default' => 'mock_endpoint',
				),
				'nonce'  => array(
					'value'   => wp_create_nonce( GF_Create_Form_Template_Library_Endpoint::ACTION_NAME ),
					'default' => 'nonce',
				),
			),
		);
	}

	/**
	 * Gets a list of the available templates from the data store.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	private function get_templates() {
		return $this->templates_store->all();
	}


}
