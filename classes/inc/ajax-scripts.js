;jQuery( function( $ ) {
    var advisoriesFunctions = {
        'init' : function() {
            this.nowFormatted = this.formatDate( this.now );
            if ( this.is_alerts || this.is_root ) {
                this.doGlobalAlert();
            } else {
                this.doLocalAlert();
            }

            this.doGlobalEmergency();
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
        'enqueueStyles' : function() {
            var link = document.createElement( 'link' );
            link.href = this.css_url;
            link.type = 'text/css';
            link.rel = 'stylesheet';
            link.media = 'all';

            this.did_css = true;

            document.getElementsByTagName( 'head' )[0].appendChild( link );
        },
        'getQueryArgs' : function() {
            return {
                'orderby' : 'meta_value_num',
                'order' : 'desc',
                'meta_key' : '_advisory_expires_time',
                'per_page' : 1,
                '_embed' : 1
            };
        },
        'gatherAlertInfo' : function( e ) {
            if ( typeof e !== 'object' || e.length < 1 ) {
                this.log( typeof e );
                this.log( 'The alert info does not appear to be an array' );
                this.log( e );
                return '';
            }

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
                'url' : alert.link,
                'title' : alert.title.rendered,
                'author' : author,
                'date' : date
            };

            return this.alertBody( data );
        },
        /* Site-specific alert */
        'doLocalAlert' : function() {
            jQuery.ajax( {
                'url' : this.local_url,
                'data' : this.getQueryArgs(),
                'success' : function( data ) {
                    return advisoriesFunctions.insertLocalAlert( data );
                },
                'error' : function( xhr, status, error ) {
                    advisoriesFunctions.log( 'Failed retrieving local alert' );
                    advisoriesFunctions.log( error );
                    advisoriesFunctions.log( advisoriesFunctions.local_url );
                    advisoriesFunctions.log( advisoriesFunctions.getQueryArgs() );
                },
                'dataType' : 'json'
            } );
        },
        /* Non-emergency campus-wide alert */
        'doGlobalAlert' : function() {
            jQuery.ajax( {
                'url' : this.alerts_url,
                'data' : this.getQueryArgs(),
                'success' : function( data ) {
                    return advisoriesFunctions.insertGlobalAdvisory( data );
                },
                'error' : function( xhr, status, error ) {
                    advisoriesFunctions.log( 'Failed retrieving global alert' );
                    advisoriesFunctions.log( error );
                    advisoriesFunctions.log( advisoriesFunctions.alerts_url );
                    advisoriesFunctions.log( advisoriesFunctions.getQueryArgs() );
                },
                'dataType' : 'json'
            } );
        },
        /* Emergency campus-wide alert */
        'doGlobalEmergency' : function() {
            jQuery.ajax( {
                'url' : this.emergency_url,
                'data' : this.getQueryArgs(),
                'success' : function( data ) {
                    return advisoriesFunctions.insertGlobalAlert( data );
                },
                'error' : function( xhr, status, error ) {
                    advisoriesFunctions.log( 'Failed retrieving global emergency' );
                    advisoriesFunctions.log( error );
                    advisoriesFunctions.log( advisoriesFunctions.emergency_url );
                    advisoriesFunctions.log( advisoriesFunctions.getQueryArgs() );
                },
                'dataType' : 'json'
            } );
        },
        /* Site-specific alerts */
        'insertLocalAlert' : function( e ) {
            body = this.wrapLocalAlert( this.gatherAlertInfo( e ) );
            if ( false === body ) {
                return false;
            }

            this.log( e );
            this.log( body );

            if ( document.querySelectorAll( '.content' ).length >= 1 ) {
                jQuery( body ).prependTo( '.content' );
            } else if ( document.querySelectorAll( '#content' ).length >= 1 ) {
                jQuery( body ).prependTo( '#content' );
            }

            return false;
        },
        /* Emergency campus-wide alerts */
        'insertGlobalAlert' : function( e ) {
            var body = this.wrapGlobalEmergency( this.gatherAlertInfo( e ) );
            if ( false === body ) {
                return false;
            }

            this.log( e );
            this.log( body );
            jQuery( body ).prependTo( 'body' );

            return false;
        },
        /* Non-emergency campus-wide alerts */
        'insertGlobalAdvisory' : function( e ) {
            var body = this.wrapGlobalAlert( this.gatherAlertInfo( e ) );
            if ( false === body ) {
                return false;
            }

            this.log( e );
            this.log( body );
            if ( document.querySelectorAll( '.site-header' ).length >= 1 ) {
                jQuery( body ).insertAfter( jQuery( '.site-header' ) );
            } else if ( document.querySelectorAll( '.umw-header-bar' ).length >= 1 ) {
                jQuery( body ).insertAfter( jQuery( '.umw-header-bar' ) );
            }
        },
        'wrapLocalAlert' : function( body ) {
            if ( body === '' ) {
                return false;
            }

            return '<aside class="' + e.class + '">' + body + '</aside>';
        },
        'wrapGlobalEmergency' : function( body ) {
            if ( body === '' ) {
                return false;
            }

            return '<aside class="emergency-alert">' + body + '</aside>';
        },
        'wrapGlobalAlert' : function( body ) {
            if ( body === '' ) {
                return false;
            }

            return '<aside class="campus-advisory">' + body + '</aside>';
        },
        'log' : function( m ) {
            if ( typeof console === 'undefined' )
                return;

            console.log( m );
        },
        'alertBody' : function( e ) {
            if ( false === this.did_css ) {
                this.enqueueStyles();
            }

            return '<div class="wrap"><article class="alert"><header class="alert-heading"><h2><a href="' + e.url + '" title="Read the details of ' + e.title + '">' + e.title + '</a></h2></header>' +
                '<footer class="alert-meta">Posted by <span class="alert-author">' + e.author + '</span> on <span class="alert-time">' + e.date + '</span></footer></article></div>';
        },
        'now' : new Date(),
        'alerts_url' : advisoriesObject.alerts_url,
        'local_url' : advisoriesObject.local_url,
        'emergency_url' : advisoriesObject.emergency_url,
        'is_root' : advisoriesObject.is_root,
        'is_alerts' : advisoriesObject.is_alerts,
        'css_url' : advisoriesObject.css_url,
        'did_css' : false
    };

    advisoriesFunctions.init();
} );