jQuery( function( $ ) {
	/**
	 * This is a stop-gap measure to see if someone is viewing the mobile home page of the root network
	 */
	function umwaa_is_mobile_home() {
/*		if ( typeof( console ) !== 'undefined' ) {
			console.log( 'Checking to see if this is the mobile home page' );
		}
*/		if ( document.location.pathname !== '/mobile/' || ( document.location.hostname.indexOf( 'www.' ) != 0 && document.location.hostname.indexOf( 'sfd-www.' ) != 0 ) ) {
/*			if ( typeof( console ) !== 'undefined' ) {
				console.log( document.location.hostname );
			}
*/			return false;
		}
/*		if ( typeof( console ) !== 'undefined' ) {
			console.log( 'This is the university mobile home page' );
		}
*/		return true;
	}
	/**
	 * Perform the JSON request to check alert status
	 */
	var umwaav = new Date().getTime();
	$.getJSON( umwActAlerts.ajaxurl, { 'action':'check_active_alert', 'v':umwaav }, function( result ) {
/*		if( typeof( console ) !== 'undefined' ) {
			console.log( result );
		}
*/		if ( 'alert' in result ) {
/*			if ( typeof( console ) !== 'undefined' ) {
				console.log( 'Getting ready to insert an alert' );
			}
*/			insert_active_alert( result.alert );
/*		} else if ( typeof( console ) !== 'undefined' ) {
			console.log( 'No alert was found' );
*/		}
		if ( 'emergency' in result ) {
/*			if ( typeof( console ) !== 'undefined' ) {
				console.log( 'Getting ready to insert an emergency alert' );
			}
*/			insert_emergency_alert( result.emergency );
/*		} else if ( typeof( console ) !== 'undefined' ) {
			console.log( 'No emergency alert was found' );
*/		}
	} );
	
	/**
	 * Insert an emergency alert, if defined
	 * Should occur on all pages throughout entire website
	 */
	function insert_emergency_alert( data ) {
	
		if( typeof( data.html ) === 'undefined' || -1 == data.html || '0' == data.html || null == data.html ) {
/*			if( typeof( console ) !== 'undefined' ) {
				console.log( 'No emergency alert defined' );
			}
*/			return;
		}
		
		var $umwh = $( data.html );
		if( $( '.home-top-left' ).length > 0 ) {
			$umwh.hide()
				.prependTo( $( '.home-top-left' ) )
				.fadeIn(1000);
		} else {
			if( $( '#wrap' ).length > 0 ) {
				$umwh.hide()
					.prependTo( $( '#wrap' ) )
					.fadeIn(1000);
				$('body').addClass( 'has-active-alert' );
			} else if( $( 'body' ).hasClass( 'wptouch-pro' ) ) {
				$umwh.hide()
					/*.prependTo( $( 'body' ) )*/
					.insertAfter( $( '#main-menu' ) )
					.fadeIn( 1000 );
				$('body').addClass( 'has-active-alert' );
			}
		}
	}
	
	/**
	 * Insert a non-emergency alert, if defined
	 * Should only occur on home page of desktop website
	 */
	function insert_active_alert( data ) {
		if( typeof( data.html ) === 'undefined' || -1 == data.html || '0' == data.html || null == data.html ) {
/*			if( typeof( console ) !== 'undefined' ) {
				console.log( 'No alert defined' );
			}
*/			return;
		}
		if ( $( '.home-top-left .slide-content' ).length <= 0 && !umwaa_is_mobile_home() ) {
/*			if ( typeof( console ) !== 'undefined' ) {
				console.log( 'We did not find .slide-content inside .home-top-left' );
			}
*/			return;
		}
		var $umwah = $( data.html );
		if ( umwaa_is_mobile_home() ) {
/*			if ( typeof( console ) !== 'undefined' ) {
				console.log( 'Preparing to insert the active non-emergency alert on the mobile site' );
			}
*/			$umwah.hide()
				/*.prependTo( $( 'body' ) )*/
				.insertAfter( $( '#main-menu' ) )
				.fadeIn( 1000 );
			$('body').addClass( 'has-active-alert' );
			return;
		}
		$umwah.hide().appendTo( $( '.home-top-left' ) ).fadeIn( 1000 );
/*		if ( typeof( console ) !== 'undefined' ) {
			console.log( $umwah );
			console.log( 'We should have just appended the object logged above to .home-top-left' );
		}
*/	}
} );