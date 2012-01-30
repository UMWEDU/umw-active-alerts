jQuery( function( $ ) {
	function insert_emergency_alert( data ) {
	
		if( typeof( data.html ) === 'undefined' || -1 == data.html || '0' == data.html || null == data.html ) {
			if( typeof( console ) !== 'undefined' ) {
				console.log( 'No emergency alert defined' );
			}
			return;
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
					.prependTo( $( 'body' ) )
					.fadeIn( 1000 );
				$('body').addClass( 'has-active-alert' );
			}
		}
	}
	
	function insert_active_alert( data ) {
		if( typeof( data.html ) === 'undefined' || -1 == data.html || '0' == data.html || null == data.html ) {
			if( typeof( console ) !== 'undefined' ) {
				console.log( 'No alert defined' );
			}
			return;
		}
		if ( $( '.home-top-left #eps-slideshow-caption' ).length <= 0 ) {
			if ( typeof( console ) !== 'undefined' ) {
				console.log( 'We did not find #eps-slideshow-caption inside .home-top-left' );
			}
			return;
		}
		var $umwah = $( data.html );
		$umwah.hide().appendTo( $( '.home-top-left' ) ).fadeIn( 1000 );
		if ( typeof( console ) !== 'undefined' ) {
			console.log( $umwah );
			console.log( 'We should have just appended the object logged above to .home-top-left' );
		}
	}
	
	$.getJSON( umwActAlerts.ajaxurl, {'action':'check_active_alert'}, function( result ) {
		if( typeof( console ) !== 'undefined' ) {
			console.log( result );
		}
		
		if ( 'emergency' in result ) {
			if ( typeof( console ) !== 'undefined' ) {
				console.log( 'Getting ready to insert an emergency alert' );
			}
			insert_emergency_alert( result.emergency );
		} else if ( typeof( console ) !== 'undefined' ) {
			console.log( 'No emergency alert was found' );
		}
		if ( 'alert' in result ) {
			if ( typeof( console ) !== 'undefined' ) {
				console.log( 'Getting ready to insert an alert' );
			}
			insert_active_alert( result.alert );
		} else if ( typeof( console ) !== 'undefined' ) {
			console.log( 'No alert was found' );
		}
	} );
} );