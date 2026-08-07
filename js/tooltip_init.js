jQuery( function() {
	gform_initialize_tooltips();
} );

function gform_initialize_tooltips() {
	var $tooltips = jQuery( '.gf_tooltip' );
	if ( ! $tooltips.length ) {
		return;
	}

	$tooltips.tooltip( {
		show: {
			effect: 'fadeIn',
			duration: 200,
			delay: 100,
		},
		position:     {
			my: 'center bottom',
			at: 'center-3 top-11',
		},
		tooltipClass: 'arrow-bottom',
		items: '[aria-label]',
		content: function () {
			var content = jQuery( this ).attr( 'aria-label' );
			return gform_sanitize_tooltip_html( content );
		},
		open:         function ( event, ui ) {
			if ( typeof ( event.originalEvent ) === 'undefined' ) {
				return false;
			}

			// set the tooltip offset on reveal based on tip width and offset of trigger to handle dynamic changes in overflow
			setTimeout( function() {
				var leftOffset = ( this.getBoundingClientRect().left - ( ( ui.tooltip[0].offsetWidth / 2 ) - 5 ) ).toFixed(3);
				ui.tooltip.css( 'left', leftOffset + 'px' );
			}.bind( this ), 100 );


			var $id = ui.tooltip.attr( 'id' );
			jQuery( 'div.ui-tooltip' ).not( '#' + $id ).remove();
		},
		close:        function ( event, ui ) {
			ui.tooltip.hover( function () {
					jQuery( this ).stop( true ).fadeTo( 400, 1 );
				},
				function () {
					jQuery( this ).fadeOut( '500', function () {
						jQuery( this ).remove();
					} );
				} );
		}
	} );
}

/**
 * Sanitizes tooltip HTML using a strict allow-list.
 *
 * @since 3.0.0
 *
 * @param {string} content The HTML content to sanitize.
 *
 * @return {string}
 */
function gform_sanitize_tooltip_html( content ) {
	var parser = new DOMParser();
	var body = parser.parseFromString( content || '', 'text/html' ).body;
	var allowedTags = [ 'A', 'B', 'BR', 'CODE', 'DIV', 'EM', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'I', 'P', 'SPAN', 'STRONG' ];
	var discardTags = [ 'BUTTON', 'EMBED', 'FORM', 'IFRAME', 'IMG', 'INPUT', 'LINK', 'MATH', 'META', 'OBJECT', 'OPTION', 'SCRIPT', 'SELECT', 'STYLE', 'SVG', 'TEMPLATE', 'TEXTAREA' ];

	Array.prototype.slice.call( body.querySelectorAll( '*' ) ).forEach( function( element ) {
		if ( ! element.parentNode ) {
			return;
		}

		if ( discardTags.indexOf( element.tagName ) !== -1 ) {
			element.parentNode.removeChild( element );
			return;
		}

		if ( allowedTags.indexOf( element.tagName ) === -1 ) {
			while ( element.firstChild ) {
				element.parentNode.insertBefore( element.firstChild, element );
			}

			element.parentNode.removeChild( element );
			return;
		}

		gform_sanitize_tooltip_attributes( element );
	} );

	return body.innerHTML;
}

/**
 * Removes unsafe attributes from an allowed tooltip HTML element.
 *
 * @since 3.0.0
 *
 * @param {Element} element The element to sanitize.
 */
function gform_sanitize_tooltip_attributes( element ) {
	var href = element.getAttribute( 'href' );
	var target = element.getAttribute( 'target' );
	var title = element.getAttribute( 'title' );
	var className = element.getAttribute( 'class' );
	var ariaHidden = element.getAttribute( 'aria-hidden' );
	var normalizedHref = href ? href.replace( /[\u0000-\u001F\u007F\s]+/g, '' ).toLowerCase() : '';

	Array.prototype.slice.call( element.attributes ).forEach( function( attribute ) {
		element.removeAttribute( attribute.name );
	} );

	if ( element.tagName === 'A' ) {
		if ( href && ( normalizedHref.indexOf( '#' ) === 0 || ! /^[a-z][a-z0-9+.-]*:/.test( normalizedHref ) || /^(https?:|mailto:|tel:)/.test( normalizedHref ) ) ) {
			element.setAttribute( 'href', href );
		}

		target = target ? target.toLowerCase() : '';

		if ( target === '_blank' || target === '_self' ) {
			element.setAttribute( 'target', target );

			if ( target === '_blank' ) {
				element.setAttribute( 'rel', 'noopener' );
			}
		}

		if ( title ) {
			element.setAttribute( 'title', title );
		}
	}

	if ( className ) {
		element.setAttribute( 'class', className );
	}

	if ( ariaHidden === 'true' || ariaHidden === 'false' ) {
		element.setAttribute( 'aria-hidden', ariaHidden );
	}
}

/**
 * Sanitizes tooltip HTML.
 *
 * @param {string} content The HTML content to sanitize.
 *
 * @return {string}
 */
function gform_strip_scripts( content ) {
	return gform_sanitize_tooltip_html( content );
}

function gform_system_shows_scrollbars() {
	var parent = document.createElement("div");
	parent.setAttribute("style", "width:30px;height:30px;");
	parent.classList.add('scrollbar-test');

	var child = document.createElement("div");
	child.setAttribute("style", "width:100%;height:40px");
	parent.appendChild(child);
	document.body.appendChild(parent);

	var scrollbarWidth = 30 - parent.firstChild.clientWidth;

	document.body.removeChild(parent);

	return scrollbarWidth ? true : false;
}
