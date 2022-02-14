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
			return gform_strip_scripts( content );
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
 * Sanitizes a given piece of HTML markup by removing script tags from it.
 *
 * @param {string} content The HTML content to sanitize.
 *
 * @return {string}
 */
function gform_strip_scripts( content ) {
	var tempWrapper = document.createElement( 'div' );

	tempWrapper.innerHTML = content;

	var scripts = tempWrapper.getElementsByTagName( 'script' );

	for ( var i = 0; i < scripts.length; i++ ) {
		scripts[ i ].parentNode.removeChild( scripts[ i ] );
	}

	return tempWrapper.innerHTML;
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
