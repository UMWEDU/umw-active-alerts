;jQuery(function ($) {
    var advisoriesFunctions = {
        'init': function () {
            this.nowFormatted = this.formatDate(this.now);
            if (this.is_alerts || (this.is_root && this.is_front_page)) {
                this.doGlobalAlert();
            } else {
                this.doLocalAlert();
            }

            this.doGlobalEmergency();
        },
        'formatDate': function (date) {
            var iso = date.toISOString().match(/(\d{4}\-\d{2}\-\d{2})T(\d{2}:\d{2}:\d{2})/);
            return iso[1] + ' ' + iso[2];
        },
        'humanDate': function (date) {
            var fullDate = date.toLocaleString( 'en-US', { timeZone: 'America/New_York', dateStyle: 'long', timeStyle: 'short' } );
            var monthNames = {
                January: 'Jan.',
                February: 'Feb.',
                /*March: 'March',
                April: 'April',
                May: 'May',
                June: 'June',
                July: 'July',*/
                August: 'Aug.',
                September: 'Sept.',
                October: 'Oct.',
                November: 'Nov.',
                December: 'Dec.',
                PM: 'p.m.',
                AM: 'a.m.',
            };

            for ( var i in monthNames ) {
                fullDate = fullDate.replace( i, monthNames[i] );
            }

            return fullDate;
        },
        'enqueueStyles': function () {
            var link = document.createElement('link');
            link.href = this.css_url;
            link.type = 'text/css';
            link.rel = 'stylesheet';
            link.media = 'all';

            this.did_css = true;

            document.getElementsByTagName('head')[0].appendChild(link);
        },
        'getQueryArgs': function () {
            return {
                'orderby': 'date',
                'order': 'desc',
                'meta_query': {
                    'expires': {
                        'key': '_advisory_expires_time',
                        'value': 'NOW',
                        'type': 'DATETIME',
                        'compare': '>'
                    }
                },
                'per_page': 1,
                '_embed': 1
            };
        },
        'getCurrentTime': function () {
            var today = new Date();
            var year = today.getFullYear();
            var month = today.getMonth();
            var d = today.getDate();
            var h = today.getHours();
            var m = today.getMinutes();
            var s = today.getSeconds();

            month = ('0' + (month + 1)).slice(-2);
            m = ('0' + m).slice(-2);
            s = ('0' + s).slice(-2);

            return year + '-' + month + '-' + d + ' ' + h + ':' + m + ':' + s;
        },
        'gatherAlertInfo': function (e) {
            if (typeof e !== 'object' || e.length < 1) {
                this.log(typeof e);
                this.log('The alert info does not appear to be an array');
                this.log(e);
                return '';
            }

            var alert = e[0];
            var author = alert._embedded.author[0].name;
            if ('meta' in alert) {
                if ('_advisory_author' in alert.meta && alert.meta._advisory_author != '') {
                    author = alert.meta._advisory_author;
                }
            }
            var dateString = alert.date + '.000-04:00';

            this.log(dateString);

            var date = new Date(dateString);
            date = this.humanDate(date);
            var data = {
                'url': alert.link,
                'title': alert.title.rendered,
                'author': author,
                'date': date,
                'showmeta': alert.meta._advisory_meta_include
            };

            return this.alertBody(data);
        },
        /* Site-specific alert */
        'doLocalAlert': function () {
            jQuery.ajax({
                'url': this.local_url,
                'data': this.getQueryArgs(),
                'success': function (data) {
                    advisoriesFunctions.log('Retrieved local alert information from ' + advisoriesFunctions.local_url);
                    advisoriesFunctions.log(advisoriesFunctions.getQueryArgs());
                    advisoriesFunctions.log(data);
                    return advisoriesFunctions.insertLocalAlert(data);
                },
                'error': function (xhr, status, error) {
                    advisoriesFunctions.log('Failed retrieving local alert');
                    advisoriesFunctions.log(error);
                    advisoriesFunctions.log(advisoriesFunctions.local_url);
                    advisoriesFunctions.log(advisoriesFunctions.getQueryArgs());
                },
                'dataType': 'json'
            });
        },
        /* Non-emergency campus-wide alert */
        'doGlobalAlert': function () {
            jQuery.ajax({
                'url': this.alerts_url,
                'data': this.getQueryArgs(),
                'success': function (data) {
                    advisoriesFunctions.log('Retrieved global alert information from ' + advisoriesFunctions.alerts_url);
                    advisoriesFunctions.log(advisoriesFunctions.getQueryArgs());
                    advisoriesFunctions.log(data);
                    return advisoriesFunctions.insertGlobalAdvisory(data);
                },
                'error': function (xhr, status, error) {
                    advisoriesFunctions.log('Failed retrieving global alert');
                    advisoriesFunctions.log(error);
                    advisoriesFunctions.log(advisoriesFunctions.alerts_url);
                    advisoriesFunctions.log(advisoriesFunctions.getQueryArgs());
                },
                'dataType': 'json'
            });
        },
        /* Emergency campus-wide alert */
        'doGlobalEmergency': function () {
            jQuery.ajax({
                'url': this.emergency_url,
                'data': this.getQueryArgs(),
                'success': function (data) {
                    advisoriesFunctions.log('Retrieved global emergency information from ' + advisoriesFunctions.emergency_url);
                    advisoriesFunctions.log(advisoriesFunctions.getQueryArgs());
                    advisoriesFunctions.log(data);
                    return advisoriesFunctions.insertGlobalAlert(data);
                },
                'error': function (xhr, status, error) {
                    advisoriesFunctions.log('Failed retrieving global emergency');
                    advisoriesFunctions.log(error);
                    advisoriesFunctions.log(advisoriesFunctions.emergency_url);
                    advisoriesFunctions.log(advisoriesFunctions.getQueryArgs());
                },
                'dataType': 'json'
            });
        },
        /* Site-specific alerts */
        'insertLocalAlert': function (e) {
            body = this.wrapLocalAlert(this.gatherAlertInfo(e));
            if (false === body) {
                return false;
            }

            this.log(e);
            this.log(body);

            if (document.querySelectorAll('.content').length >= 1) {
                if (document.querySelectorAll('.umw-global-header').length > 0) {
                    jQuery(body).prependTo('.content');
                } else if (document.querySelectorAll('.site-inner').length > 0) {
                    jQuery(body).prependTo('.site-inner');
                } else {
                    jQuery(body).prependTo('.content');
                }
            } else if (document.querySelectorAll('#content').length >= 1) {
                jQuery(body).prependTo('#content');
            }

            return false;
        },
        /* Emergency campus-wide alerts */
        'insertGlobalAlert': function (e) {
            var body = this.wrapGlobalEmergency(this.gatherAlertInfo(e));
            if (false === body) {
                return false;
            }

            this.log(e);
            this.log(body);
            jQuery(body).prependTo('body');

            return false;
        },
        /* Non-emergency campus-wide alerts */
        'insertGlobalAdvisory': function (e) {
            var body = this.wrapGlobalAlert(this.gatherAlertInfo(e));
            if (false === body) {
                return false;
            }

            this.log(e);
            this.log(body);
            if (document.querySelectorAll('.site-header').length >= 1) {
                jQuery(body).insertAfter(jQuery('.site-header'));
            } else if (document.querySelectorAll('.umw-header-bar').length >= 1) {
                jQuery(body).insertAfter(jQuery('.umw-header-bar'));
            } else if (document.querySelectorAll('.umw-with-blocks .umw-header, .umw-no-blocks .umw-header').length >= 1) {
                jQuery(body).insertAfter(jQuery('.umw-header'));
            }
        },
        'wrapLocalAlert': function (body) {
            if (body === '') {
                return false;
            }

            var alertClass = this.is_alerts ? 'campus-advisory' : 'local-advisory';

            return '<aside class="' + alertClass + '">' + body + '</aside>';
        },
        'wrapGlobalEmergency': function (body) {
            if (body === '') {
                return false;
            }

            return '<aside class="emergency-alert">' + body + '</aside>';
        },
        'wrapGlobalAlert': function (body) {
            if (body === '') {
                return false;
            }

            return '<aside class="campus-advisory">' + body + '</aside>';
        },
        'alertBody': function (e) {
            if (false === this.did_css) {
                this.enqueueStyles();
            }

            var el = document.createElement('umw-active-alert');
            el.innerHTML = this.formatTemplate(this.body_template, e.url, e.title, e.author, e.date);

            if (false !== e.showmeta) {
                this.log('We are removing the meta data from the alert');
                el.getElementsByClassName('alert-meta')[0].remove();
            } else {
                this.log('We are keeping the meta data for this alert');
                this.log(e.showmeta);
            }

            return el.innerHTML;
        },
        /**
         * Replace placeholders with data, similar to PHP's sprintf() function, except, using {#} instead of %_ notation
         * @param {string} template the format of the information to be replaced
         * @param {string} [args...] Any number of string elements to be replaced within the template
         *
         * @since   1.0
         * @returns {string} the replaced information
         */
        'formatTemplate': function (template) {
            var args = Array.prototype.slice.call(arguments, 1);
            return template.replace(/{(\d+)}/g, function (match, number) {
                return typeof args[number] != 'undefined'
                    ? args[number]
                    : match
                    ;
            });
        },
        'log': function (m) {
            if (typeof console === 'undefined')
                return;
            if (!jQuery('body').hasClass('logged-in'))
                return;

            console.log(m);
        },
        'now': new Date(),
        'alerts_url': advisoriesObject.alerts_url,
        'local_url': advisoriesObject.local_url,
        'emergency_url': advisoriesObject.emergency_url,
        'is_root': advisoriesObject.is_root,
        'is_alerts': advisoriesObject.is_alerts,
        'is_front_page': advisoriesObject.is_front_page,
        'css_url': advisoriesObject.css_url,
        'body_template': advisoriesObject.body_template,
        'did_css': false
    };

    advisoriesFunctions.init();
});
