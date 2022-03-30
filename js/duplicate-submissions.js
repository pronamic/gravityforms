/**
 * Provides functionality to allow browsers to re-submit forms without creating duplicate submissions.
 */
(function() {

	var config = window.gf_duplicate_submissions || {};

	/**
	 * Check if the current browser is Safari.
	 *
	 * @returns {boolean}
	 */
	var isSafari = function() {
		var ua                  = window.navigator.userAgent;
		var iOS                 = !!ua.match( /iP(ad|od|hone)/i );
		var hasSafariInUa       = !!ua.match( /Safari/i );
		var noOtherBrowsersInUa = !ua.match( /Chrome|CriOS|OPiOS|mercury|FxiOS|Firefox/i )
		var result              = false;

		if ( iOS ) { //detecting Safari in IOS mobile browsers
			var webkit = !!ua.match( /WebKit/i );
			result     = webkit && hasSafariInUa && noOtherBrowsersInUa;
		} else if ( window.safari !== undefined ) { //detecting Safari in Desktop Browsers
			result = true;
		} else { // detecting Safari in other platforms
			result = hasSafariInUa && noOtherBrowsersInUa;
		}

		return result;
	};

	/**
	 * Update a Query Var based on the provided key/value.
	 *
	 * @param {string} key   The key to update.
	 * @param {string} value The value to which the key should be updated.
	 * @param {string} url   The URL to update.
	 *
	 * @returns {string}
	 */
	var updateQueryVar = function( key, value, url ) {
		var separator = '?';

		var hashSplit  = url.split( '#' ),
		    hash       = hashSplit[ 1 ] ? '#' + hashSplit[ 1 ] : '',
		    querySplit = hashSplit[ 0 ].split( '?' ),
		    host       = querySplit[ 0 ],
		    query      = querySplit[ 1 ],
		    params     = query !== undefined ? query.split( '&' ) : [],
		    updated    = false;

		for ( var index = 0; index < params.length; index++ ) {
			var item = params[ index ];

			// No need to process this parameter since it doesn't match the one we're updating.
			if ( ! item.startsWith( key + '=' ) ) {
				continue;
			}

			// Update the param if the value is non-empty, otherwise remove it.
			if ( value.length > 0 ) {
				params[ index ] = key + '=' + value;
			} else {
				params.splice( index, 1 );
			}

			updated = true;
		}

		// Param didn't already exist; if the value is non-empty, add it to the param array.
		if ( ! updated && value.length > 0 ) {
			params[ params.length ] = key + '=' + value;
		}

		var queryString = params.join( '&' );

		return host + separator + queryString + hash;
	};

	/**
	 * Get the properly-formatted URL for redirects.
	 *
	 * @returns {string}
	 */
	var getFormattedURL = function() {
		var baseUrl = updateQueryVar( config.safari_redirect_param, '', window.location.href );
		var safariUrl = updateQueryVar( config.safari_redirect_param, '1', window.location.href );

		console.log( baseUrl, safariUrl );

		return isSafari() ? safariUrl : baseUrl;
	};

	/**
	 * Replace the current history state to avoid duplicate submissions.
	 */
	var handleReplaceState = function() {
		window.history.replaceState( null, null, getFormattedURL() );
	};

	/**
	 * Initialize.
	 */
	var init = function() {
		if ( window.gf_duplicate_submissions_initialized || config.is_gf_submission !== '1' || !window.history.replaceState ) {
			return;
		}

		window.gf_duplicate_submissions_initialized = true;

		handleReplaceState();
	};

	init();
})();
