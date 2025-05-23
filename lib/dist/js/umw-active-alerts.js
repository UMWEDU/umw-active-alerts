/******/ (() => { // webpackBootstrap
class UmwActiveAlerts {
  constructor() {
    this.setVars();
    this.init();
  }
  log(message) {
    if (typeof console !== 'undefined') {
      console.log(message);
    }
  }
  setVars() {
    this.now = new Date();
    this.alerts_url = advisoriesObject.alerts_url;
    this.local_url = advisoriesObject.local_url;
    this.emergency_url = advisoriesObject.emergency_url;
    this.is_root = advisoriesObject.is_root;
    this.is_alerts = advisoriesObject.is_alerts;
    this.is_front_page = advisoriesObject.is_front_page;
    this.css_url = advisoriesObject.css_url;
    this.body_template = advisoriesObject.body_template;
    this.did_css = false;
  }
  init() {
    this.nowFormatted = this.formatDate(this.now);
    if (this.is_alerts || this.is_root && this.is_front_page) {
      if (this.is_alerts) {
        this.log('This appears to be the main alerts site');
      } else {
        this.log('This appears to be the front page of the root site');
      }
      this.log('So we are triggering the global alert');
      this.doGlobalAlert();
    } else {
      this.log('This does not appear to be the main alerts site or the front page of the root site');
      this.log('So we are triggering the local alert');
      this.doLocalAlert();
    }
    this.doGlobalEmergency();
  }
  formatDate(date) {
    const iso = date.toISOString().match(/(\d{4}\-\d{2}\-\d{2})T(\d{2}:\d{2}:\d{2})/);
    return iso[1] + ' ' + iso[2];
  }
  humanDate(date) {
    let fullDate = date.toLocaleString('en-US', {
      dateStyle: 'long',
      timeStyle: 'short'
    });
    const monthNames = {
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
      AM: 'a.m.'
    };
    for (let i in monthNames) {
      fullDate = fullDate.replace(i, monthNames[i]);
    }
    return fullDate;
  }
  enqueueStyles() {
    const link = document.createElement('link');
    link.href = this.css_url;
    link.type = 'text/css';
    link.rel = 'stylesheet';
    link.media = 'all';
    this.did_css = true;
    document.getElementsByTagName('head')[0].appendChild(link);
  }
  getQueryArgs() {
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
      '_embed': 1,
      'v': this.now
    };
  }
  getQueryString() {
    const queryString = this.flattenObject(this.getQueryArgs());
    const params = new URLSearchParams();
    for (let key in queryString) {
      params.append(key, queryString[key]);
    }
    return params;
  }
  getCurrentTime() {
    const today = new Date();
    let iso = today.toISOString().match(/(\d{4}\-\d{2}\-\d{2})T(\d{2}:\d{2}:\d{2})/);
    return iso[1] + ' ' + iso[2];
  }
  gatherAlertInfo(e) {
    if (typeof e !== 'object' && !Array.isArray(e) || e.length < 1) {
      this.log(typeof e);
      this.log('The alert info does not appear to be an array');
      this.log(e);
      return '';
    }
    const alert = e[0];
    let author = alert._embedded.author[0].name;
    if ('meta' in alert) {
      if ('_advisory_author' in alert.meta && alert.meta._advisory_author != '') {
        author = alert.meta._advisory_author;
      }
    }
    const dateString = alert.date + '.000-04:00';
    this.log(dateString);
    let date = new Date(dateString);
    date = this.humanDate(date);
    const data = {
      'url': alert.link,
      'title': alert.title.rendered,
      'author': author,
      'date': date,
      'showmeta': alert.meta._advisory_meta_include
    };
    return this.alertBody(data);
  }
  handleFetchError(error) {
    if (typeof error.json === "function") {
      error.json().then(jsonError => {
        this.log("Json error from API");
        this.log(jsonError);
      }).catch(genericError => {
        this.log("Generic error from API");
        this.log(error.statusText);
      });
    } else {
      this.log("Fetch error");
      this.log(error);
    }
  }
  doAlert(type) {
    let url = this.local_url;
    if ('global' === type) {
      url = this.alerts_url;
    } else if ('emergency' === type) {
      url = this.emergency_url;
    }
    const queryUrl = url + '?' + this.getQueryString();
    this.log('Preparing to query the following URL: ' + queryUrl);
    fetch(queryUrl, {
      method: 'GET',
      headers: {
        "Content-Type": "application/json"
      }
    }).then(response => {
      if (!response.ok) {
        return Promise.reject(response);
      }
      return response.json();
    }).then(data => {
      this.log(data);
      this.insertAlert(data, type);
    }).catch(error => {
      this.handleFetchError(error);
    });
  }
  insertAlert(data, type) {
    switch (type) {
      case 'local':
        this.insertLocalAlert(data);
        break;
      case 'global':
        this.insertGlobalAlert(data);
        break;
      case 'emergency':
        this.insertGlobalEmergency(data);
        break;
    }
  }

  /* Site-specific alert */
  doLocalAlert() {
    this.doAlert('local');
  }
  insertLocalAlert(e) {
    const body = this.wrapLocalAlert(this.gatherAlertInfo(e));
    if (false === body) {
      return false;
    }
    this.log(e);
    this.log(body);
    if (document.querySelectorAll('.content').length >= 1) {
      if (document.querySelectorAll('.umw-global-header').length > 0) {
        document.querySelector('.content').prepend(body);
      } else if (document.querySelectorAll('.content .container .breadcrumb').length > 0) {
        document.querySelector('.content .container .breadcrumb').after(body);
      } else if (document.querySelectorAll('.content .umw-block-content').length > 0) {
        document.querySelector('.content .umw-block-content').prepend(body);
      } else if (document.querySelectorAll('.site-inner').length > 0) {
        document.querySelector('.site-inner').prepend(body);
      } else {
        document.querySelector('.content').prepend(body);
      }
    } else if (document.querySelectorAll('#content').length >= 1) {
      document.querySelector('#content').prepend(body);
    }
    return false;
  }
  wrapLocalAlert(body) {
    if (body === '') {
      return false;
    }
    let classList = this.is_alerts ? 'campus-advisory' : 'local-advisory';
    return this.wrapAlert(body, classList);
  }

  /* Non-emergency campus-wide alert */
  doGlobalAlert() {
    this.doAlert('global');
  }
  insertGlobalAlert(e) {
    const body = this.wrapGlobalEmergency(this.gatherAlertInfo(e));
    if (false === body) {
      return false;
    }
    this.log(e);
    this.log(body);
    document.querySelector('body').prepend(body);
    return false;
  }
  wrapGlobalAlert(body) {
    if (body === '') {
      return false;
    }
    const classList = 'campus-advisory';
    return this.wrapAlert(body, classList);
  }

  /* Emergency campus-wide alert */
  doGlobalEmergency() {
    this.doAlert('emergency');
  }
  insertGlobalEmergency(e) {
    const body = this.wrapGlobalAlert(this.gatherAlertInfo(e));
    if (false === body) {
      return false;
    }
    this.log(e);
    this.log(body);
    if (document.querySelectorAll('.site-header').length >= 1) {
      document.querySelector('.site-header').after(body);
    } else if (document.querySelectorAll('.umw-header-bar').length >= 1) {
      document.querySelector('.umw-header-bar').after(body);
    } else if (document.querySelectorAll('.umw-with-blocks .umw-header, .umw-no-blocks .umw-header').length >= 1) {
      document.querySelector('body').prepend(body);
    }
  }
  wrapGlobalEmergency(body) {
    if (body === '') {
      return false;
    }
    const classList = 'emergency-alert';
    return this.wrapAlert(body, classList);
  }
  wrapAlert(body, classList) {
    if (body === '') {
      return false;
    }
    const wrap = document.createElement('aside');
    wrap.classList.add(classList);
    wrap.innerHTML = body;
    return wrap;
  }
  alertBody(e) {
    /*if (false === this.did_css) {
        this.enqueueStyles();
    }*/

    const el = document.createElement('umw-active-alert');
    el.innerHTML = this.formatTemplate(this.body_template, e.url, e.title, e.author, e.date);
    if (false !== e.showmeta) {
      this.log('We are removing the meta data from the alert');
      el.getElementsByClassName('alert-meta')[0].remove();
    } else {
      this.log('We are keeping the meta data for this alert');
      this.log(e.showmeta);
    }
    return el.innerHTML;
  }

  /**
   * Replace placeholders with data, similar to PHP's sprintf() function, except, using {#} instead of %_ notation
   * @param {string} template the format of the information to be replaced
   * @param {string} [args...] Any number of string elements to be replaced within the template
   *
   * @since   1.0
   * @returns {string} the replaced information
   */
  formatTemplate(template) {
    let args = Array.prototype.slice.call(arguments, 1);
    return template.replace(/{(\d+)}/g, function (match, number) {
      return typeof args[number] != 'undefined' ? args[number] : match;
    });
  }
  flattenObject(obj, prefix = '') {
    const result = {};
    for (const key in obj) {
      if (obj.hasOwnProperty(key)) {
        const fullKey = prefix ? `${prefix}[${key}]` : key;
        const value = obj[key];
        if (typeof value === 'object' && value !== null) {
          Object.assign(result, this.flattenObject(value, fullKey));
        } else {
          result[fullKey] = value;
        }
      }
    }
    return result;
  }
}
document.addEventListener('DOMContentLoaded', () => {
  new UmwActiveAlerts();
});
/******/ })()
;