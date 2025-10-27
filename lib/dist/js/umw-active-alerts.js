/******/ (() => { // webpackBootstrap
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
var UmwActiveAlerts = /*#__PURE__*/function () {
  function UmwActiveAlerts() {
    _classCallCheck(this, UmwActiveAlerts);
    this.logOpen = false;
    this.setVars();
    return this.init();
  }
  return _createClass(UmwActiveAlerts, [{
    key: "log",
    value: function log(message) {
      if (typeof console === "undefined") {
        return;
      }
      if (false === this.logOpen) {
        console.group('UMW Active Alerts');
        this.logOpen = true;
      }
      console.log(message);
    }
  }, {
    key: "setVars",
    value: function setVars() {
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
  }, {
    key: "init",
    value: function () {
      var _init = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
        var _this = this;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.n) {
            case 0:
              this.nowFormatted = this.formatDate(this.now);
              _context2.n = 1;
              return this.doGlobalEmergency().then(/*#__PURE__*/_asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
                return _regenerator().w(function (_context) {
                  while (1) switch (_context.n) {
                    case 0:
                      if (!(_this.is_alerts || _this.is_root && _this.is_front_page)) {
                        _context.n = 2;
                        break;
                      }
                      if (_this.is_alerts) {
                        _this.log('This appears to be the main alerts site');
                      } else {
                        _this.log('This appears to be the front page of the root site');
                      }
                      _this.log('So we are triggering the global alert');
                      _context.n = 1;
                      return _this.doGlobalAlert();
                    case 1:
                      _context.n = 3;
                      break;
                    case 2:
                      _this.log('This does not appear to be the main alerts site or the front page of the root site');
                      _this.log('So we are triggering the local alert');
                      _context.n = 3;
                      return _this.doLocalAlert();
                    case 3:
                      return _context.a(2);
                  }
                }, _callee);
              }))).then(function () {
                if (_this.logOpen) {
                  console.groupEnd();
                }
              });
            case 1:
              return _context2.a(2);
          }
        }, _callee2, this);
      }));
      function init() {
        return _init.apply(this, arguments);
      }
      return init;
    }()
  }, {
    key: "formatDate",
    value: function formatDate(date) {
      var iso = date.toISOString().match(/(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})/);
      return iso[1] + ' ' + iso[2];
    }
  }, {
    key: "humanDate",
    value: function humanDate(date) {
      var fullDate = date.toLocaleString('en-US', {
        dateStyle: 'long',
        timeStyle: 'short'
      });
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
        AM: 'a.m.'
      };
      for (var i in monthNames) {
        fullDate = fullDate.replace(i, monthNames[i]);
      }
      return fullDate;
    }
  }, {
    key: "enqueueStyles",
    value: function enqueueStyles() {
      var link = document.createElement('link');
      link.href = this.css_url;
      link.type = 'text/css';
      link.rel = 'stylesheet';
      link.media = 'all';
      this.did_css = true;
      document.getElementsByTagName('head')[0].appendChild(link);
    }
  }, {
    key: "getQueryArgs",
    value: function getQueryArgs() {
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
        'v': this.now.toISOString()
      };
    }
  }, {
    key: "getQueryString",
    value: function getQueryString() {
      var queryString = this.flattenObject(this.getQueryArgs());
      var params = new URLSearchParams();
      for (var key in queryString) {
        params.append(key, queryString[key]);
      }
      return params;
    }
  }, {
    key: "getCurrentTime",
    value: function getCurrentTime() {
      var today = new Date();
      var iso = today.toISOString().match(/(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})/);
      return iso[1] + ' ' + iso[2];
    }
  }, {
    key: "gatherAlertInfo",
    value: function gatherAlertInfo(e) {
      if (_typeof(e) !== 'object' && !Array.isArray(e) || e.length < 1) {
        this.log(_typeof(e));
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
    }
  }, {
    key: "handleFetchError",
    value: function handleFetchError(error) {
      var _this2 = this;
      if (typeof error.json === "function") {
        error.json().then(function (jsonError) {
          _this2.log("Json error from API");
          _this2.log(jsonError);
        })["catch"](function (genericError) {
          _this2.log("Generic error from API");
          _this2.log(genericError.statusText);
        });
      } else {
        this.log("Fetch error");
        this.log(error);
      }
    }
  }, {
    key: "doAlert",
    value: function () {
      var _doAlert = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3(type) {
        var _this3 = this;
        var url, queryUrl;
        return _regenerator().w(function (_context3) {
          while (1) switch (_context3.n) {
            case 0:
              url = this.local_url;
              if ('global' === type) {
                url = this.alerts_url;
              } else if ('emergency' === type) {
                url = this.emergency_url;
              }
              queryUrl = url + '?' + this.getQueryString();
              this.log('Preparing to query the following URL: ' + queryUrl);
              fetch(queryUrl, {
                method: 'GET',
                headers: {
                  "Content-Type": "application/json"
                }
              }).then(function (response) {
                if (!response.ok) {
                  return Promise.reject(response);
                }
                return response.json();
              }).then(function (data) {
                _this3.log(data);
                _this3.insertAlert(data, type);
              })["catch"](function (error) {
                _this3.handleFetchError(error);
              });
            case 1:
              return _context3.a(2);
          }
        }, _callee3, this);
      }));
      function doAlert(_x) {
        return _doAlert.apply(this, arguments);
      }
      return doAlert;
    }()
  }, {
    key: "insertAlert",
    value: function insertAlert(data, type) {
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
  }, {
    key: "doLocalAlert",
    value: (function () {
      var _doLocalAlert = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee4() {
        return _regenerator().w(function (_context4) {
          while (1) switch (_context4.n) {
            case 0:
              _context4.n = 1;
              return this.doAlert('local');
            case 1:
              return _context4.a(2);
          }
        }, _callee4, this);
      }));
      function doLocalAlert() {
        return _doLocalAlert.apply(this, arguments);
      }
      return doLocalAlert;
    }())
  }, {
    key: "insertLocalAlert",
    value: function insertLocalAlert(e) {
      var body = this.wrapLocalAlert(this.gatherAlertInfo(e));
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
  }, {
    key: "wrapLocalAlert",
    value: function wrapLocalAlert(body) {
      if (body === '') {
        return false;
      }
      var classList = this.is_alerts ? 'campus-advisory' : 'local-advisory';
      return this.wrapAlert(body, classList);
    }

    /* Non-emergency campus-wide alert */
  }, {
    key: "doGlobalAlert",
    value: (function () {
      var _doGlobalAlert = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee5() {
        return _regenerator().w(function (_context5) {
          while (1) switch (_context5.n) {
            case 0:
              _context5.n = 1;
              return this.doAlert('global');
            case 1:
              return _context5.a(2);
          }
        }, _callee5, this);
      }));
      function doGlobalAlert() {
        return _doGlobalAlert.apply(this, arguments);
      }
      return doGlobalAlert;
    }())
  }, {
    key: "insertGlobalAlert",
    value: function insertGlobalAlert(e) {
      var body = this.wrapGlobalAlert(this.gatherAlertInfo(e));
      if (false === body) {
        return false;
      }
      this.log(e);
      this.log(body);
      if (document.querySelectorAll('.emergency-alert').length > 0) {
        var emergency = document.querySelector('.emergency-alert');
        emergency.after(body);
      } else {
        document.querySelector('body').prepend(body);
      }
      return false;
    }
  }, {
    key: "wrapGlobalAlert",
    value: function wrapGlobalAlert(body) {
      if (body === '') {
        return false;
      }
      var classList = 'campus-advisory';
      return this.wrapAlert(body, classList);
    }

    /* Emergency campus-wide alert */
  }, {
    key: "doGlobalEmergency",
    value: (function () {
      var _doGlobalEmergency = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee6() {
        return _regenerator().w(function (_context6) {
          while (1) switch (_context6.n) {
            case 0:
              _context6.n = 1;
              return this.doAlert('emergency');
            case 1:
              return _context6.a(2);
          }
        }, _callee6, this);
      }));
      function doGlobalEmergency() {
        return _doGlobalEmergency.apply(this, arguments);
      }
      return doGlobalEmergency;
    }())
  }, {
    key: "insertGlobalEmergency",
    value: function insertGlobalEmergency(e) {
      var body = this.wrapGlobalEmergency(this.gatherAlertInfo(e));
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
  }, {
    key: "wrapGlobalEmergency",
    value: function wrapGlobalEmergency(body) {
      if (body === '') {
        return false;
      }
      var classList = 'emergency-alert';
      return this.wrapAlert(body, classList);
    }
  }, {
    key: "wrapAlert",
    value: function wrapAlert(body, classList) {
      if (body === '') {
        return false;
      }
      var wrap = document.createElement('aside');
      wrap.classList.add(classList);
      wrap.innerHTML = body;
      return wrap;
    }
  }, {
    key: "alertBody",
    value: function alertBody(e) {
      /*if (false === this.did_css) {
          this.enqueueStyles();
      }*/

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
    }

    /**
     * Replace placeholders with data, similar to PHP's sprintf() function, except, using {#} instead of %_ notation
     * @param {string} template the format of the information to be replaced
     * @param {string} [args...] Any number of string elements to be replaced within the template
     *
     * @since   1.0
     * @returns {string} the replaced information
     */
  }, {
    key: "formatTemplate",
    value: function formatTemplate(template) {
      var args = Array.prototype.slice.call(arguments, 1);
      return template.replace(/{(\d+)}/g, function (match, number) {
        return typeof args[number] != 'undefined' ? args[number] : match;
      });
    }
  }, {
    key: "flattenObject",
    value: function flattenObject(obj) {
      var prefix = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
      var result = {};
      for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
          var fullKey = prefix ? "".concat(prefix, "[").concat(key, "]") : key;
          var value = obj[key];
          if (_typeof(value) === 'object' && value !== null) {
            Object.assign(result, this.flattenObject(value, fullKey));
          } else {
            result[fullKey] = value;
          }
        }
      }
      return result;
    }
  }]);
}();
document.addEventListener('DOMContentLoaded', /*#__PURE__*/_asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee7() {
  var alert;
  return _regenerator().w(function (_context7) {
    while (1) switch (_context7.n) {
      case 0:
        _context7.n = 1;
        return new UmwActiveAlerts();
      case 1:
        alert = _context7.v;
      case 2:
        return _context7.a(2);
    }
  }, _callee7);
})));
/******/ })()
;