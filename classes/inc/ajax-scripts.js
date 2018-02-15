jQuery( function( $ ) {
    advisoriesObject.formatDate = function( date ) {
        var year = date.getFullYear();
        var month = ( date.getMonth() + 1 );
        var day = date.getDate();
        var hour = date.getHours();
        var min = date.getMinutes();

        month = ( '00' + month ).substr( -2 );
        day = ( '00' + day ).substr( -2 );
        hour = ( '00' + hour ).substr( -2 );
        min = ( '00' + min ).substr( -2 );

        return year + '-' + month + '-' + day + ' ' + hour + ':' + min + ':00';
    };
    advisoriesObject.humanDate = function( date ) {
        var monthNames = ['Jan.', 'Feb.', 'March', 'April', 'May', 'June', 'July', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'];
        var year = date.getFullYear();
        var month = date.getMonth();
        var day = date.getDate();
        var hour = date.getHours();
        var min = date.getMinutes();

        var ap = ( hour >= 12 ) ? 'p.m.' : 'a.m.';
        if ( hour == 0 ) {
            hour = 12;
        } else if ( hour > 12 ) {
            hour = ( hour - 12 );
        }

        return monthNames[month] + ' ' + day + ', ' + year + ' at ' + hour + ':' + min + ' ' + ap;
    }
    advisoriesObject.doLocalAlert = function() {
        var args = {
            'orderby' : 'meta_value_num',
            'order' : 'desc',
            'meta_key' : '_advisory_expires_time',
            'per_page' : 1,
            '_embed' : 1
        };
        jQuery.ajax( { 'url' : advisoriesObject.local_url, 'data' : args, 'success' : function( data ) { console.log( data ); }, 'error' : function( xhr, status, error ) { console.log( error ); }, 'dataType' : 'json' } );
        jQuery.get( advisoriesObject.local_url, args, function( data, status ) { advisoriesObject.log( data ) }, 'jsonp' );
    };
    advisoriesObject.doGlobalAlert = function() {
        var args = {
            'orderby' : 'meta_value_num',
            'order' : 'desc',
            'meta_key' : '_advisory_expires_time',
            'per_page' : 1,
            '_embed' : 1
        };
        jQuery.ajax( { 'url' : advisoriesObject.alerts_url, 'data' : args, 'success' : function( data ) { console.log( data ); }, 'error' : function( xhr, status, error ) { console.log( xhr ); console.log( status ); console.log( error ); }, 'dataType' : 'json' } );
    }
    advisoriesObject.insertLocalAlert = function( e ) {
        var alert = e[0];
        var author = alert._embedded.author[0].name;
        if ( 'meta' in alert ) {
            if ( '_advisory_author' in alert.meta ) {
                author = alert.meta._advisory_author;
            }
        }
        var date = new Date( alert.date );
        date = advisoriesObject.humanDate( date );
        var data = {
            'url' : e.link,
            'title' : e.title.rendered,
            'author' : author,
            'date' : date;
        }

        advisoriesObject.alertBody( data );

        advisoriesObject.log( e );
        return false;
    };
    advisoriesObject.insertGlobalAlert = function( e ) {
        advisoriesObject.log( e );
        return false;
    };
    advisoriesObject.log = function( m ) {
        if ( typeof console === 'undefined' )
            return;

        advisoriesObject.log( m );
    };
    advisoriesObject.alertBody = function( e ) {
        return '<div class="wrap"><article class="alert"><header class="alert-heading"><h2><a href="' + e.url + '" title="Read the details of ' + e.title + '">' + e.title + '</a></h2></header>' +
            '<footer class="alert-meta">Posted by <span class="alert-author">' + e.author + '</span> on <span class="alert-time">' + e.date + '</span></footer></article></div>';
    }

    advisoriesObject.now = new Date();
    advisoriesObject.nowFormatted = advisoriesObject.formatDate( advisoriesObject.now );
    if ( advisoriesObject.is_alerts || advisoriesObject.is_root ) {
        advisoriesObject.doGlobalAlert();
    } else {
        advisoriesObject.doLocalAlert();
    }
} );