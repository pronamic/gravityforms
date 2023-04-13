function setDefaultThemeData() {
	const propsSet = window.localStorage.getItem( 'custom-gf-doc-theme-props-set' );
	if ( ! propsSet ) {
		window.localStorage.setItem( 'font-size', '14' );
		window.localStorage.setItem( 'custom-gf-doc-theme-props-set', '1' );
	}
}

setDefaultThemeData();

( function() {
	function docReady( fn ) {
		if ( document.readyState === "complete" || document.readyState === "interactive" ) {
			// call on next available tick
			setTimeout( fn, 1 );
		} else {
			document.addEventListener( "DOMContentLoaded", fn );
		}
	}

	function setTitle() {
		const url = window.location.pathname;
		const filename = url.substring( url.lastIndexOf( '/' ) + 1 );
		const nameParts = filename.split( '.' );
		if ( nameParts[ 0 ].indexOf( 'module-' ) !== -1 ) {
			const header = document.querySelector( '.main-wrapper > section > header' );
			if ( header ) {
				header.insertAdjacentHTML( 'afterbegin', `<h1>${ nameParts[ 0 ].replace( 'module-', '' ) }</h1>` );
			}
		}
	}

	function modifyNavItems() {
		const items = document.querySelectorAll( '.gform-docs-nav-item a' );
		const href = document.location.href;
		items.forEach( ( item ) => {
			if ( href.includes( item.id ) ) {
				item.classList.add( 'current' );
			}
		} );
	}

	function fadeInContent() {
		const content = document.querySelector( '.main-wrapper' );
		if ( content ) {
			content.classList.add( 'reveal-content' );
		}
	}

	function init() {
		setTitle();
		modifyNavItems();
		fadeInContent();
	}

	docReady( init );
} )();
