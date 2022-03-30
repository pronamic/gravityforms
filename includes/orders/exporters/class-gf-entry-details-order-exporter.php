<?php

namespace Gravity_Forms\Gravity_Forms\Orders\Exporters;

use \Gravity_Forms\Gravity_Forms\Orders\GF_Order;
use \Gravity_Forms\Gravity_Forms\Orders\Exporters\GF_Order_Exporter;
use \GFCommon;

class GF_Entry_Details_Order_Exporter extends GF_Order_Exporter {

	/**
	 * GF_Entry_Details_Order_Formatter constructor.
	 *
	 * @param GF_Order $order  The order to be formatted.
	 * @param array    $config Any specific configurations required while formatting the order.
	 */
	public function __construct( $order, $config = array() ) {
		parent::__construct( $order, $config );
		$this->data['rows'] = array();
	}

	/**
	 * Extracts a set of raw data from the order.
	 *
	 * @since 2.6
	 */
	protected function format() {

		foreach ( $this->order->get_items() as $item ) {
			$this->data['rows'][ $item->belongs_to ][] = $this->filter_item_data(
				$item,
				array(),
				array(
					'price_money'     => GFCommon::to_money( $item->get_base_price(), $this->order->currency ),
					'sub_total_money' => GFCommon::to_money( $item->sub_total, $this->order->currency ),
				)
			);
		}

		foreach ( $this->data['totals'] as $label => $total ) {
			$this->data['totals'][ $label . '_money' ] = GFCommon::to_money( $total, $this->order->currency );
		}
	}
}

