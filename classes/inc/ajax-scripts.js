;jQuery( function( $ ) {
    var advisoriesFunctions = {
        'init' : function() {
            if ( this.is_alerts || this.is_root ) {
                this.doGlobalAlert();
            } else {
                this.doLocalAlert();
            }
        },
        'formatDate' : function( date ) {
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
        },
        'humanDate' : function( date ) {
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
        },
        'doLocalAlert' : function() {
            var args = {
                'orderby' : 'meta_value_num',
                'order' : 'desc',
                'meta_key' : '_advisory_expires_time',
                'per_page' : 1,
                '_embed' : 1
            };
            jQuery.ajax( { 'url' : this.local_url, 'data' : args, 'success' : function( data ) { advisoriesFunctions.log( data ); }, 'error' : function( xhr, status, error ) { advisoriesFunctions.log( error ); }, 'dataType' : 'json' } );
            jQuery.get( this.local_url, args, function( data, status ) { advisoriesFunctions.log( data ) }, 'json' );
        },
        'doGlobalAlert' : function() {
            var args = {
                'orderby' : 'meta_value_num',
                'order' : 'desc',
                'meta_key' : '_advisory_expires_time',
                'per_page' : 1,
                '_embed' : 1
            };
            jQuery.ajax( { 'url' : this.alerts_url, 'data' : args, 'success' : function( data ) { advisoriesFunctions.log( data ); }, 'error' : function( xhr, status, error ) { advisoriesFunctions.log( xhr ); advisoriesFunctions.log( status ); advisoriesFunctions.log( error ); }, 'dataType' : 'json' } );
        },
        'insertLocalAlert' : function( e ) {
            var alert = e[0];
            var author = alert._embedded.author[0].name;
            if ( 'meta' in alert ) {
                if ( '_advisory_author' in alert.meta ) {
                    author = alert.meta._advisory_author;
                }
            }
            var date = new Date( alert.date );
            date = this.humanDate( date );
            var data = {
                'url' : e.link,
                'title' : e.title.rendered,
                'author' : author,
                'date' : date
            }

            this.alertBody( data );

            this.log( e );
            return false;
        },
        'insertGlobalAlert' : function( e ) {
            this.log( e );
            return false;
        },
        'log' : function( m ) {
            if ( typeof console === 'undefined' )
                return;

            console.log( m );
        },
        'alertBody' : function( e ) {
            return '<div class="wrap"><article class="alert"><header class="alert-heading"><h2><a href="' + e.url + '" title="Read the details of ' + e.title + '">' + e.title + '</a></h2></header>' +
                '<footer class="alert-meta">Posted by <span class="alert-author">' + e.author + '</span> on <span class="alert-time">' + e.date + '</span></footer></article></div>';
        },
        'now' : new Date(),
        'nowFormatted' : this.formatDate( this.now ),
        'alerts_url' : advisoriesObject.alerts_url,
        'local_url' : advisoriesObject.local_url,
        'is_root' : advisoriesObject.is_root,
        'is_alerts' : advisoriesObject.is_alerts
    };

    advisoriesFunctions.init();
} );