<?php


namespace Gravity_Forms\Gravity_Forms\Query\Batch_Processing;


use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;

class GF_Batch_Operations_Service_Provider extends GF_Service_Provider {

	const ENTRY_META_BATCH_PROCESSOR = 'entry_meta_batch_processor';
	/**
	 * Register new services to the Service Container.
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	public function register( GF_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . 'class-gf-entry-meta-batch-processor.php' );
		$container->add(
			self::ENTRY_META_BATCH_PROCESSOR,
			function() {
				return new GF_Entry_Meta_Batch_Processor();
			}
		);
	}
}
