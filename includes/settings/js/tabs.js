window.addEventListener( 'load', function() {
	document.querySelectorAll( '.gform-settings-tabs__navigation a' ).forEach( function( tab ) {
		tab.addEventListener( 'click', function ( e ) {

			e.preventDefault();

			// Get selected tab.
			var selectedTab = e.target.dataset.tab;

			// Hide active tab.
			document.querySelectorAll( '.gform-settings-tabs__navigation .active, .gform-settings-tabs__container.active' ).forEach( function( item ) {
				item.classList.remove( 'active' );
			} );

			// Set selected tab to active tab input.
			document.querySelector( 'input[name="gform_settings_tab"]' ).value = selectedTab;

			// Show selected tab.
			e.target.classList.add( 'active' );
			document.querySelector( '.gform-settings-tabs__container[data-tab="' + selectedTab + '"]' ).classList.add( 'active' );

		} );
	} );
} );