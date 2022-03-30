<?php

namespace Gravity_Forms\Gravity_Forms\Orders\Factories;

use \Gravity_Forms\Gravity_Forms\Orders\GF_Order;
use \Gravity_Forms\Gravity_Forms\Orders\Items\GF_Order_Item;
use \Gravity_Forms\Gravity_Forms\Orders\Items\GF_Form_Product_Item;
use \Gravity_Forms\Gravity_Forms\Orders\Exporters\GF_Save_Entry_Order_Exporter;
use \GFCommon;

final class GF_Order_Factory {

	/**
	 * Creates an order from an entry.
	 *
	 * This method tries to locate the saved order meta in the entry meta first, and if no order meta found it creates an order from the entry products.
	 *
	 * @since 2.6
	 *
	 * @param array $form             The form object.
	 * @param array $entry            The entry object.
	 * @param bool  $use_choice_text  If the product field has choices, this decided if the choice text should be retrieved along with the product name or not.
	 * @param bool  $use_admin_labels Whether to use the product admin label or the front end label.
	 * @param bool  $receipt          Whether to show only the line items paid for in the order or all products in the form.
	 *
	 * @return GF_Order
	 */
	public static function create_from_entry( $form, $entry, $use_choice_text = false, $use_admin_labels = false, $receipt = false ) {

		if ( ! self::validate( $form, $entry ) ) {
			return new GF_Order();
		}

		$order_meta = gform_get_meta( $entry['id'], 'gform_order' );
		$order      = self::create_from_form( $form, $entry, $use_choice_text, $use_admin_labels );

		if ( ! $order_meta ) {
			return $order;
		}

		$order_meta['currency'] = rgar( $order_meta, 'currency', $entry['currency'] );
		$products               = self::get_products( $form, $entry, $use_choice_text, $use_admin_labels );
		$meta_rows              = rgar( $order_meta, 'rows' );

		// Load line items and custom items.
		foreach ( $meta_rows as $group_label => $items ) {
			foreach ( $items as $item ) {
				$item['belongs_to'] = $group_label;
				// When saving line items to the order meta, name and options are deleted as they already exist in form product fields.
				// Leave the name and options, but override all other properties that don't exist in the product field, like is_trial for example.
				if ( ! rgar( $item, 'name' ) && $order->get_item( $item['id'] ) ) {
					$order->get_item( $item['id'] )->override_properties( $item, array( 'options', 'name', 'price', 'sub_total', 'quantity' ) );
					unset( $products[ $item['id'] ] );
				} else {
					$order->add_item( new GF_Order_Item( $item['id'], $item ) );
				}
			}
		}

		// Delete any product that was not paid for if only a receipt is required.
		if ( $receipt ) {
			foreach ( $products as $id => $product ) {
				$order->delete_item( $product->get_id() );
			}
		}

		return $order;
	}

	/**
	 * Creates an order from a form.
	 *
	 * This method creates an order from the form product fields.
	 *
	 * @since 2.6
	 *
	 * @param array $form             The form object.
	 * @param array $entry            The entry object.
	 * @param bool  $use_choice_text  If the product field has choices, this decided if the choice text should be retrieved along with the product name or not.
	 * @param bool  $use_admin_labels Whether to use the product admin label or the front end label.
	 *
	 * @return GF_Order
	 */
	public static function create_from_form( $form, $entry, $use_choice_text = false, $use_admin_labels = false ) {

		if ( ! self::validate( $form, $entry ) ) {
			return new GF_Order();
		}

		$order           = new GF_Order();
		$order->currency = $entry['currency'];
		$products        = self::get_products( $form, $entry, $use_choice_text, $use_admin_labels );
		$order->add_items( $products );
		return $order;
	}

	/**
	 * Creates an order from a feed.
	 *
	 * This method created the order from the form products then adds/removes/replaces order items according to the feed settings.
	 *
	 * @since 2.6
	 *
	 * @param array                 $feed        The feed object.
	 * @param array                 $form        The form object.
	 * @param array                 $entry       The entry object.
	 * @param array                 $submission  The submitted data.
	 * @param \GFPaymentAddOn|null  $addon       An instance of the addon that is creating the order.
	 *
	 * @return GF_Order
	 */
	public static function create_from_feed( $feed, $form, $entry, $submission, $addon = null ) {

		if ( ! self::validate( $form, $entry ) ) {
			return new GF_Order();
		}

		$order = self::create_from_form( $form, $entry );

		// If trial is enabled, add required additional items to the order.
		if ( rgars( $feed, 'meta/transactionType' ) === 'subscription' && rgars( $feed, 'meta/trial_enabled' ) ) {

			$trial_discount = GFCommon::to_number( $submission['payment_amount'], $entry['currency'] ) * -1;
			/**
			 * Filter the trial discount amount.
			 *
			 * @since 2.6
			 *
			 * @param float  $trial_discount The trial discount amount.
			 * @param array  $form           The Form object to filter through
			 * @param array  $feed           The Form object to filter through
			 * @param array  $entry          The entry object to filter through
			 */
			$trial_discount = gf_apply_filters( array( 'gforms_order_trial_discount_item_price', $form['id'] ), $trial_discount, $form, $feed, $entry );

			$trial_discount_description = __( 'Trial Discount', 'gravityforms' );
			/**
			 * Filter the description of the trial discount.
			 *
			 * @since 2.6
			 *
			 * @param string $trial_discount_description The trial discount description.
			 * @param array  $form                       The Form object to filter through
			 * @param array  $feed                       The Form object to filter through
			 * @param array  $entry                      The entry object to filter through
			 */
			$trial_discount_description = gf_apply_filters( array( 'gforms_order_trial_discount_item_description', $form['id'] ), $trial_discount_description, $form, $feed, $entry );
			$order->add_item(
				new GF_Order_Item(
					'trial_discount',
					array(
						'name'         => $trial_discount_description,
						'price'        => abs( GFCommon::to_number( $trial_discount, $entry['currency'] ) ) * -1,
						'belongs_to'   => 'footer',
						'is_line_item' => true,
						'is_discount'  => true,
					)
				)
			);

			// If the trial product is a form product not a custom amount, set it as line item so it is always shown.
			if ( is_numeric( rgars( $feed, 'meta/trial_product' ) ) ) {
				$order->get_item( rgars( $feed, 'meta/trial_product' ) )->is_trial     = true;
				$order->get_item( rgars( $feed, 'meta/trial_product' ) )->is_line_item = true;
			}

			// If trial is free or custom amount, add a custom item for it.
			if (
				! rgars( $feed, 'meta/trial_product' )
				|| rgars( $feed, 'meta/trial_product' ) === 'enter_amount'
				|| rgars( $feed, 'meta/trial_product' ) === 'free_trial'
			) {

				$price = rgars( $feed, 'meta/trial_product' ) === 'enter_amount' ? GFCommon::to_number( rgars( $feed, 'meta/trial_amount' ), $order->currency ) : 0;

				/**
				 * Filter the price of the custom trial item.
				 *
				 * @since 2.6
				 *
				 * @param float  $price             The trial price.
				 * @param array  $form              The Form object to filter through
				 * @param array  $feed              The Form object to filter through
				 * @param array  $entry             The entry object to filter through
				 */
				$price = gf_apply_filters( array( 'gforms_order_trial_item_price', $form['id'] ), $price, $form, $feed, $entry );

				$trial_description = $price ? ( rgars( $feed, 'meta/trial_amount' ) . ' ' . __( 'Trial', 'gravityforms' ) ) : __( 'Free Trial', 'gravityforms' );

				/**
				 * Filter the description that appears in the subscription details box of the custom trial item.
				 *
				 * @since 2.6
				 *
				 * @param string $trial_description The trial description.
				 * @param array  $form              The Form object to filter through
				 * @param array  $feed              The Form object to filter through
				 * @param array  $entry             The entry object to filter through
				 */
				$trial_description = gf_apply_filters( array( 'gforms_order_trial_item_description', $form['id'] ), $trial_description, $form, $feed, $entry );

				$trial_item_name = $price ? __( 'Trial', 'gravityforms' ) : __( 'Free Trial', 'gravityforms' );

				/**
				 * Filter the name that appears in the order summary of the custom trial item.
				 *
				 * @since 2.6
				 *
				 * @param string $trial_item_name The trial item name.
				 * @param array  $form            The Form object to filter through
				 * @param array  $feed            The Form object to filter through
				 * @param array  $entry           The entry object to filter through
				 */
				$trial_item_name = gf_apply_filters( array( 'gforms_order_trial_discount_item_name', $form['id'] ), $trial_item_name, $form, $feed, $entry );

				$order->add_item(
					new GF_Order_Item(
						'trial',
						array(
							'price'        => $price,
							'name'         => $trial_item_name,
							'is_trial'     => true,
							'is_line_item' => true,
							'description'  => $trial_description,
						)
					)
				);
			}
		}

		// If the trial product is a form product not a custom amount, set it as line item so it is always shown.
		if ( rgars( $feed, 'meta/setupFee_enabled' ) ) {
			$order->get_item( rgars( $feed, 'meta/setupFee_product' ) )->is_setup     = true;
			$order->get_item( rgars( $feed, 'meta/setupFee_product' ) )->is_line_item = true;
		}

		// If payment amount is not set to form total, set only the selected products as line items, otherwise mark all as line items.
		if ( rgars( $feed, 'meta/transactionType' ) === 'product' && is_numeric( rgars( $feed, 'meta/paymentAmount' ) ) ) {
			$order->get_item( rgars( $feed, 'meta/paymentAmount' ) )->is_line_item = true;
		} elseif ( rgars( $feed, 'meta/transactionType' ) === 'subscription' && is_numeric( rgars( $feed, 'meta/recurringAmount' ) ) ) {
			$order->get_item( rgars( $feed, 'meta/recurringAmount' ) )->is_recurring = true;
			$order->get_item( rgars( $feed, 'meta/recurringAmount' ) )->is_line_item = true;
		} else {
			foreach ( $order->get_items() as $id => $item ) {
				$order->get_item( $id )->is_line_item = true;
			}
		}

		/**
		 * Allows adding additional items to the order before it is saved.
		 *
		 * @since 2.6
		 *
		 * @param array  $additional_items  An associative array that represents the ID of the item as key and the data of the item as a value array.
		 * @param array  $form              The Form object to filter through
		 * @param array  $feed              The Form object to filter through
		 * @param array  $entry             The entry object to filter through
		 */
		$additional_items = gf_apply_filters( array( 'gform_order_additional_items', $form['id'] ), array(), $form, $feed, $entry );

		foreach ( $additional_items as $id => $data ) {
			if ( ! $id || ! is_array( $data ) || empty( $data ) ) {
				continue;
			}

			$order->add_item( new GF_Order_Item( $id, $data ) );
		}

		return $order;

	}

	/**
	/**
	 * Gets the product fields in the form as GF_Form_Product_Item objects.
	 *
	 * @since 2.6
	 *
	 * @param array $form             The form object.
	 * @param array $entry            The entry object.
	 * @param bool  $use_choice_text  If the product field has choices, this decided if the choice text should be retrieved along with the product name or not.
	 * @param bool  $use_admin_labels Whether to use the product admin label or the front end label.
	 *
	 * @return array|GF_Form_Product_Item[] and empty array if the form has no products, or the products array.
	 */
	public static function get_products( $form, $entry, $use_choice_text, $use_admin_labels ) {
		$form_product_items = GFCommon::get_product_fields( $form, $entry, $use_choice_text, $use_admin_labels );
		$products           = rgar( $form_product_items, 'products' );
		if ( ! $products ) {
			return array();
		}
		$shipping = rgar( $form_product_items, 'shipping' );

		if ( ! empty( $shipping['name'] ) && ! empty( $shipping['price'] ) ) {
			$shipping['is_shipping']     = true;
			$shipping['is_line_item']    = true;
			$shipping['belongs_to']      = 'footer';
			$products[ $shipping['id'] ] = $shipping;
		}

		$product_items = array();
		foreach ( $products as $id => $product ) {
			$product['id']                   = $id;
			$product_items[ $product['id'] ] = new GF_Form_Product_Item( $product['id'], $product );
		}

		return $product_items;
	}

	/**
	 * Validates that the form and entry have the required information to creates an order.
	 *
	 * @param array $form  The form the order is being created from.
	 * @param array $entry The entry the order is being created from.
	 *
	 * @return bool
	 */
	public static function validate( $form, $entry ) {
		self::load_dependencies();
		if ( ! rgar( $form, 'id' ) || ! rgar( $form, 'fields' ) || empty( $entry ) ) {
			return false;
		}

		return true;
	}
	/**
	 * Includes the required classes.
	 *
	 * @since 2.6
	 */
	public static function load_dependencies() {
		if ( ! class_exists( 'GF_Order' ) ) {
			require_once GFCommon::get_base_path() . '/includes/orders/class-gf-order.php';
			require_once GFCommon::get_base_path() . '/includes/orders/items/class-gf-order-item.php';
			require_once GFCommon::get_base_path() . '/includes/orders/items/class-gf-form-product-order-item.php';
			require_once GFCommon::get_base_path() . '/includes/orders/exporters/class-gf-order-exporter.php';
			require_once GFCommon::get_base_path() . '/includes/orders/exporters/class-gf-entry-details-order-exporter.php';
			require_once GFCommon::get_base_path() . '/includes/orders/exporters/class-gf-save-entry-order-exporter.php';
			require_once GFCommon::get_base_path() . '/includes/orders/summaries/class-gf-order-summary.php';
		}
	}

}
