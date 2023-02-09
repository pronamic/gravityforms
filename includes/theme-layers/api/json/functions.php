<?php

use Gravity_Forms\Gravity_Forms\Theme_Layers\API\JSON\Layers\Json_Theme_Layer;
use Gravity_Forms\Gravity_Forms\Theme_Layers\GF_Theme_Layers_Provider;

function gforms_register_theme_json( $path ) {
	$container = GFForms::get_service_container();
	$layer     = new Json_Theme_Layer( $container->get( GF_Theme_Layers_Provider::DEFINITION_ENGINE_FACTORY ), $container->get( GF_Theme_Layers_Provider::OUTPUT_ENGINE_FACTORY ) );
	$layer->set_json( $path );
	$layer->init_engines();

	add_filter( 'gform_registered_theme_layers', function ( $layers ) use ( $layer ) {
		$layers[] = $layer;

		return $layers;
	} );
}