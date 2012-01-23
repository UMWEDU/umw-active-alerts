jQuery( function( $ ) {
	$.getJSON( umwActAlerts.ajaxurl, {'action':'check_active_alert'}, function( data ) {
		if( typeof( console ) !== 'undefined' ) {
			console.log( data );
		}
		
		if( typeof( data.html ) === 'undefined' || -1 == data.html || '0' == data.html ) {
			if( typeof( console ) !== 'undefined' ) {
				console.log( 'No alert defined' );
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
	} );
} );