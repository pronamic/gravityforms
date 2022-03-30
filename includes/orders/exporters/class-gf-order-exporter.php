<?php

namespace Gravity_Forms\Gravity_Forms\Orders\Exporters;

use \Gravity_Forms\Gravity_Forms\Orders\GF_Order;
use \Gravity_Forms\Gravity_Forms\Orders\Items\GF_Order_Item;
use \GFCommon;


class GF_Order_Exporter {

	/**
	 * The order to be formatted.
	 *
	 * @since 2.6
	 *
	 * @var GF_Order
	 */
	protected $order;

	/**
	 * Any specific configurations required while formatting the order.
	 *
	 * @since 2.6
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * An array containing the extracted order data.
	 *
	 * @since 2.6
	 *
	 * @var array[]
	 */
	protected $data = array(
		'totals' => array(),
	);

	/**
	 * GF_Order_Formatter constructor.
	 *
	 * @param GF_Order $order  The order to be formatted.
	 * @param array    $config Any specific configurations required while formatting the order.
	 */
	public function __construct( $order, $config = array() ) {
		$this->order          = $order;
		$this->data['totals'] = $this->order->get_totals();
	}

	/**
	 * Extracts a set of raw data from the order.
	 *
	 * @since 2.6
	 */
	protected function format() {

	}

	/**
	 * Filters the item data and keeps only the required values.
	 *
	 * @since 2.6
	 *
	 * @param GF_Order_Item $item    The order item.
	 * @param array         $exclude A set of properties to exclude from the item.
	 * @param array         $add     More rows to be added to the item data.
	 *
	 * @return array The filtered data.
	 */
	protected function filter_item_data( $item, $exclude = array(), $add = array() ) {

		$data = $item->to_array();

		if ( is_array( $exclude ) && ! empty( $exclude ) ) {
			$data = array_diff_key( $data, array_flip( $exclude ) );
		}

		if ( is_array( $add ) && ! empty( $add ) ) {
			$data = array_merge( $data, $add );
		}

		return array_filter( $data );
	}

	/**
	 * Returns the extracted data.
	 *
	 * @since 2.6
	 *
	 * @param string|callable $output What format to use when exporting the data, or a function to execute on the formatted data.
	 *
	 * @return mixed|array[]
	 */
	public function export( $output = 'ARRAY' ) {
		try {
			$this->format();
		} catch ( \Exception $ex ) {
			$this->data['errors'] = array( $ex->getMessage(), $ex->getCode() );
		}

		if ( is_callable( $output ) ) {
			return $output( $this->data );
		}

		return $output === 'json' ? json_encode( $this->data ) : $this->data;
	}
}

