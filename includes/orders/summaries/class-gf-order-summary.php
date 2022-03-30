<?php

namespace Gravity_Forms\Gravity_Forms\Orders\Summaries;

use \Gravity_Forms\Gravity_Forms\Orders\GF_Order;
use \Gravity_Forms\Gravity_Forms\Orders\Factories\GF_Order_Factory;
use \Gravity_Forms\Gravity_Forms\Orders\Exporters\GF_Entry_Details_Order_Exporter;

final class GF_Order_Summary {

	/**
	 * Contains any specific configurations for rendering the summary, for example showing only a receipt.
	 *
	 * @since 2.6
	 *
	 * @var array
	 */
	public static $configurations;

	/**
	 * Renders the summary markup using the provided data and view.
	 *
	 * @since 2.6
	 *
	 * @param array  $form             The form object.
	 * @param array  $entry            The entry object.
	 * @param string $view             The view to be used for rendering the order.
	 * @param bool   $use_choice_text  If the product field has choices, this decided if the choice text should be retrieved along with the product name or not.
	 * @param bool   $use_admin_labels Whether to use the product admin label or the front end label.
	 * @param bool   $receipt          Whether to show only the line items paid for in the order or all products in the form.
	 *
	 * @return string The summary markup.
	 */
	public static function render( $form, $entry, $view = 'order-summary', $use_choice_text = false, $use_admin_labels = false, $receipt = false ) {
		GF_Order_Factory::load_dependencies();

		$order         = GF_Order_Factory::create_from_entry( $form, $entry, $use_choice_text, $use_admin_labels, rgar( self::$configurations, 'receipt' ) );
		$order_summary = ( new GF_Entry_Details_Order_Exporter( $order ) )->export();
		if ( empty( $order_summary['rows'] ) ) {
			return '';
		}

		$order_summary['labels'] = self::get_labels( $form );
		ob_start();
		include 'views/view-' . $view . '.php';
		return ob_get_clean();

	}

	/**
	 * Return the labels used in the summary view.
	 *
	 * @since 2.6
	 *
	 * @param array form The form object.
	 *
	 * @return array
	 */
	public static function get_labels( $form ) {
		return array(
			'order_label'       => gf_apply_filters( array( 'gform_order_label', $form['id'] ), __( 'Order', 'gravityforms' ), $form['id'] ),
			'product'           => gf_apply_filters( array( 'gform_product', $form['id'] ), __( 'Product', 'gravityforms' ), $form['id'] ),
			'product_qty'       => gf_apply_filters( array( 'gform_product_qty', $form['id'] ), __( 'Qty', 'gravityforms' ), $form['id'] ),
			'product_unitprice' => gf_apply_filters( array( 'gform_product_unitprice', $form['id'] ), __( 'Unit Price', 'gravityforms' ), $form['id'] ),
			'product_price'     => gf_apply_filters( array( 'gform_product_price', $form['id'] ), __( 'Price', 'gravityforms' ), $form['id'] ),

		);
	}

}
