<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\API;

use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Factories\Definition_Engine_Factory;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Factories\Output_Engine_Factory;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\GF_Theme_Layer;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Traits\Enqueues_Assets;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Traits\Has_Block_Settings;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Traits\Has_Settings_Fields;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Traits\Modifies_Markup;
use Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Traits\Outputs_Form_CSS_Properties;

/**
 * Implementation of GF_Theme_Layer which uses all available traits.
 *
 * @since 2.7
 */
abstract class GF_All_Access_Theme_Layer extends GF_Theme_Layer {

	use Has_Settings_Fields;
	use Has_Block_Settings;
	use Modifies_Markup;
	use Outputs_Form_CSS_Properties;
	use Enqueues_Assets;

	protected $_settings_fields     = array();
	protected $_block_settings      = array();
	protected $_overidden_fields    = array();
	protected $_form_css_properties = array();
	protected $_scripts             = array();
	protected $_styles              = array();

	public function __construct( Definition_Engine_Factory $definition_engine_factory, Output_Engine_Factory $output_engine_factory ) {
		$this->definition_engine_factory = $definition_engine_factory;
		$this->output_engine_factory     = $output_engine_factory;
	}

}