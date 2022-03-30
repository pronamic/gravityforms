<?php
/**
 * Creates the template parts for use on the splash page
 *
 * @package Gravity_Forms\Gravity_Forms\Splash_Page
 */

namespace Gravity_Forms\Gravity_Forms\Splash_Page_Template_Tags;

use \GFForms;
use \GFCommon;

/**
 * Class GF_Splash_Page_Template_Tags
 *
 * @since 2.6
 *
 * Template tags for displaying content on the splash page.
 */
class GF_Splash_Page_Template_Tags {

	/**
	 * Display a headline.
	 *
	 * @since 2.6
	 *
	 * @param array $args Associative array of arguments with key and value.
	 *
	 * @return string HTML that will be displayed
	 */
	public function headline( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'container_classes' => '',
				'text'              => '',
			)
		);

		$classes = "gform-splash__section gform-splash__section--headline {$args['container_classes']}";

		return "<div class='{$classes}'><h2>{$args['text']}</h2></div>";
	}

	/**
	 * Display text next to an image.
	 *
	 * @since 2.6
	 *
	 * @param array $args Associative array of arguments with key and value.
	 *
	 * @return string HTML that will be displayed
	 */
	public function text_and_image( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'container_classes' => '',
				'image'             => array(),
				'image_placement'   => '',
				'text'              => '',
			)
		);

		$image_html = $this->build_image_html( $args['image'] );
		$classes    = "gform-splash__section gform-splash__section--text-and-image gform-splash__section--image-{$args['image_placement']} {$args['container_classes']}";

		return "<div class='{$classes}'><div class='gform-splash-text'>{$args['text']}</div><div class='gform-splash-image-wrapper'>{$image_html}</div></div>";
	}

	/**
	 * Display a full-width image.
	 *
	 * @since 2.6
	 *
	 * @param array $args Associative array of arguments with key and value.
	 *
	 * @return string HTML that will be displayed
	 */
	public function full_width_image( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'container_classes' => '',
				'image'             => array(),
			)
		);

		$image_html = $this->build_image_html( $args['image'] );
		$classes    = "gform-splash__section gform-splash__section--full-width-image {$args['container_classes']}";

		return "<div class='{$classes}'>{$image_html}</div>";
	}

	/**
	 * Display full-width text.
	 *
	 * @since 2.6
	 *
	 * @param array $args Associative array of arguments with key and value.
	 *
	 * @return string HTML that will be displayed
	 */
	public function full_width_text( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'container_classes' => '',
				'text'              => '',
			)
		);

		$classes = "gform-splash__section gform-splash__section--full-width-text {$args['container_classes']}";

		return "<div class='{$classes}'>{$args['text']}</div>";
	}

	/**
	 * Display text in equal-width columns.
	 *
	 * @since 2.6
	 *
	 * @param array $args Associative array of arguments with key and value.
	 *
	 * @return string HTML that will be displayed
	 */
	public function equal_columns( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'container_classes' => '',
				'columns'           => array(),
			)
		);

		$classes = "gform-splash__section gform-splash__section--columns {$args['container_classes']}";

		$columns_html = '<div class="columns">';
		foreach ( $args['columns'] as $column ) {
			$columns_html .= "<div class='column'>{$column}</div>";
		}
		$columns_html .= '</div>';

		return "<div class='{$classes}'>{$columns_html}</div>";
	}

	/**
	 * Take an array of image attributes and turn it into an HTML <img> tag.
	 *
	 * @since 2.6
	 *
	 * @param string|array $image Either the image URL as a string, or an array of image attributes.
	 *
	 * @return string HTML that will be displayed
	 */
	public function build_image_html( $image ) {
		if ( is_array( $image ) ) {
			$attrs = '';
			foreach ( $image as $attr => $value ) {
				$attrs .= $attr . '="' . $value . '" ';
			}
			$image_html = '<div class="gform-splash-image"><img ' . $attrs . '/></div>';
		} else {
			$image_html = '<div class="gform-splash-image"><img src="' . $image . '"></div>';
		}
		return $image_html;
	}
}
