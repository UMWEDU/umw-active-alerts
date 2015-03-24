var UMWAlerts = UMWAlerts || {
	'av' : new Date().getTime(),
	
	/**
	 * Create a console logging function
	 */
	'log' : function( m ) {
		return;
		
		if ( typeof( console ) === 'undefined' ) {
			return;
		}
		console.log( m );
	}, 
	
	/**
	 * Most likely obsolete at this point
	 */
	'is_mobile_home' : function() {
		if ( document.location.pathname !== '/mobile/' || ( document.location.hostname.indexOf( 'www.' ) != 0 && document.location.hostname.indexOf( 'sfd-www.' ) != 0 ) ) {
			return false;
		}
		return true;
	}, 
	
	/**
	 * Perform the JSON request to check alert status
	 */
	'do_ajax' : function() {
		jQuery.getJSON( umwActAlerts.ajaxurl, { 
			'action' : 'check_active_alert', 
			'v' : this.av 
		}, function( result ) {
			if ( 'alert' in result ) {
				UMWAlerts.insert_active_alert( result.alert );
			}
			if ( 'emergency' in result ) {
				UMWAlerts.insert_emergency_alert( result.emergency );
			}
		} );
	}, 
	
	/**
	 * Insert an emergency alert, if defined
	 * Should occur on all pages throughout entire website
	 */
	'insert_emergency_alert' : function( data ) {
		this.log( 'Attempting to enter the insert_emergency_alert function' );
		if( typeof( data.html ) === 'undefined' || -1 == data.html || '0' == data.html || null == data.html ) {
			return;
		}
		
		var $umwh = jQuery( data.html );
		/**
		 * As a first resort, let's add the alert above the tools bar
		 */
		if ( this.exists( 'body > .umw-helpful-links' ) ) {
			this.log( 'The helpful links toolbar was found, so we will insert the alert at the beginning of body' );
			jQuery( 'body' ).prepend( $umwh );
			return;
		} else {
			this.log( 'The helpful links toolbar was not found, so we are moving on to other options' );
		}
		
		if( this.exists( '.home-top-left' ) ) {
			$umwh.hide()
				.prependTo( jQuery( '.home-top-left' ) )
				.fadeIn(1000);
		} else {
			if( this.exists( '#wrap' ) ) {
				$umwh.hide()
					.prependTo( jQuery( '#wrap' ) )
					.fadeIn(1000);
				jQuery('body').addClass( 'has-active-alert' );
			} else if( jQuery( 'body' ).hasClass( 'wptouch-pro' ) ) {
				$umwh.hide()
					.insertAfter( jQuery( '#main-menu' ) )
					.fadeIn( 1000 );
				jQuery('body').addClass( 'has-active-alert' );
			}
		}
	}, 
	
	/**
	 * Insert a non-emergency alert, if defined
	 * Should only occur on home page of desktop website
	 */
	'insert_active_alert' : function( data ) {
		if( typeof( data.html ) === 'undefined' || -1 == data.html || '0' == data.html || null == data.html ) {
			return;
		}
		
		/**
		 * If this isn't the root site of the root network, bail out
		 */
		if ( ( ! jQuery( 'body' ).hasClass( 'network-www.umw.wtf' ) && ! jQuery( 'body' ).hasClass( 'network-www.umw.red' ) && ! jQuery( 'body' ).hasClass( 'network-www.umw.edu' ) ) || ! jQuery( 'body' ).hasClass( 'site-root' ) ) {
			this.log( 'The body did not appear to be at the root of the install, so we are bailing on inserting a non-emergency alert.' );
			return;
		}
		/*if ( this.exists( '.home-top-left .slide-content' ) === false && !umwaa_is_mobile_home() ) {
			return;
		}*/
		
		var $umwah = jQuery( data.html );
		
		/**
		 * As a first resort, let's add the alert below the header bar
		 */
		if ( this.exists( 'body > .umw-header-bar' ) ) {
			jQuery( $umwah ).insertAfter( 'body > .umw-header-bar' );
			return;
		}
		/**
		 * As a second resort, let's add it after the tools bar
		 */
		if ( this.exists( 'body > .umw-helpful-links' ) ) {
			jQuery( $umwah ).insertAfter( jQuery( 'body > .umw-helpful-links' ) );
			return;
		}
		
		if ( umwaa_is_mobile_home() ) {
			$umwah.hide()
				.insertAfter( jQuery( '#main-menu' ) )
				.fadeIn( 1000 );
			jQuery('body').addClass( 'has-active-alert' );
			return;
		}
		$umwah.hide().appendTo( jQuery( '.home-top-left' ) ).fadeIn( 1000 );
	}, 
	
	/**
	 * Perform any initation actions that need to occur
	 */
	'init' : function() {
		return this.do_ajax();
	}, 
	
	/**
	 * Check to see if an element exists in the DOM
	 */
	'exists' : function( e ) {
		return document.querySelectorAll( e ).length > 0;
	}
};

jQuery( function() {
	return UMWAlerts.init();
} );