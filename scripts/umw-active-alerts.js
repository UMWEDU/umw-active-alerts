var UMWAlertsData = UMWAlertsData || {
	'localized' : false
};

if ( UMWAlertsData.localized ) {
	var UMWAlerts = UMWAlerts || {
		'ajaxurl' : UMWAlertsData.ajaxurl, 
		'av' : new Date().getTime(), 
		'do_ajax' : function() {
			jQuery.getJSON( UMWAlerts.ajaxurl, {
				'action' : 'check_global_advisories', 
				'v' : UMWAlerts.av, 
				'is_root' : UMWAlertsData.is_root, 
				'is_alerts' : UMWAlertsData.is_alerts, 
				'umwalerts_nonce' : UMWAlertsData.nonce
			}, function(e) {
				if ( 'alert' in e ) {
					UMWAlerts.doActiveAlert( e.alert );
				}
				if ( 'advisory' in e ) {
					// Only output on root site home page
					UMWAlerts.doActiveAdvisory( e.advisory );
				}
			} );
		}, 
		'doActiveAlert' : function( e ) {
			var t = '<aside class="emergency-alert">' + UMWAlerts.alertBody( e ) + '</aside>';
			jQuery( t ).prependTo( 'body' );
			return;
		}, 
		'doActiveAdvisory' : function( e ) {
			var t = '<aside class="campus-advisory">' + UMWAlerts.alertBody( e ) + '</aside>';
			/*if ( document.querySelectorAll( '.home-top .flexslider' ).length >= 1 ) {
				jQuery( t ).insertBefore( jQuery( '.home-top .flexslider' ) );
			} else */if ( document.querySelectorAll( '.site-header' ).length >= 1 ) {
				jQuery( t ).insertAfter( jQuery( '.site-header' ) );
			} else if ( document.querySelectorAll( '.umw-header-bar' ).length >= 1 ) {
				jQuery( t ).insertAfter( jQuery( '.umw-header-bar' ) );
			}
		}, 
		'alertBody' : function( e ) {
			return '<div class="wrap"><article class="alert"><header class="alert-heading"><h2><a href="' + e.url + '" title="Read the details of ' + e.title + '">' + e.title + '</a></h2></header>' + 
	/*'			<div class="alert-content">' +
	'				' + e.content + '' + 
	'			</div>' + */
	'			<footer class="alert-meta">Posted by <span class="alert-author">' + e.author + '</span> on <span class="alert-time">' + e.date + '</span></footer></article></div>';
		}
	};
	
	var UMWLocalAlerts = UMWLocalAlerts || {
		'av' : new Date().getTime(), 
		'do_ajax' : function() {
			jQuery.getJSON( UMWAlertsData.ajaxurl, {
				'action' : 'check_local_advisories', 
				'v' : UMWAlerts.av, 
				'is_root' : UMWAlertsData.is_root, 
				'is_alerts' : UMWAlertsData.is_alerts, 
				'umwalerts_nonce' : UMWAlertsData.nonce
			}, function(e) {
				if ( 'local' in e ) {
					UMWLocalAlerts.doLocalAlert( e.local );
				}
			} );
		}, 
		'doLocalAlert' : function( e ) {
			var t = '<aside class="' + e.class + '">' + UMWAlerts.alertBody( e ) + '</aside>';
			if ( document.querySelectorAll( '.content' ).length >= 1 ) {
				jQuery( t ).prependTo( '.content' );
			} else if ( document.querySelectorAll( '#content' ).length >= 1 ) {
				jQuery( t ).prependTo( '#content' );
			}
		}
	};
}

jQuery( function() {
	if ( UMWAlertsData.localized == false || jQuery( 'body' ).hasClass( 'site-advisories' ) ) {
		return;
	}
	
	UMWAlerts.do_ajax();
	
	if ( UMWAlertsData.dolocals ) {
		UMWLocalAlerts.do_ajax();
	}
} );