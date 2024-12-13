<?php

namespace Gravity_Forms\Gravity_Forms\Libraries;

class Dom_Parser {

	const NO_PARSE_QUERY_ARG = 'gf_disable_hooks_injection';

	/**
	 * The string representation of the current DOM.
	 *
	 * @since 2.5.6
	 *
	 * @var string $content
	 */
	public $content;

	/**
	 * A DOMDocument object made by calling loadXML().
	 *
	 * @since 2.5.6
	 *
	 * @var DOMDocument $dom_xml
	 */
	public $dom_xml;

	/**
	 * A DOMDocument object made by calling loadHTML().
	 *
	 * @since 2.5.6
	 *
	 * @var DOMDocument $dom_html
	 */
	public $dom_html;

	/**
	 * Whether the current server has DOMDocument active.
	 *
	 * @since 2.5.6
	 *
	 * @var bool $has_domdocument
	 */
	public $has_domdocument;

	/**
	 * The position at which to insert the hooks script.
	 *
	 * @since 2.5.6
	 *
	 * @var int $insert_position
	 */
	public $insert_position = 0;

	/**
	 * GF_Dom_Parser constructor.
	 *
	 * @param string $content
	 */
	public function __construct( $content ) {
		$this->content         = $content;
		$this->has_domdocument = class_exists( 'DOMDocument' );

		if ( ! $this->has_domdocument || empty( $this->content ) ) {
			return;
		}

		$this->parse_dom();
	}

	/**
	 * Parse the DOM content into XML and HTML DOMDocuments.
	 *
	 * @since 2.5.6
	 *
	 * @return void
	 */
	private function parse_dom() {
		libxml_use_internal_errors( true );
		$this->dom_xml  = $this->get_dom_xml();
		$this->dom_html = $this->get_dom_html();
		libxml_clear_errors();
	}

	/**
	 * Callback to fire when ob_flush() is called. Allows us to ensure that our Hooks JS has been output on the page,
	 * even in heavily-cached or concatenated environments.
	 *
	 * @since 2.5.6
	 *
	 * @return string
	 */
	public function get_injected_html() {
		require_once \GFCommon::get_base_path() . '/form_display.php';

		$has_printed = \GFFormDisplay::$hooks_js_printed;

		/**
		 * Allow plugins to force the hook vars to output no matter what. Useful for certain edge-cases.
		 *
		 * @since  2.5.3
		 *
		 * @param bool $force_output Whether to force the script output.
		 *
		 * @return bool
		 */
		$force_output = apply_filters( 'gform_force_hooks_js_output', false );

		if ( ! $force_output && ! $has_printed ) {
			return $this->content;
		}

		if ( ! $this->should_inject_hooks_js() ) {
			return $this->content;
		}

		return $this->inject_hooks_js();
	}

	/**
	 * Take the given DOM Content and inject the Hooks JS code in the correct position.
	 *
	 * @return string
	 */
	private function inject_hooks_js() {
		$insert_position  = $this->get_insert_position();
		$hooks_javascript = \GFCommon::get_hooks_javascript_code();

		$content = str_replace( $hooks_javascript, '', $this->content );
		$string  = \GFCommon::get_inline_script_tag( $hooks_javascript );
		$pieces  = preg_split( "/\r\n|\n|\r/", $content );

		if ( count( $pieces ) > 1 && $insert_position > 0 ) {
			array_splice( $pieces, $insert_position, 0, $string );
			$content = implode( "\n", $pieces );
		} else {
			$content = preg_replace( '/(<[\s]*head(?!e)[^>]*>)/', '$0 ' . $string, $content, 1 );
		}

		return $content;
	}

	/**
	 * There are some contexts in which we do not want to inject our Hooks JS. This determines
	 * whether we are in one of those contexts.
	 *
	 * @since 2.5.6
	 *
	 * @return bool
	 */
	public function should_inject_hooks_js() {
		if ( ! $this->is_parseable_request() ) {
			return false;
		}

		if ( $this->is_xml() ) {
			return false;
		}

		if ( ! $this->is_full_html_doc() ) {
			return false;
		}

		if ( $this->is_amp() ) {
			return false;
		}

		return true;
	}

	/**
	 * Attempt to parse the DOM Content into a DOMDocument XML model.
	 *
	 * @since 2.5.6
	 *
	 * @return DOMDocument|false
	 */
	private function get_dom_xml() {
		if ( empty( $this->content ) ) {
			return false;
		}

		try {
			$xdom = new \DOMDocument();
			$xdom->loadXML( $this->content );

			return $xdom;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Attempt to parse the DOM Content into a DOMDocument HTML model.
	 *
	 * @since 2.5.6
	 *
	 * @return DOMDocument|false
	 */
	private function get_dom_html() {
		if ( empty( $this->content ) ) {
			return false;
		}

		try {
			$dom = new \DOMDocument();
			$dom->loadHTML( $this->content );

			return $dom;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Get the correct line position at which the Hooks JS should be inserted. A location of 0 will result
	 * in the script being added right after the opening <head> tag, while anything greater will inject it
	 * at the defined line number.
	 *
	 * @since 2.5.6
	 *
	 * @return int
	 */
	private function get_insert_position() {
		// Default to 0 to inject right after head.
		$insert_position = 0;
		$insert_el       = false;

		if ( ! $this->has_domdocument ) {
			return 0;
		}

		if ( ! $this->dom_html ) {
			return 0;
		}

		$meta_els = $this->dom_html->getElementsByTagName( 'meta' );

		foreach ( $meta_els as $meta_el ) {
			if (
				// Some charsets are defined via a charset attribute
				$meta_el->hasAttribute( 'charset' ) ||

				// Other charsets are defined via a combo of http-equiv and content attritbutes
				(
					$meta_el->hasAttribute( 'http-equiv' ) &&
					$meta_el->hasAttribute( 'content' )
				)
			) {
				$insert_position = $meta_el->getLineNo();
				$insert_el       = $meta_el;
			}
		}

		if ( $insert_position === 0 ) {
			return $insert_position;
		}

		$pieces   = preg_split( "/\r\n|\n|\r/", $this->content );
		$previous = $pieces[ $insert_position - 1 ];

		// Only use injection position if the detected line # actually falls after the meta tag.
		preg_match( '/<\s*meta[^>]*>$/', $previous, $pos_matches );

		if ( empty( $pos_matches ) ) {
			return 0;
		}

		return $insert_position;
	}

	/**
	 * Determine if the current server request is one which requires us to add our hooks scripts.
	 *
	 * @since 2.5.6
	 * @since 2.5.13 - Added $check_empty param
	 *
	 * @param bool $check_empty Whether or not to validate that the DOM content isn't empty.
	 *
	 * @return bool
	 */
	public function is_parseable_request( $check_empty = true ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( ! empty( $_POST['gform_ajax'] ) ) {
			return false;
		}

		$query_arg = rgget( self::NO_PARSE_QUERY_ARG );

		if ( ! empty( $query_arg ) ) {
			return false;
		}

		if ( $check_empty && empty( $this->content ) ) {
			return false;
		}

		/**
		 * Allow developers to disable the DOM parser entirely.
		 * This filter is useful for environments where performance is a priority and the DOM parser is not needed.
		 * Only recommended for advanced users who understand the implications of disabling the DOM parser.
		 *
		 * @since 2.9.1
		 *
		 * Example:
		 * add_filter( 'gform_disable_dom_parser', '__return_true' ); // Disables the Dom parser on every page.
		 */
 		$is_disabled = apply_filters( 'gform_disable_dom_parser', false );
		return ! $is_disabled;
	}

	/**
	 * Determine if the current document is an XML document.
	 *
	 * @since 2.5.6
	 *
	 * @return bool
	 */
	public function is_xml() {
		if ( ! $this->has_domdocument ) {
			return false;
		}

		if ( ! $this->dom_xml ) {
			return false;
		}

		if ( ! is_null( $this->dom_xml->documentElement ) && $this->dom_xml->documentElement->tagName !== 'html' ) {
			return true;
		}

		return false;
	}

	/**
	 * Determine if the current document has the required (<html>, <head>) elements, and thus
	 * should be treated as a full doc.
	 *
	 * @since 2.5.6
	 *
	 * @return bool
	 */
	public function is_full_html_doc() {
		if ( ! $this->has_domdocument ) {
			return $this->has_head_regex();
		}

		if ( ! $this->dom_html ) {
			return false;
		}

		$html = $this->dom_html->getElementsByTagName( 'html' );
		$head = $this->dom_html->getElementsByTagName( 'head' );

		// No HTML tag or head tag - we shouldn't mess with this so we bail.
		if ( empty( $head->length ) || empty( $html->length ) ) {
			return false;
		}

		return true;
	}

	/**
	 * If the current server doesn't have DOMDocument defined, use a regex method to find the
	 * <head> element. Less reliable than the DOMDocument method, but a decent fallback.
	 *
	 * @since 2.5.6
	 *
	 * @return bool
	 */
	private function has_head_regex() {
		preg_match( '/(<[\s]*head(?!e)[^>]*>)/', $content, $hmatches );

		if ( empty( $hmatches ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine whether the current document is an AMP document.
	 *
	 * @since 2.5.6
	 *
	 * @return bool
	 */
	public function is_amp() {
		if ( ! $this->has_domdocument || ! $this->dom_html ) {
			return $this->is_amp_regex();
		}

		$html = $this->dom_html->getElementsByTagName( 'html' );

		$html_el = $html[0];

		// Markup is AMP using the amp attribute - bail.
		if ( $html_el->hasAttribute( 'amp' ) ) {
			return true;
		}

		// Pattern copied from the official AMP project Repository: https://github.com/ampproject/amp-toolbox-php
		$pattern = '/<html\s([^>]*?(?:'
		           . "\xE2\x9A\xA1"
		           . '|'
		           . "\xE2\x9A\xA1\xEF\xB8\x8F"
		           . ')[^>]*?)>/i';

		preg_match( $pattern, $this->content, $emoji_matches );

		// Markup is AMP using the ⚡ symbol - bail.
		if ( ! empty( $emoji_matches ) ) {
			return true;
		}

		return false;
	}

	/**
	 * If the current server doesn't have DOMDocument defined, use a regex method to detect AMP.
	 *
	 * @since 2.5.6
	 *
	 * @return bool
	 */
	private function is_amp_regex() {
		// Bail if this markup is AMP'd
		preg_match( '/^<!DOCTYPE html>[\r\n]*<[\s]*html[\s]+[^>]*amp[=\s>]+/i', trim( $content ), $amatches );

		if ( ! empty( $amatches ) ) {
			return true;
		}
	}
}
