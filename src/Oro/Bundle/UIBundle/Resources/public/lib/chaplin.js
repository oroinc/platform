/*!
 * Chaplin 1.2.0
 *
 * Chaplin may be freely distributed under the MIT license.
 * For all details and documentation:
 * http://chaplinjs.org
 */

define(['backbone', 'underscore', 'oroui/js/app/services/load-modules'], function(Backbone, _, loadModules) {
  function require(name) {
    return {backbone: Backbone, underscore: _}[name];
  }

  require =(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';
module.exports = {
  Application: require('./chaplin/application'),
  Composer: require('./chaplin/composer'),
  Controller: require('./chaplin/controllers/controller'),
  Dispatcher: require('./chaplin/dispatcher'),
  Composition: require('./chaplin/lib/composition'),
  EventBroker: require('./chaplin/lib/event_broker'),
  History: require('./chaplin/lib/history'),
  Route: require('./chaplin/lib/route'),
  Router: require('./chaplin/lib/router'),
  support: require('./chaplin/lib/support'),
  SyncMachine: require('./chaplin/lib/sync_machine'),
  utils: require('./chaplin/lib/utils'),
  mediator: require('./chaplin/mediator'),
  Collection: require('./chaplin/models/collection'),
  Model: require('./chaplin/models/model'),
  CollectionView: require('./chaplin/views/collection_view'),
  Layout: require('./chaplin/views/layout'),
  View: require('./chaplin/views/view')
};


},{"./chaplin/application":2,"./chaplin/composer":3,"./chaplin/controllers/controller":4,"./chaplin/dispatcher":5,"./chaplin/lib/composition":6,"./chaplin/lib/event_broker":7,"./chaplin/lib/history":8,"./chaplin/lib/route":9,"./chaplin/lib/router":10,"./chaplin/lib/support":11,"./chaplin/lib/sync_machine":12,"./chaplin/lib/utils":13,"./chaplin/mediator":14,"./chaplin/models/collection":15,"./chaplin/models/model":16,"./chaplin/views/collection_view":17,"./chaplin/views/layout":18,"./chaplin/views/view":19}],2:[function(require,module,exports){
'use strict';
var Application, Backbone, Composer, Dispatcher, EventBroker, Layout, Router, _, mediator;

_ = require('underscore');

Backbone = require('backbone');

Composer = require('./composer');

Dispatcher = require('./dispatcher');

Router = require('./lib/router');

Layout = require('./views/layout');

EventBroker = require('./lib/event_broker');

mediator = require('./mediator');

module.exports = Application = (function() {
  Application.extend = Backbone.Model.extend;

  _.extend(Application.prototype, EventBroker);

  Application.prototype.title = '';

  Application.prototype.dispatcher = null;

  Application.prototype.layout = null;

  Application.prototype.router = null;

  Application.prototype.composer = null;

  Application.prototype.started = false;

  function Application(options) {
    if (options == null) {
      options = {};
    }
    this.initialize(options);
  }

  Application.prototype.initialize = function(options) {
    if (options == null) {
      options = {};
    }
    if (this.started) {
      throw new Error('Application#initialize: App was already started');
    }
    this.initRouter(options.routes, options);
    this.initDispatcher(options);
    this.initLayout(options);
    this.initComposer(options);
    this.initMediator();
    return this.start();
  };

  Application.prototype.initDispatcher = function(options) {
    return this.dispatcher = new Dispatcher(options);
  };

  Application.prototype.initLayout = function(options) {
    if (options == null) {
      options = {};
    }
    if (options.title == null) {
      options.title = this.title;
    }
    return this.layout = new Layout(options);
  };

  Application.prototype.initComposer = function(options) {
    if (options == null) {
      options = {};
    }
    return this.composer = new Composer(options);
  };

  Application.prototype.initMediator = function() {
    return Object.seal(mediator);
  };

  Application.prototype.initRouter = function(routes, options) {
    this.router = new Router(options);
    return typeof routes === "function" ? routes(this.router.match) : void 0;
  };

  Application.prototype.start = function() {
    this.router.startHistory();
    this.started = true;
    this.disposed = false;
    return Object.seal(this);
  };

  Application.prototype.dispose = function() {
    var i, len, prop, properties;
    if (this.disposed) {
      return;
    }
    properties = ['dispatcher', 'layout', 'router', 'composer'];
    for (i = 0, len = properties.length; i < len; i++) {
      prop = properties[i];
      if (this[prop] != null) {
        this[prop].dispose();
      }
    }
    this.disposed = true;
    return Object.freeze(this);
  };

  return Application;

})();


},{"./composer":3,"./dispatcher":5,"./lib/event_broker":7,"./lib/router":10,"./mediator":14,"./views/layout":18,"backbone":"backbone","underscore":"underscore"}],3:[function(require,module,exports){
'use strict';
var Backbone, Composer, Composition, EventBroker, _, mediator;

_ = require('underscore');

Backbone = require('backbone');

Composition = require('./lib/composition');

EventBroker = require('./lib/event_broker');

mediator = require('./mediator');

module.exports = Composer = (function() {
  Composer.extend = Backbone.Model.extend;

  _.extend(Composer.prototype, EventBroker);

  Composer.prototype.compositions = null;

  function Composer() {
    this.initialize.apply(this, arguments);
  }

  Composer.prototype.initialize = function(options) {
    if (options == null) {
      options = {};
    }
    this.compositions = {};
    mediator.setHandler('composer:compose', this.compose, this);
    mediator.setHandler('composer:retrieve', this.retrieve, this);
    return this.subscribeEvent('dispatcher:dispatch', this.cleanup);
  };

  Composer.prototype.compose = function(name, second, third) {
    if (typeof second === 'function') {
      if (third || second.prototype.dispose) {
        if (second.prototype instanceof Composition) {
          return this._compose(name, {
            composition: second,
            options: third
          });
        } else {
          return this._compose(name, {
            options: third,
            compose: function() {
              var autoRender, disabledAutoRender;
              if (second.prototype instanceof Backbone.Model || second.prototype instanceof Backbone.Collection) {
                this.item = new second(null, this.options);
              } else {
                this.item = new second(this.options);
              }
              autoRender = this.item.autoRender;
              disabledAutoRender = autoRender === void 0 || !autoRender;
              if (disabledAutoRender && typeof this.item.render === 'function') {
                return this.item.render();
              }
            }
          });
        }
      }
      return this._compose(name, {
        compose: second
      });
    }
    if (typeof third === 'function') {
      return this._compose(name, {
        compose: third,
        options: second
      });
    }
    return this._compose(name, second);
  };

  Composer.prototype._compose = function(name, options) {
    var composition, current, isPromise, returned;
    if (typeof options.compose !== 'function' && (options.composition == null)) {
      throw new Error('Composer#compose was used incorrectly');
    }
    if (options.composition != null) {
      composition = new options.composition(options.options);
    } else {
      composition = new Composition(options.options);
      composition.compose = options.compose;
      if (options.check) {
        composition.check = options.check;
      }
    }
    current = this.compositions[name];
    if (current && current.check(composition.options)) {
      current.stale(false);
    } else {
      if (current) {
        current.dispose();
      }
      returned = composition.compose(composition.options);
      isPromise = typeof (returned != null ? returned.then : void 0) === 'function';
      composition.stale(false);
      this.compositions[name] = composition;
    }
    if (isPromise) {
      return returned;
    } else {
      return this.compositions[name].item;
    }
  };

  Composer.prototype.retrieve = function(name) {
    var active;
    active = this.compositions[name];
    if (active && !active.stale()) {
      return active.item;
    }
  };

  Composer.prototype.cleanup = function() {
    var composition, i, key, len, ref;
    ref = Object.keys(this.compositions);
    for (i = 0, len = ref.length; i < len; i++) {
      key = ref[i];
      composition = this.compositions[key];
      if (composition.stale()) {
        composition.dispose();
        delete this.compositions[key];
      } else {
        composition.stale(true);
      }
    }
  };

  Composer.prototype.disposed = false;

  Composer.prototype.dispose = function() {
    var i, key, len, ref;
    if (this.disposed) {
      return;
    }
    this.unsubscribeAllEvents();
    mediator.removeHandlers(this);
    ref = Object.keys(this.compositions);
    for (i = 0, len = ref.length; i < len; i++) {
      key = ref[i];
      this.compositions[key].dispose();
    }
    delete this.compositions;
    this.disposed = true;
    return Object.freeze(this);
  };

  return Composer;

})();


},{"./lib/composition":6,"./lib/event_broker":7,"./mediator":14,"backbone":"backbone","underscore":"underscore"}],4:[function(require,module,exports){
'use strict';
var Backbone, Controller, EventBroker, _, mediator, utils,
  slice = [].slice;

_ = require('underscore');

Backbone = require('backbone');

mediator = require('../mediator');

EventBroker = require('../lib/event_broker');

utils = require('../lib/utils');

module.exports = Controller = (function() {
  Controller.extend = Backbone.Model.extend;

  _.extend(Controller.prototype, Backbone.Events);

  _.extend(Controller.prototype, EventBroker);

  Controller.prototype.view = null;

  Controller.prototype.redirected = false;

  function Controller() {
    this.initialize.apply(this, arguments);
  }

  Controller.prototype.initialize = function() {};

  Controller.prototype.beforeAction = function() {};

  Controller.prototype.adjustTitle = function(subtitle) {
    return mediator.execute('adjustTitle', subtitle);
  };

  Controller.prototype.reuse = function() {
    var method;
    method = arguments.length === 1 ? 'retrieve' : 'compose';
    return mediator.execute.apply(mediator, ["composer:" + method].concat(slice.call(arguments)));
  };

  Controller.prototype.compose = function() {
    throw new Error('Controller#compose was moved to Controller#reuse');
  };

  Controller.prototype.redirectTo = function() {
    this.redirected = true;
    return utils.redirectTo.apply(utils, arguments);
  };

  Controller.prototype.disposed = false;

  Controller.prototype.dispose = function() {
    var i, key, len, member, ref;
    if (this.disposed) {
      return;
    }
    ref = Object.keys(this);
    for (i = 0, len = ref.length; i < len; i++) {
      key = ref[i];
      member = this[key];
      if (typeof (member != null ? member.dispose : void 0) === 'function') {
        member.dispose();
        delete this[key];
      }
    }
    this.unsubscribeAllEvents();
    this.stopListening();
    this.disposed = true;
    return Object.freeze(this);
  };

  return Controller;

})();


},{"../lib/event_broker":7,"../lib/utils":13,"../mediator":14,"backbone":"backbone","underscore":"underscore"}],5:[function(require,module,exports){
'use strict';
var Backbone, Dispatcher, EventBroker, _, mediator, utils;

_ = require('underscore');

Backbone = require('backbone');

EventBroker = require('./lib/event_broker');

utils = require('./lib/utils');

mediator = require('./mediator');

module.exports = Dispatcher = (function() {
  Dispatcher.extend = Backbone.Model.extend;

  _.extend(Dispatcher.prototype, EventBroker);

  Dispatcher.prototype.previousRoute = null;

  Dispatcher.prototype.currentController = null;

  Dispatcher.prototype.currentRoute = null;

  Dispatcher.prototype.currentParams = null;

  Dispatcher.prototype.currentQuery = null;

  function Dispatcher() {
    this.initialize.apply(this, arguments);
  }

  Dispatcher.prototype.initialize = function(options) {
    if (options == null) {
      options = {};
    }
    this.settings = _.defaults(options, {
      controllerPath: 'controllers/',
      controllerSuffix: '_controller'
    });
    return this.subscribeEvent('router:match', this.dispatch);
  };

  Dispatcher.prototype.dispatch = function(route, params, options) {
    var ref, ref1;
    params = _.extend({}, params);
    options = _.extend({}, options);
    if (options.query == null) {
      options.query = {};
    }
    if (options.forceStartup !== true) {
      options.forceStartup = false;
    }
    if (!options.forceStartup && ((ref = this.currentRoute) != null ? ref.controller : void 0) === route.controller && ((ref1 = this.currentRoute) != null ? ref1.action : void 0) === route.action && _.isEqual(this.currentParams, params) && _.isEqual(this.currentQuery, options.query)) {
      return;
    }
    return this.loadController(route.controller, (function(_this) {
      return function(Controller) {
        return _this.controllerLoaded(route, params, options, Controller);
      };
    })(this));
  };

  Dispatcher.prototype.loadController = function(name, handler) {
    var fileName, moduleName;
    if (name === Object(name)) {
      return handler(name);
    }
    fileName = name + this.settings.controllerSuffix;
    moduleName = this.settings.controllerPath + fileName;
    return utils.loadModule(moduleName, handler);
  };

  Dispatcher.prototype.controllerLoaded = function(route, params, options, Controller) {
    var controller, prev, previous;
    if (this.nextPreviousRoute = this.currentRoute) {
      previous = _.extend({}, this.nextPreviousRoute);
      if (this.currentParams != null) {
        previous.params = this.currentParams;
      }
      if (previous.previous) {
        delete previous.previous;
      }
      prev = {
        previous: previous
      };
    }
    this.nextCurrentRoute = _.extend({}, route, prev);
    controller = new Controller(params, this.nextCurrentRoute, options);
    return this.executeBeforeAction(controller, this.nextCurrentRoute, params, options);
  };

  Dispatcher.prototype.executeAction = function(controller, route, params, options) {
    if (this.currentController) {
      this.publishEvent('beforeControllerDispose', this.currentController);
      this.currentController.dispose(params, route, options);
    }
    this.currentController = controller;
    this.currentParams = _.extend({}, params);
    this.currentQuery = _.extend({}, options.query);
    controller[route.action](params, route, options);
    if (controller.redirected) {
      return;
    }
    return this.publishEvent('dispatcher:dispatch', this.currentController, params, route, options);
  };

  Dispatcher.prototype.executeBeforeAction = function(controller, route, params, options) {
    var before, executeAction, promise;
    before = controller.beforeAction;
    executeAction = (function(_this) {
      return function() {
        if (controller.redirected || _this.currentRoute && route === _this.currentRoute) {
          _this.nextPreviousRoute = _this.nextCurrentRoute = null;
          controller.dispose();
          return;
        }
        _this.previousRoute = _this.nextPreviousRoute;
        _this.currentRoute = _this.nextCurrentRoute;
        _this.nextPreviousRoute = _this.nextCurrentRoute = null;
        return _this.executeAction(controller, route, params, options);
      };
    })(this);
    if (!before) {
      executeAction();
      return;
    }
    if (typeof before !== 'function') {
      throw new TypeError('Controller#beforeAction: function expected. ' + 'Old object-like form is not supported.');
    }
    promise = controller.beforeAction(params, route, options);
    if (typeof (promise != null ? promise.then : void 0) === 'function') {
      return promise.then(executeAction);
    } else {
      return executeAction();
    }
  };

  Dispatcher.prototype.disposed = false;

  Dispatcher.prototype.dispose = function() {
    if (this.disposed) {
      return;
    }
    this.unsubscribeAllEvents();
    this.disposed = true;
    return Object.freeze(this);
  };

  return Dispatcher;

})();


},{"./lib/event_broker":7,"./lib/utils":13,"./mediator":14,"backbone":"backbone","underscore":"underscore"}],6:[function(require,module,exports){
'use strict';
var Backbone, Composition, EventBroker, _;

_ = require('underscore');

Backbone = require('backbone');

EventBroker = require('./event_broker');

module.exports = Composition = (function() {
  Composition.extend = Backbone.Model.extend;

  _.extend(Composition.prototype, Backbone.Events);

  _.extend(Composition.prototype, EventBroker);

  Composition.prototype.item = null;

  Composition.prototype.options = null;

  Composition.prototype._stale = false;

  function Composition(options) {
    this.options = _.extend({}, options);
    this.item = this;
    this.initialize(this.options);
  }

  Composition.prototype.initialize = function() {};

  Composition.prototype.compose = function() {};

  Composition.prototype.check = function(options) {
    return _.isEqual(this.options, options);
  };

  Composition.prototype.stale = function(value) {
    var item, name;
    if (value == null) {
      return this._stale;
    }
    this._stale = value;
    for (name in this) {
      item = this[name];
      if (item && item !== this && typeof item === 'object' && item.hasOwnProperty('stale')) {
        item.stale = value;
      }
    }
  };

  Composition.prototype.disposed = false;

  Composition.prototype.dispose = function() {
    var i, key, len, member, ref;
    if (this.disposed) {
      return;
    }
    ref = Object.keys(this);
    for (i = 0, len = ref.length; i < len; i++) {
      key = ref[i];
      member = this[key];
      if (member && member !== this && typeof member.dispose === 'function') {
        member.dispose();
        delete this[key];
      }
    }
    this.unsubscribeAllEvents();
    this.stopListening();
    delete this.redirected;
    this.disposed = true;
    return Object.freeze(this);
  };

  return Composition;

})();


},{"./event_broker":7,"backbone":"backbone","underscore":"underscore"}],7:[function(require,module,exports){
'use strict';
var EventBroker, mediator,
  slice = [].slice;

mediator = require('../mediator');

EventBroker = {
  subscribeEvent: function(type, handler) {
    if (typeof type !== 'string') {
      throw new TypeError('EventBroker#subscribeEvent: ' + 'type argument must be a string');
    }
    if (typeof handler !== 'function') {
      throw new TypeError('EventBroker#subscribeEvent: ' + 'handler argument must be a function');
    }
    mediator.unsubscribe(type, handler, this);
    return mediator.subscribe(type, handler, this);
  },
  subscribeEventOnce: function(type, handler) {
    if (typeof type !== 'string') {
      throw new TypeError('EventBroker#subscribeEventOnce: ' + 'type argument must be a string');
    }
    if (typeof handler !== 'function') {
      throw new TypeError('EventBroker#subscribeEventOnce: ' + 'handler argument must be a function');
    }
    mediator.unsubscribe(type, handler, this);
    return mediator.subscribeOnce(type, handler, this);
  },
  unsubscribeEvent: function(type, handler) {
    if (typeof type !== 'string') {
      throw new TypeError('EventBroker#unsubscribeEvent: ' + 'type argument must be a string');
    }
    if (typeof handler !== 'function') {
      throw new TypeError('EventBroker#unsubscribeEvent: ' + 'handler argument must be a function');
    }
    return mediator.unsubscribe(type, handler);
  },
  unsubscribeAllEvents: function() {
    return mediator.unsubscribe(null, null, this);
  },
  publishEvent: function() {
    var args, type;
    type = arguments[0], args = 2 <= arguments.length ? slice.call(arguments, 1) : [];
    if (typeof type !== 'string') {
      throw new TypeError('EventBroker#publishEvent: ' + 'type argument must be a string');
    }
    return mediator.publish.apply(mediator, [type].concat(slice.call(args)));
  }
};

Object.freeze(EventBroker);

module.exports = EventBroker;


},{"../mediator":14}],8:[function(require,module,exports){
'use strict';
var Backbone, History, _, rootStripper, routeStripper,
  extend = function(child, parent) { for (var key in parent) { if (hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; },
  hasProp = {}.hasOwnProperty;

_ = require('underscore');

Backbone = require('backbone');

routeStripper = /^[#\/]|\s+$/g;

rootStripper = /^\/+|\/+$/g;

History = (function(superClass) {
  extend(History, superClass);

  function History() {
    return History.__super__.constructor.apply(this, arguments);
  }

  History.prototype.getFragment = function(fragment, forcePushState) {
    var root;
    if (fragment == null) {
      if (this._hasPushState || !this._wantsHashChange || forcePushState) {
        fragment = this.location.pathname + this.location.search;
        root = this.root.replace(/\/$/, '');
        if (!fragment.indexOf(root)) {
          fragment = fragment.slice(root.length);
        }
      } else {
        fragment = this.getHash();
      }
    }
    return fragment.replace(routeStripper, '');
  };

  History.prototype.start = function(options) {
    var atRoot, fragment, loc, ref, ref1, ref2;
    if (Backbone.History.started) {
      throw new Error('Backbone.history has already been started');
    }
    Backbone.History.started = true;
    this.options = _.extend({}, {
      root: '/'
    }, this.options, options);
    this.root = this.options.root;
    this._wantsHashChange = this.options.hashChange !== false;
    this._wantsPushState = Boolean(this.options.pushState);
    this._hasPushState = Boolean(this.options.pushState && ((ref = this.history) != null ? ref.pushState : void 0));
    fragment = this.getFragment();
    routeStripper = (ref1 = this.options.routeStripper) != null ? ref1 : routeStripper;
    rootStripper = (ref2 = this.options.rootStripper) != null ? ref2 : rootStripper;
    this.root = ('/' + this.root + '/').replace(rootStripper, '/');
    if (this._hasPushState) {
      Backbone.$(window).on('popstate', this.checkUrl);
    } else if (this._wantsHashChange) {
      Backbone.$(window).on('hashchange', this.checkUrl);
    }
    this.fragment = fragment;
    loc = this.location;
    atRoot = loc.pathname.replace(/[^\/]$/, '$&/') === this.root;
    if (this._wantsHashChange && this._wantsPushState && !this._hasPushState && !atRoot) {
      this.fragment = this.getFragment(null, true);
      this.location.replace(this.root + '#' + this.fragment);
      return true;
    } else if (this._wantsPushState && this._hasPushState && atRoot && loc.hash) {
      this.fragment = this.getHash().replace(routeStripper, '');
      this.history.replaceState({}, document.title, this.root + this.fragment);
    }
    if (!this.options.silent) {
      return this.loadUrl();
    }
  };

  History.prototype.navigate = function(fragment, options) {
    var historyMethod, url;
    if (fragment == null) {
      fragment = '';
    }
    if (!Backbone.History.started) {
      return false;
    }
    if (!options || options === true) {
      options = {
        trigger: options
      };
    }
    fragment = this.getFragment(fragment);
    url = this.root + fragment;
    if (this.fragment === fragment) {
      return false;
    }
    this.fragment = fragment;
    if (fragment.length === 0 && url !== this.root) {
      url = url.slice(0, -1);
    }
    if (this._hasPushState) {
      historyMethod = options.replace ? 'replaceState' : 'pushState';
      this.history[historyMethod]({}, document.title, url);
    } else if (this._wantsHashChange) {
      this._updateHash(this.location, fragment, options.replace);
    } else {
      return this.location.assign(url);
    }
    if (options.trigger) {
      return this.loadUrl(fragment);
    }
  };

  return History;

})(Backbone.History);

module.exports = Backbone.$ ? History : Backbone.History;


},{"backbone":"backbone","underscore":"underscore"}],9:[function(require,module,exports){
'use strict';
var Backbone, Controller, EventBroker, Route, _, utils,
  bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; };

_ = require('underscore');

Backbone = require('backbone');

EventBroker = require('./event_broker');

utils = require('./utils');

Controller = require('../controllers/controller');

module.exports = Route = (function() {
  var escapeRegExp, optionalRegExp, paramRegExp, processTrailingSlash;

  Route.extend = Backbone.Model.extend;

  _.extend(Route.prototype, EventBroker);

  escapeRegExp = /[\-{}\[\]+?.,\\\^$|#\s]/g;

  optionalRegExp = /\((.*?)\)/g;

  paramRegExp = /(?::|\*)(\w+)/g;

  processTrailingSlash = function(path, trailing) {
    switch (trailing) {
      case true:
        if (path.slice(-1) !== '/') {
          path += '/';
        }
        break;
      case false:
        if (path.slice(-1) === '/') {
          path = path.slice(0, -1);
        }
    }
    return path;
  };

  function Route(pattern1, controller, action, options) {
    this.pattern = pattern1;
    this.controller = controller;
    this.action = action;
    this.handler = bind(this.handler, this);
    this.parseOptionalPortion = bind(this.parseOptionalPortion, this);
    if (typeof this.pattern !== 'string') {
      throw new Error('Route: RegExps are not supported. Use strings with :names and `constraints` option of route');
    }
    this.options = _.extend({}, options);
    if (this.options.paramsInQS !== false) {
      this.options.paramsInQS = true;
    }
    if (this.options.name != null) {
      this.name = this.options.name;
    }
    if (this.name && this.name.indexOf('#') !== -1) {
      throw new Error('Route: "#" cannot be used in name');
    }
    if (this.name == null) {
      this.name = this.controller + '#' + this.action;
    }
    this.allParams = [];
    this.requiredParams = [];
    this.optionalParams = [];
    if (this.action in Controller.prototype) {
      throw new Error('Route: You should not use existing controller ' + 'properties as action names');
    }
    this.createRegExp();
    Object.freeze(this);
  }

  Route.prototype.matches = function(criteria) {
    var i, invalidParamsCount, len, name, propertiesCount, property, ref;
    if (typeof criteria === 'string') {
      return criteria === this.name;
    } else {
      propertiesCount = 0;
      ref = ['name', 'action', 'controller'];
      for (i = 0, len = ref.length; i < len; i++) {
        name = ref[i];
        propertiesCount++;
        property = criteria[name];
        if (property && property !== this[name]) {
          return false;
        }
      }
      invalidParamsCount = propertiesCount === 1 && (name === 'action' || name === 'controller');
      return !invalidParamsCount;
    }
  };

  Route.prototype.reverse = function(params, query) {
    var i, j, len, len1, name, raw, ref, ref1, remainingParams, url, value;
    params = this.normalizeParams(params);
    remainingParams = _.extend({}, params);
    if (params === false) {
      return false;
    }
    url = this.pattern;
    ref = this.requiredParams;
    for (i = 0, len = ref.length; i < len; i++) {
      name = ref[i];
      value = params[name];
      url = url.replace(RegExp("[:*]" + name, "g"), value);
      delete remainingParams[name];
    }
    ref1 = this.optionalParams;
    for (j = 0, len1 = ref1.length; j < len1; j++) {
      name = ref1[j];
      if (value = params[name]) {
        url = url.replace(RegExp("[:*]" + name, "g"), value);
        delete remainingParams[name];
      }
    }
    raw = url.replace(optionalRegExp, function(match, portion) {
      if (portion.match(/[:*]/g)) {
        return "";
      } else {
        return portion;
      }
    });
    url = processTrailingSlash(raw, this.options.trailing);
    if (typeof query !== 'object') {
      query = utils.queryParams.parse(query);
    }
    if (this.options.paramsInQS !== false) {
      _.extend(query, remainingParams);
    }
    if (!utils.isEmpty(query)) {
      url += '?' + utils.queryParams.stringify(query);
    }
    return url;
  };

  Route.prototype.normalizeParams = function(params) {
    var i, paramIndex, paramName, paramsHash, ref, routeParams;
    if (Array.isArray(params)) {
      if (params.length < this.requiredParams.length) {
        return false;
      }
      paramsHash = {};
      routeParams = this.requiredParams.concat(this.optionalParams);
      for (paramIndex = i = 0, ref = params.length - 1; i <= ref; paramIndex = i += 1) {
        paramName = routeParams[paramIndex];
        paramsHash[paramName] = params[paramIndex];
      }
      if (!this.testConstraints(paramsHash)) {
        return false;
      }
      params = paramsHash;
    } else {
      if (params == null) {
        params = {};
      }
      if (!this.testParams(params)) {
        return false;
      }
    }
    return params;
  };

  Route.prototype.testConstraints = function(params) {
    var constraints;
    constraints = this.options.constraints;
    return Object.keys(constraints || {}).every(function(key) {
      return constraints[key].test(params[key]);
    });
  };

  Route.prototype.testParams = function(params) {
    var i, len, paramName, ref;
    ref = this.requiredParams;
    for (i = 0, len = ref.length; i < len; i++) {
      paramName = ref[i];
      if (params[paramName] === void 0) {
        return false;
      }
    }
    return this.testConstraints(params);
  };

  Route.prototype.createRegExp = function() {
    var pattern;
    pattern = this.pattern;
    pattern = pattern.replace(escapeRegExp, '\\$&');
    this.replaceParams(pattern, (function(_this) {
      return function(match, param) {
        return _this.allParams.push(param);
      };
    })(this));
    pattern = pattern.replace(optionalRegExp, this.parseOptionalPortion);
    pattern = this.replaceParams(pattern, (function(_this) {
      return function(match, param) {
        _this.requiredParams.push(param);
        return _this.paramCapturePattern(match);
      };
    })(this));
    return this.regExp = RegExp("^" + pattern + "(?=\\/*(?=\\?|$))");
  };

  Route.prototype.parseOptionalPortion = function(match, optionalPortion) {
    var portion;
    portion = this.replaceParams(optionalPortion, (function(_this) {
      return function(match, param) {
        _this.optionalParams.push(param);
        return _this.paramCapturePattern(match);
      };
    })(this));
    return "(?:" + portion + ")?";
  };

  Route.prototype.replaceParams = function(s, callback) {
    return s.replace(paramRegExp, callback);
  };

  Route.prototype.paramCapturePattern = function(param) {
    if (param[0] === ':') {
      return '([^\/\?]+)';
    } else {
      return '(.*?)';
    }
  };

  Route.prototype.test = function(path) {
    var constraints, matched;
    matched = this.regExp.test(path);
    if (!matched) {
      return false;
    }
    constraints = this.options.constraints;
    if (constraints) {
      return this.testConstraints(this.extractParams(path));
    }
    return true;
  };

  Route.prototype.handler = function(pathParams, options) {
    var actionParams, params, path, query, ref, route;
    options = _.extend({}, options);
    if (pathParams && typeof pathParams === 'object') {
      query = utils.queryParams.stringify(options.query);
      params = pathParams;
      path = this.reverse(params);
    } else {
      ref = pathParams.split('?'), path = ref[0], query = ref[1];
      if (query == null) {
        query = '';
      } else {
        options.query = utils.queryParams.parse(query);
      }
      params = this.extractParams(path);
      path = processTrailingSlash(path, this.options.trailing);
    }
    actionParams = _.extend({}, params, this.options.params);
    route = {
      path: path,
      action: this.action,
      controller: this.controller,
      name: this.name,
      query: query
    };
    return this.publishEvent('router:match', route, actionParams, options);
  };

  Route.prototype.extractParams = function(path) {
    var i, index, len, match, matches, paramName, params, ref;
    params = {};
    matches = this.regExp.exec(path);
    ref = matches.slice(1);
    for (index = i = 0, len = ref.length; i < len; index = ++i) {
      match = ref[index];
      paramName = this.allParams.length ? this.allParams[index] : index;
      params[paramName] = match;
    }
    return params;
  };

  return Route;

})();


},{"../controllers/controller":4,"./event_broker":7,"./utils":13,"backbone":"backbone","underscore":"underscore"}],10:[function(require,module,exports){
'use strict';
var Backbone, EventBroker, History, Route, Router, _, mediator, utils,
  bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; };

_ = require('underscore');

Backbone = require('backbone');

EventBroker = require('./event_broker');

History = require('./history');

Route = require('./route');

utils = require('./utils');

mediator = require('../mediator');

module.exports = Router = (function() {
  Router.extend = Backbone.Model.extend;

  _.extend(Router.prototype, EventBroker);

  function Router(options1) {
    var isWebFile;
    this.options = options1 != null ? options1 : {};
    this.match = bind(this.match, this);
    isWebFile = window.location.protocol !== 'file:';
    _.defaults(this.options, {
      pushState: isWebFile,
      root: '/',
      trailing: false
    });
    this.removeRoot = new RegExp('^' + utils.escapeRegExp(this.options.root) + '(#)?');
    this.subscribeEvent('!router:route', this.oldEventError);
    this.subscribeEvent('!router:routeByName', this.oldEventError);
    this.subscribeEvent('!router:changeURL', this.oldURLEventError);
    this.subscribeEvent('dispatcher:dispatch', this.changeURL);
    mediator.setHandler('router:route', this.route, this);
    mediator.setHandler('router:reverse', this.reverse, this);
    this.createHistory();
  }

  Router.prototype.oldEventError = function() {
    throw new Error('!router:route and !router:routeByName events were removed. Use `Chaplin.utils.redirectTo`');
  };

  Router.prototype.oldURLEventError = function() {
    throw new Error('!router:changeURL event was removed.');
  };

  Router.prototype.createHistory = function() {
    return Backbone.history = new History();
  };

  Router.prototype.startHistory = function() {
    return Backbone.history.start(this.options);
  };

  Router.prototype.stopHistory = function() {
    if (Backbone.History.started) {
      return Backbone.history.stop();
    }
  };

  Router.prototype.findHandler = function(predicate) {
    var handler, i, len, ref;
    ref = Backbone.history.handlers;
    for (i = 0, len = ref.length; i < len; i++) {
      handler = ref[i];
      if (predicate(handler)) {
        return handler;
      }
    }
  };

  Router.prototype.match = function(pattern, target, options) {
    var action, controller, ref, ref1, route;
    if (options == null) {
      options = {};
    }
    if (arguments.length === 2 && target && typeof target === 'object') {
      ref = options = target, controller = ref.controller, action = ref.action;
      if (!(controller && action)) {
        throw new Error('Router#match must receive either target or ' + 'options.controller & options.action');
      }
    } else {
      controller = options.controller, action = options.action;
      if (controller || action) {
        throw new Error('Router#match cannot use both target and ' + 'options.controller / options.action');
      }
      ref1 = target.split('#'), controller = ref1[0], action = ref1[1];
    }
    _.defaults(options, {
      trailing: this.options.trailing
    });
    route = new Route(pattern, controller, action, options);
    Backbone.history.handlers.push({
      route: route,
      callback: route.handler
    });
    return route;
  };

  Router.prototype.route = function(pathDesc, params, options) {
    var handler, path, pathParams;
    if (pathDesc && typeof pathDesc === 'object') {
      path = pathDesc.url;
      if (!params && pathDesc.params) {
        params = pathDesc.params;
      }
    }
    params = Array.isArray(params) ? params.slice() : _.extend({}, params);
    if (path != null) {
      path = path.replace(this.removeRoot, '');
      handler = this.findHandler(function(handler) {
        return handler.route.test(path);
      });
      options = params;
      params = null;
    } else {
      options = _.extend({}, options);
      handler = this.findHandler(function(handler) {
        if (handler.route.matches(pathDesc)) {
          params = handler.route.normalizeParams(params);
          if (params) {
            return true;
          }
        }
        return false;
      });
    }
    if (handler) {
      _.defaults(options, {
        changeURL: true
      });
      pathParams = path != null ? path : params;
      handler.callback(pathParams, options);
      return true;
    } else {
      throw new Error('Router#route: request was not routed');
    }
  };

  Router.prototype.reverse = function(criteria, params, query) {
    var handler, handlers, i, len, reversed, root, url;
    root = this.options.root;
    if ((params != null) && typeof params !== 'object') {
      throw new TypeError('Router#reverse: params must be an array or an ' + 'object');
    }
    handlers = Backbone.history.handlers;
    for (i = 0, len = handlers.length; i < len; i++) {
      handler = handlers[i];
      if (!(handler.route.matches(criteria))) {
        continue;
      }
      reversed = handler.route.reverse(params, query);
      if (reversed !== false) {
        url = root ? root + reversed : reversed;
        return url;
      }
    }
    throw new Error('Router#reverse: invalid route criteria specified: ' + ("" + (JSON.stringify(criteria))));
  };

  Router.prototype.changeURL = function(controller, params, route, options) {
    var navigateOptions, url;
    if (!((route.path != null) && (options != null ? options.changeURL : void 0))) {
      return;
    }
    url = route.path + (route.query ? "?" + route.query : '');
    navigateOptions = {
      trigger: options.trigger === true,
      replace: options.replace === true
    };
    return Backbone.history.navigate(url, navigateOptions);
  };

  Router.prototype.disposed = false;

  Router.prototype.dispose = function() {
    if (this.disposed) {
      return;
    }
    this.stopHistory();
    delete Backbone.history;
    this.unsubscribeAllEvents();
    mediator.removeHandlers(this);
    this.disposed = true;
    return Object.freeze(this);
  };

  return Router;

})();


},{"../mediator":14,"./event_broker":7,"./history":8,"./route":9,"./utils":13,"backbone":"backbone","underscore":"underscore"}],11:[function(require,module,exports){
'use strict';
module.exports = {
  propertyDescriptors: true
};


},{}],12:[function(require,module,exports){
'use strict';
var STATE_CHANGE, SYNCED, SYNCING, SyncMachine, UNSYNCED, event, fn, i, len, ref;

UNSYNCED = 'unsynced';

SYNCING = 'syncing';

SYNCED = 'synced';

STATE_CHANGE = 'syncStateChange';

SyncMachine = {
  _syncState: UNSYNCED,
  _previousSyncState: null,
  syncState: function() {
    return this._syncState;
  },
  isUnsynced: function() {
    return this._syncState === UNSYNCED;
  },
  isSynced: function() {
    return this._syncState === SYNCED;
  },
  isSyncing: function() {
    return this._syncState === SYNCING;
  },
  unsync: function() {
    var ref;
    if ((ref = this._syncState) === SYNCING || ref === SYNCED) {
      this._previousSync = this._syncState;
      this._syncState = UNSYNCED;
      this.trigger(this._syncState, this, this._syncState);
      this.trigger(STATE_CHANGE, this, this._syncState);
    }
  },
  beginSync: function() {
    var ref;
    if ((ref = this._syncState) === UNSYNCED || ref === SYNCED) {
      this._previousSync = this._syncState;
      this._syncState = SYNCING;
      this.trigger(this._syncState, this, this._syncState);
      this.trigger(STATE_CHANGE, this, this._syncState);
    }
  },
  finishSync: function() {
    if (this._syncState === SYNCING) {
      this._previousSync = this._syncState;
      this._syncState = SYNCED;
      this.trigger(this._syncState, this, this._syncState);
      this.trigger(STATE_CHANGE, this, this._syncState);
    }
  },
  abortSync: function() {
    if (this._syncState === SYNCING) {
      this._syncState = this._previousSync;
      this._previousSync = this._syncState;
      this.trigger(this._syncState, this, this._syncState);
      this.trigger(STATE_CHANGE, this, this._syncState);
    }
  }
};

ref = [UNSYNCED, SYNCING, SYNCED, STATE_CHANGE];
fn = function(event) {
  return SyncMachine[event] = function(callback, context) {
    if (context == null) {
      context = this;
    }
    this.on(event, callback, context);
    if (this._syncState === event) {
      return callback.call(context);
    }
  };
};
for (i = 0, len = ref.length; i < len; i++) {
  event = ref[i];
  fn(event);
}

Object.freeze(SyncMachine);

module.exports = SyncMachine;


},{}],13:[function(require,module,exports){
'use strict';
var utils,
  slice = [].slice,
  indexOf = [].indexOf || function(item) { for (var i = 0, l = this.length; i < l; i++) { if (i in this && this[i] === item) return i; } return -1; };

utils = {
  isEmpty: function(object) {
    return !Object.getOwnPropertyNames(object).length;
  },
  serialize: function(data) {
    if (typeof data.serialize === 'function') {
      return data.serialize();
    } else if (typeof data.toJSON === 'function') {
      return data.toJSON();
    } else {
      throw new TypeError('utils.serialize: Unknown data was passed');
    }
  },
  readonly: function() {
    var i, key, keys, len, object;
    object = arguments[0], keys = 2 <= arguments.length ? slice.call(arguments, 1) : [];
    for (i = 0, len = keys.length; i < len; i++) {
      key = keys[i];
      Object.defineProperty(object, key, {
        value: object[key],
        writable: false,
        configurable: false
      });
    }
    return true;
  },
  getPrototypeChain: function(object) {
    var chain;
    chain = [];
    while (object = Object.getPrototypeOf(object)) {
      chain.unshift(object);
    }
    return chain;
  },
  getAllPropertyVersions: function(object, key) {
    var i, len, proto, ref, result, value;
    result = [];
    ref = utils.getPrototypeChain(object);
    for (i = 0, len = ref.length; i < len; i++) {
      proto = ref[i];
      value = proto[key];
      if (value && indexOf.call(result, value) < 0) {
        result.push(value);
      }
    }
    return result;
  },
  upcase: function(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  },
  escapeRegExp: function(str) {
    return String(str || '').replace(/([.*+?^=!:${}()|[\]\/\\])/g, '\\$1');
  },
  modifierKeyPressed: function(event) {
    return event.shiftKey || event.altKey || event.ctrlKey || event.metaKey;
  },
  reverse: function(criteria, params, query) {
    return require('../mediator').execute('router:reverse', criteria, params, query);
  },
  redirectTo: function(pathDesc, params, options) {
    return require('../mediator').execute('router:route', pathDesc, params, options);
  },
  loadModule: loadModules,
  matchesSelector: (function() {
    var el, matches;
    el = document.documentElement;
    matches = el.matches || el.msMatchesSelector || el.mozMatchesSelector || el.webkitMatchesSelector;
    return function() {
      return matches.call.apply(matches, arguments);
    };
  })(),
  querystring: {
    stringify: function(params, replacer) {
      if (params == null) {
        params = {};
      }
      if (typeof replacer !== 'function') {
        replacer = function(key, value) {
          if (Array.isArray(value)) {
            return value.map(function(value) {
              return {
                key: key,
                value: value
              };
            });
          } else if (value != null) {
            return {
              key: key,
              value: value
            };
          }
        };
      }
      return Object.keys(params).reduce(function(pairs, key) {
        var pair;
        pair = replacer(key, params[key]);
        return pairs.concat(pair || []);
      }, []).map(function(arg) {
        var key, value;
        key = arg.key, value = arg.value;
        return [key, value].map(encodeURIComponent).join('=');
      }).join('&');
    },
    parse: function(string, reviver) {
      if (string == null) {
        string = '';
      }
      if (typeof reviver !== 'function') {
        reviver = function(key, value) {
          return {
            key: key,
            value: value
          };
        };
      }
      string = string.slice(1 + string.indexOf('?'));
      return string.split('&').reduce(function(params, pair) {
        var key, parts, ref, value;
        parts = pair.split('=').map(decodeURIComponent);
        ref = reviver.apply(null, parts) || {}, key = ref.key, value = ref.value;
        if (value != null) {
          params[key] = params.hasOwnProperty(key) ? [].concat(params[key], value) : value;
        }
        return params;
      }, {});
    }
  }
};

utils.beget = Object.create;

utils.indexOf = function(array, item) {
  return array.indexOf(item);
};

utils.isArray = Array.isArray;

utils.queryParams = utils.querystring;

Object.seal(utils);

module.exports = utils;


},{"../mediator":14}],14:[function(require,module,exports){
'use strict';
var Backbone, handlers, mediator, utils,
  slice = [].slice;

Backbone = require('backbone');

utils = require('./lib/utils');

mediator = {};

mediator.subscribe = mediator.on = Backbone.Events.on;

mediator.subscribeOnce = mediator.once = Backbone.Events.once;

mediator.unsubscribe = mediator.off = Backbone.Events.off;

mediator.publish = mediator.trigger = Backbone.Events.trigger;

mediator._callbacks = null;

handlers = mediator._handlers = {};

mediator.setHandler = function(name, method, instance) {
  return handlers[name] = {
    instance: instance,
    method: method
  };
};

mediator.execute = function() {
  var args, handler, name, options, silent;
  options = arguments[0], args = 2 <= arguments.length ? slice.call(arguments, 1) : [];
  if (options && typeof options === 'object') {
    name = options.name, silent = options.silent;
  } else {
    name = options;
  }
  handler = handlers[name];
  if (handler) {
    return handler.method.apply(handler.instance, args);
  } else if (!silent) {
    throw new Error("mediator.execute: " + name + " handler is not defined");
  }
};

mediator.removeHandlers = function(instanceOrNames) {
  var handler, i, len, name;
  if (!instanceOrNames) {
    mediator._handlers = {};
  }
  if (Array.isArray(instanceOrNames)) {
    for (i = 0, len = instanceOrNames.length; i < len; i++) {
      name = instanceOrNames[i];
      delete handlers[name];
    }
  } else {
    for (name in handlers) {
      handler = handlers[name];
      if (handler.instance === instanceOrNames) {
        delete handlers[name];
      }
    }
  }
};

mediator.seal = function() {
  return Object.seal(mediator);
};

utils.readonly(mediator, 'subscribe', 'subscribeOnce', 'unsubscribe', 'publish', 'setHandler', 'execute', 'removeHandlers', 'seal');

module.exports = mediator;


},{"./lib/utils":13,"backbone":"backbone"}],15:[function(require,module,exports){
'use strict';
var Backbone, Collection, EventBroker, Model, _, utils,
  extend = function(child, parent) { for (var key in parent) { if (hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; },
  hasProp = {}.hasOwnProperty;

_ = require('underscore');

Backbone = require('backbone');

Model = require('./model');

EventBroker = require('../lib/event_broker');

utils = require('../lib/utils');

module.exports = Collection = (function(superClass) {
  extend(Collection, superClass);

  function Collection() {
    return Collection.__super__.constructor.apply(this, arguments);
  }

  _.extend(Collection.prototype, EventBroker);

  Collection.prototype.model = Model;

  Collection.prototype.serialize = function() {
    return this.map(utils.serialize);
  };

  Collection.prototype.disposed = false;

  Collection.prototype.dispose = function() {
    var i, len, prop, ref;
    if (this.disposed) {
      return;
    }
    this.trigger('dispose', this);
    this.reset([], {
      silent: true
    });
    this.unsubscribeAllEvents();
    this.stopListening();
    this.off();
    ref = ['model', 'models', '_byCid', '_callbacks'];
    for (i = 0, len = ref.length; i < len; i++) {
      prop = ref[i];
      delete this[prop];
    }
    this._byId = {};
    this.disposed = true;
    return Object.freeze(this);
  };

  return Collection;

})(Backbone.Collection);


},{"../lib/event_broker":7,"../lib/utils":13,"./model":16,"backbone":"backbone","underscore":"underscore"}],16:[function(require,module,exports){
'use strict';
var Backbone, EventBroker, Model, _, serializeAttributes, serializeModelAttributes,
  extend = function(child, parent) { for (var key in parent) { if (hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; },
  hasProp = {}.hasOwnProperty;

_ = require('underscore');

Backbone = require('backbone');

EventBroker = require('../lib/event_broker');

serializeAttributes = function(model, attributes, modelStack) {
  var delegator, i, key, len, otherModel, ref, serializedModels, value;
  delegator = Object.create(attributes);
  if (modelStack == null) {
    modelStack = {};
  }
  modelStack[model.cid] = true;
  for (key in attributes) {
    value = attributes[key];
    if (value instanceof Backbone.Model) {
      delegator[key] = serializeModelAttributes(value, model, modelStack);
    } else if (value instanceof Backbone.Collection) {
      serializedModels = [];
      ref = value.models;
      for (i = 0, len = ref.length; i < len; i++) {
        otherModel = ref[i];
        serializedModels.push(serializeModelAttributes(otherModel, model, modelStack));
      }
      delegator[key] = serializedModels;
    }
  }
  delete modelStack[model.cid];
  return delegator;
};

serializeModelAttributes = function(model, currentModel, modelStack) {
  var attributes;
  if (model === currentModel || model.cid in modelStack) {
    return null;
  }
  attributes = typeof model.getAttributes === 'function' ? model.getAttributes() : model.attributes;
  return serializeAttributes(model, attributes, modelStack);
};

module.exports = Model = (function(superClass) {
  extend(Model, superClass);

  function Model() {
    return Model.__super__.constructor.apply(this, arguments);
  }

  _.extend(Model.prototype, EventBroker);

  Model.prototype.getAttributes = function() {
    return this.attributes;
  };

  Model.prototype.serialize = function() {
    return serializeAttributes(this, this.getAttributes());
  };

  Model.prototype.disposed = false;

  Model.prototype.dispose = function() {
    var i, len, prop, ref, ref1;
    if (this.disposed) {
      return;
    }
    this.trigger('dispose', this);
    if ((ref = this.collection) != null) {
      if (typeof ref.remove === "function") {
        ref.remove(this, {
          silent: true
        });
      }
    }
    this.unsubscribeAllEvents();
    this.stopListening();
    this.off();
    ref1 = ['collection', 'attributes', 'changed', 'defaults', '_escapedAttributes', '_previousAttributes', '_silent', '_pending', '_callbacks'];
    for (i = 0, len = ref1.length; i < len; i++) {
      prop = ref1[i];
      delete this[prop];
    }
    this.disposed = true;
    return Object.freeze(this);
  };

  return Model;

})(Backbone.Model);


},{"../lib/event_broker":7,"backbone":"backbone","underscore":"underscore"}],17:[function(require,module,exports){
'use strict';
var $, Backbone, CollectionView, View, addClass, endAnimation, filterChildren, insertView, startAnimation, toggleElement, utils,
  bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; },
  extend = function(child, parent) { for (var key in parent) { if (hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; },
  hasProp = {}.hasOwnProperty;

Backbone = require('backbone');

View = require('./view');

utils = require('../lib/utils');

$ = Backbone.$;

filterChildren = function(nodeList, selector) {
  var i, len, node, results;
  if (!selector) {
    return nodeList;
  }
  results = [];
  for (i = 0, len = nodeList.length; i < len; i++) {
    node = nodeList[i];
    if (utils.matchesSelector(node, selector)) {
      results.push(node);
    }
  }
  return results;
};

toggleElement = (function() {
  if ($) {
    return function(elem, visible) {
      return elem.toggle(visible);
    };
  } else {
    return function(elem, visible) {
      return elem.style.display = (visible ? '' : 'none');
    };
  }
})();

addClass = (function() {
  if ($) {
    return function(elem, cls) {
      return elem.addClass(cls);
    };
  } else {
    return function(elem, cls) {
      return elem.classList.add(cls);
    };
  }
})();

startAnimation = (function() {
  if ($) {
    return function(elem, useCssAnimation, cls) {
      if (useCssAnimation) {
        return addClass(elem, cls);
      } else {
        return elem.css('opacity', 0);
      }
    };
  } else {
    return function(elem, useCssAnimation, cls) {
      if (useCssAnimation) {
        return addClass(elem, cls);
      } else {
        return elem.style.opacity = 0;
      }
    };
  }
})();

endAnimation = (function() {
  if ($) {
    return function(elem, duration) {
      return elem.animate({
        opacity: 1
      }, duration);
    };
  } else {
    return function(elem, duration) {
      elem.style.transition = "opacity " + duration + "ms";
      return elem.style.opacity = 1;
    };
  }
})();

insertView = (function() {
  if ($) {
    return function(list, viewEl, position, length, itemSelector) {
      var children, childrenLength, insertInMiddle, isEnd, method;
      insertInMiddle = (0 < position && position < length);
      isEnd = function(length) {
        return length === 0 || position >= length;
      };
      if (insertInMiddle || itemSelector) {
        children = list.children(itemSelector);
        childrenLength = children.length;
        if (children[position] !== viewEl) {
          if (isEnd(childrenLength)) {
            return list.append(viewEl);
          } else {
            if (position === 0) {
              return children.eq(position).before(viewEl);
            } else {
              return children.eq(position - 1).after(viewEl);
            }
          }
        }
      } else {
        method = isEnd(length) ? 'append' : 'prepend';
        return list[method](viewEl);
      }
    };
  } else {
    return function(list, viewEl, position, length, itemSelector) {
      var children, childrenLength, insertInMiddle, isEnd, last;
      insertInMiddle = (0 < position && position < length);
      isEnd = function(length) {
        return length === 0 || position === length;
      };
      if (insertInMiddle || itemSelector) {
        children = filterChildren(list.children, itemSelector);
        childrenLength = children.length;
        if (children[position] !== viewEl) {
          if (isEnd(childrenLength)) {
            return list.appendChild(viewEl);
          } else if (position === 0) {
            return list.insertBefore(viewEl, children[position]);
          } else {
            last = children[position - 1];
            if (list.lastChild === last) {
              return list.appendChild(viewEl);
            } else {
              return list.insertBefore(viewEl, last.nextElementSibling);
            }
          }
        }
      } else if (isEnd(length)) {
        return list.appendChild(viewEl);
      } else {
        return list.insertBefore(viewEl, list.firstChild);
      }
    };
  }
})();

module.exports = CollectionView = (function(superClass) {
  extend(CollectionView, superClass);

  CollectionView.prototype.itemView = null;

  CollectionView.prototype.autoRender = true;

  CollectionView.prototype.renderItems = true;

  CollectionView.prototype.animationDuration = 500;

  CollectionView.prototype.useCssAnimation = false;

  CollectionView.prototype.animationStartClass = 'animated-item-view';

  CollectionView.prototype.animationEndClass = 'animated-item-view-end';

  CollectionView.prototype.listSelector = null;

  CollectionView.prototype.$list = null;

  CollectionView.prototype.fallbackSelector = null;

  CollectionView.prototype.$fallback = null;

  CollectionView.prototype.loadingSelector = null;

  CollectionView.prototype.$loading = null;

  CollectionView.prototype.itemSelector = null;

  CollectionView.prototype.filterer = null;

  CollectionView.prototype.filterCallback = function(view, included) {
    if ($) {
      view.$el.stop(true, true);
    }
    return toggleElement(($ ? view.$el : view.el), included);
  };

  CollectionView.prototype.visibleItems = null;

  CollectionView.prototype.optionNames = View.prototype.optionNames.concat(['renderItems', 'itemView']);

  function CollectionView(options) {
    this.renderAllItems = bind(this.renderAllItems, this);
    this.toggleFallback = bind(this.toggleFallback, this);
    this.itemsReset = bind(this.itemsReset, this);
    this.itemRemoved = bind(this.itemRemoved, this);
    this.itemAdded = bind(this.itemAdded, this);
    this.visibleItems = [];
    CollectionView.__super__.constructor.apply(this, arguments);
  }

  CollectionView.prototype.initialize = function(options) {
    if (options == null) {
      options = {};
    }
    this.addCollectionListeners();
    if (options.filterer != null) {
      return this.filter(options.filterer);
    }
  };

  CollectionView.prototype.addCollectionListeners = function() {
    this.listenTo(this.collection, 'add', this.itemAdded);
    this.listenTo(this.collection, 'remove', this.itemRemoved);
    return this.listenTo(this.collection, 'reset sort', this.itemsReset);
  };

  CollectionView.prototype.getTemplateData = function() {
    var templateData;
    templateData = {
      length: this.collection.length
    };
    if (typeof this.collection.isSynced === 'function') {
      templateData.synced = this.collection.isSynced();
    }
    return templateData;
  };

  CollectionView.prototype.getTemplateFunction = function() {};

  CollectionView.prototype.render = function() {
    var listSelector;
    CollectionView.__super__.render.apply(this, arguments);
    listSelector = typeof this.listSelector === 'function' ? this.listSelector() : this.listSelector;
    if ($) {
      this.$list = listSelector ? this.find(listSelector) : this.$el;
    } else {
      this.list = listSelector ? this.find(this.listSelector) : this.el;
    }
    this.initFallback();
    this.initLoadingIndicator();
    if (this.renderItems) {
      return this.renderAllItems();
    }
  };

  CollectionView.prototype.itemAdded = function(item, collection, options) {
    return this.insertView(item, this.renderItem(item), options.at);
  };

  CollectionView.prototype.itemRemoved = function(item) {
    return this.removeViewForItem(item);
  };

  CollectionView.prototype.itemsReset = function() {
    return this.renderAllItems();
  };

  CollectionView.prototype.initFallback = function() {
    if (!this.fallbackSelector) {
      return;
    }
    if ($) {
      this.$fallback = this.find(this.fallbackSelector);
    } else {
      this.fallback = this.find(this.fallbackSelector);
    }
    this.on('visibilityChange', this.toggleFallback);
    this.listenTo(this.collection, 'syncStateChange', this.toggleFallback);
    return this.toggleFallback();
  };

  CollectionView.prototype.toggleFallback = function() {
    var visible;
    visible = this.visibleItems.length === 0 && (typeof this.collection.isSynced === 'function' ? this.collection.isSynced() : true);
    return toggleElement(($ ? this.$fallback : this.fallback), visible);
  };

  CollectionView.prototype.initLoadingIndicator = function() {
    if (!(this.loadingSelector && typeof this.collection.isSyncing === 'function')) {
      return;
    }
    if ($) {
      this.$loading = this.find(this.loadingSelector);
    } else {
      this.loading = this.find(this.loadingSelector);
    }
    this.listenTo(this.collection, 'syncStateChange', this.toggleLoadingIndicator);
    return this.toggleLoadingIndicator();
  };

  CollectionView.prototype.toggleLoadingIndicator = function() {
    var visible;
    visible = this.collection.length === 0 && this.collection.isSyncing();
    return toggleElement(($ ? this.$loading : this.loading), visible);
  };

  CollectionView.prototype.getItemViews = function() {
    var i, itemViews, key, len, ref;
    itemViews = {};
    ref = Object.keys(this.subviewsByName);
    for (i = 0, len = ref.length; i < len; i++) {
      key = ref[i];
      if (!key.indexOf('itemView:')) {
        itemViews[key.slice(9)] = this.subviewsByName[key];
      }
    }
    return itemViews;
  };

  CollectionView.prototype.filter = function(filterer, filterCallback) {
    var hasItemViews, i, included, index, item, len, ref, view;
    if (typeof filterer === 'function' || filterer === null) {
      this.filterer = filterer;
    }
    if (typeof filterCallback === 'function' || filterCallback === null) {
      this.filterCallback = filterCallback;
    }
    hasItemViews = Object.keys(this.subviewsByName).some(function(key) {
      return 0 === key.indexOf('itemView:');
    });
    if (hasItemViews) {
      ref = this.collection.models;
      for (index = i = 0, len = ref.length; i < len; index = ++i) {
        item = ref[index];
        included = typeof this.filterer === 'function' ? this.filterer(item, index) : true;
        view = this.subview("itemView:" + item.cid);
        if (!view) {
          throw new Error('CollectionView#filter: ' + ("no view found for " + item.cid));
        }
        this.filterCallback(view, included);
        this.updateVisibleItems(view.model, included, false);
      }
    }
    return this.trigger('visibilityChange', this.visibleItems);
  };

  CollectionView.prototype.renderAllItems = function() {
    var cid, i, index, item, items, j, k, len, len1, len2, ref, remainingViewsByCid, view;
    items = this.collection.models;
    this.visibleItems.length = 0;
    remainingViewsByCid = {};
    for (i = 0, len = items.length; i < len; i++) {
      item = items[i];
      view = this.subview("itemView:" + item.cid);
      if (view) {
        remainingViewsByCid[item.cid] = view;
      }
    }
    ref = Object.keys(this.getItemViews());
    for (j = 0, len1 = ref.length; j < len1; j++) {
      cid = ref[j];
      if (!(cid in remainingViewsByCid)) {
        this.removeSubview("itemView:" + cid);
      }
    }
    for (index = k = 0, len2 = items.length; k < len2; index = ++k) {
      item = items[index];
      view = this.subview("itemView:" + item.cid);
      if (view) {
        this.insertView(item, view, index, false);
      } else {
        this.insertView(item, this.renderItem(item), index);
      }
    }
    if (items.length === 0) {
      return this.trigger('visibilityChange', this.visibleItems);
    }
  };

  CollectionView.prototype.renderItem = function(item) {
    var view;
    view = this.subview("itemView:" + item.cid);
    if (!view) {
      view = this.initItemView(item);
      this.subview("itemView:" + item.cid, view);
    }
    view.render();
    return view;
  };

  CollectionView.prototype.initItemView = function(model) {
    if (this.itemView) {
      return new this.itemView({
        autoRender: false,
        model: model
      });
    } else {
      throw new Error('The CollectionView#itemView property ' + 'must be defined or the initItemView() must be overridden.');
    }
  };

  CollectionView.prototype.insertView = function(item, view, position, enableAnimation) {
    var elem, included, length, list;
    if (enableAnimation == null) {
      enableAnimation = true;
    }
    if (this.animationDuration === 0) {
      enableAnimation = false;
    }
    if (typeof position !== 'number') {
      position = this.collection.indexOf(item);
    }
    included = typeof this.filterer === 'function' ? this.filterer(item, position) : true;
    elem = $ ? view.$el : view.el;
    if (included && enableAnimation) {
      startAnimation(elem, this.useCssAnimation, this.animationStartClass);
    }
    if (this.filterer) {
      this.filterCallback(view, included);
    }
    length = this.collection.length;
    list = $ ? this.$list : this.list;
    if (included) {
      insertView(list, elem, position, length, this.itemSelector);
      view.trigger('addedToParent');
    }
    this.updateVisibleItems(item, included);
    if (included && enableAnimation) {
      if (this.useCssAnimation) {
        setTimeout((function(_this) {
          return function() {
            return addClass(elem, _this.animationEndClass);
          };
        })(this));
      } else {
        endAnimation(elem, this.animationDuration);
      }
    }
    return view;
  };

  CollectionView.prototype.removeViewForItem = function(item) {
    this.updateVisibleItems(item, false);
    return this.removeSubview("itemView:" + item.cid);
  };

  CollectionView.prototype.updateVisibleItems = function(item, includedInFilter, triggerEvent) {
    var includedInVisibleItems, visibilityChanged, visibleItemsIndex;
    if (triggerEvent == null) {
      triggerEvent = true;
    }
    visibilityChanged = false;
    visibleItemsIndex = this.visibleItems.indexOf(item);
    includedInVisibleItems = visibleItemsIndex !== -1;
    if (includedInFilter && !includedInVisibleItems) {
      this.visibleItems.push(item);
      visibilityChanged = true;
    } else if (!includedInFilter && includedInVisibleItems) {
      this.visibleItems.splice(visibleItemsIndex, 1);
      visibilityChanged = true;
    }
    if (visibilityChanged && triggerEvent) {
      this.trigger('visibilityChange', this.visibleItems);
    }
    return visibilityChanged;
  };

  CollectionView.prototype.dispose = function() {
    var i, len, prop, ref;
    if (this.disposed) {
      return;
    }
    ref = ['$list', '$fallback', '$loading', 'visibleItems'];
    for (i = 0, len = ref.length; i < len; i++) {
      prop = ref[i];
      delete this[prop];
    }
    return CollectionView.__super__.dispose.call(this);
  };

  return CollectionView;

})(View);


},{"../lib/utils":13,"./view":19,"backbone":"backbone"}],18:[function(require,module,exports){
'use strict';
var $, Backbone, EventBroker, Layout, View, _, mediator, utils,
  bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; },
  extend = function(child, parent) { for (var key in parent) { if (hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; },
  hasProp = {}.hasOwnProperty;

_ = require('underscore');

Backbone = require('backbone');

View = require('./view');

EventBroker = require('../lib/event_broker');

utils = require('../lib/utils');

mediator = require('../mediator');

$ = Backbone.$;

module.exports = Layout = (function(superClass) {
  extend(Layout, superClass);

  Layout.prototype.el = 'body';

  Layout.prototype.keepElement = true;

  Layout.prototype.title = '';

  Layout.prototype.globalRegions = null;

  Layout.prototype.listen = {
    'beforeControllerDispose mediator': 'scroll'
  };

  function Layout(options) {
    if (options == null) {
      options = {};
    }
    this.openLink = bind(this.openLink, this);
    this.globalRegions = [];
    this.title = options.title;
    if (options.regions) {
      this.regions = options.regions;
    }
    this.settings = _.defaults(options, {
      titleTemplate: function(data) {
        var st;
        st = data.subtitle ? data.subtitle + " \u2013 " : '';
        return st + data.title;
      },
      openExternalToBlank: false,
      routeLinks: 'a, .go-to',
      skipRouting: '.noscript',
      scrollTo: [0, 0]
    });
    mediator.setHandler('region:show', this.showRegion, this);
    mediator.setHandler('region:register', this.registerRegionHandler, this);
    mediator.setHandler('region:unregister', this.unregisterRegionHandler, this);
    mediator.setHandler('region:find', this.regionByName, this);
    mediator.setHandler('adjustTitle', this.adjustTitle, this);
    Layout.__super__.constructor.apply(this, arguments);
    if (this.settings.routeLinks) {
      this.startLinkRouting();
    }
  }

  Layout.prototype.scroll = function() {
    var to, x, y;
    to = this.settings.scrollTo;
    if (to && typeof to === 'object') {
      x = to[0], y = to[1];
      return window.scrollTo(x, y);
    }
  };

  Layout.prototype.adjustTitle = function(subtitle) {
    var title;
    if (subtitle == null) {
      subtitle = '';
    }
    title = this.settings.titleTemplate({
      title: this.title,
      subtitle: subtitle
    });
    document.title = title;
    this.publishEvent('adjustTitle', subtitle, title);
    return title;
  };

  Layout.prototype.startLinkRouting = function() {
    var route;
    route = this.settings.routeLinks;
    if (route) {
      return this.delegate('click', route, this.openLink);
    }
  };

  Layout.prototype.stopLinkRouting = function() {
    var route;
    route = this.settings.routeLinks;
    if (route) {
      return this.undelegate('click', route);
    }
  };

  Layout.prototype.isExternalLink = function(link) {
    var host, protocol, target;
    if (!utils.matchesSelector(link, 'a, area')) {
      return false;
    }
    if (link.download) {
      return true;
    }
    if (!link.host) {
      link.href += '';
    }
    protocol = location.protocol, host = location.host;
    target = link.target;
    return target === '_blank' || link.rel === 'external' || link.protocol !== protocol || link.host !== host || (target === '_parent' && parent !== self) || (target === '_top' && top !== self);
  };

  Layout.prototype.openLink = function(event) {
    var el, href, skipRouting;
    if (utils.modifierKeyPressed(event)) {
      return;
    }
    el = $ ? event.currentTarget : event.delegateTarget;
    href = el.getAttribute('href') || el.getAttribute('data-href');
    if (!href || href[0] === '#') {
      return;
    }
    skipRouting = this.settings.skipRouting;
    switch (typeof skipRouting) {
      case 'function':
        if (!skipRouting(href, el)) {
          return;
        }
        break;
      case 'string':
        if (utils.matchesSelector(el, skipRouting)) {
          return;
        }
    }
    if (this.isExternalLink(el)) {
      if (this.settings.openExternalToBlank) {
        event.preventDefault();
        this.openWindow(href);
      }
      return;
    }
    utils.redirectTo({
      url: href
    });
    return event.preventDefault();
  };

  Layout.prototype.openWindow = function(href) {
    return window.open(href);
  };

  Layout.prototype.registerRegionHandler = function(instance, name, selector) {
    if (name != null) {
      return this.registerGlobalRegion(instance, name, selector);
    } else {
      return this.registerGlobalRegions(instance);
    }
  };

  Layout.prototype.registerGlobalRegion = function(instance, name, selector) {
    this.unregisterGlobalRegion(instance, name);
    return this.globalRegions.unshift({
      instance: instance,
      name: name,
      selector: selector
    });
  };

  Layout.prototype.registerGlobalRegions = function(instance) {
    var i, len, name, ref, selector, version;
    ref = utils.getAllPropertyVersions(instance, 'regions');
    for (i = 0, len = ref.length; i < len; i++) {
      version = ref[i];
      for (name in version) {
        selector = version[name];
        this.registerGlobalRegion(instance, name, selector);
      }
    }
  };

  Layout.prototype.unregisterRegionHandler = function(instance, name) {
    if (name != null) {
      return this.unregisterGlobalRegion(instance, name);
    } else {
      return this.unregisterGlobalRegions(instance);
    }
  };

  Layout.prototype.unregisterGlobalRegion = function(instance, name) {
    var cid, region;
    cid = instance.cid;
    return this.globalRegions = (function() {
      var i, len, ref, results;
      ref = this.globalRegions;
      results = [];
      for (i = 0, len = ref.length; i < len; i++) {
        region = ref[i];
        if (region.instance.cid !== cid || region.name !== name) {
          results.push(region);
        }
      }
      return results;
    }).call(this);
  };

  Layout.prototype.unregisterGlobalRegions = function(instance) {
    var region;
    return this.globalRegions = (function() {
      var i, len, ref, results;
      ref = this.globalRegions;
      results = [];
      for (i = 0, len = ref.length; i < len; i++) {
        region = ref[i];
        if (region.instance.cid !== instance.cid) {
          results.push(region);
        }
      }
      return results;
    }).call(this);
  };

  Layout.prototype.regionByName = function(name) {
    var i, len, ref, reg;
    ref = this.globalRegions;
    for (i = 0, len = ref.length; i < len; i++) {
      reg = ref[i];
      if (reg.name === name && !reg.instance.stale) {
        return reg;
      }
    }
  };

  Layout.prototype.showRegion = function(name, instance) {
    var region;
    region = this.regionByName(name);
    if (!region) {
      throw new Error("No region registered under " + name);
    }
    return instance.container = region.selector === '' ? $ ? region.instance.$el : region.instance.el : region.instance.noWrap ? region.instance.container.find(region.selector) : region.instance.find(region.selector);
  };

  Layout.prototype.dispose = function() {
    var i, len, prop, ref;
    if (this.disposed) {
      return;
    }
    this.stopLinkRouting();
    ref = ['globalRegions', 'title', 'route'];
    for (i = 0, len = ref.length; i < len; i++) {
      prop = ref[i];
      delete this[prop];
    }
    mediator.removeHandlers(this);
    return Layout.__super__.dispose.call(this);
  };

  return Layout;

})(View);


},{"../lib/event_broker":7,"../lib/utils":13,"../mediator":14,"./view":19,"backbone":"backbone","underscore":"underscore"}],19:[function(require,module,exports){
'use strict';
var $, Backbone, EventBroker, View, _, attach, mediator, setHTML, utils,
  extend = function(child, parent) { for (var key in parent) { if (hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; },
  hasProp = {}.hasOwnProperty,
  indexOf = [].indexOf || function(item) { for (var i = 0, l = this.length; i < l; i++) { if (i in this && this[i] === item) return i; } return -1; };

_ = require('underscore');

Backbone = require('backbone');

EventBroker = require('../lib/event_broker');

utils = require('../lib/utils');

mediator = require('../mediator');

$ = Backbone.$;

setHTML = (function() {
  if ($) {
    return function(view, html) {
      view.$el.html(html);
      return html;
    };
  } else {
    return function(view, html) {
      return view.el.innerHTML = html;
    };
  }
})();

attach = (function() {
  if ($) {
    return function(view) {
      var actual;
      actual = $(view.container);
      if (typeof view.containerMethod === 'function') {
        return view.containerMethod(actual, view.el);
      } else {
        return actual[view.containerMethod](view.el);
      }
    };
  } else {
    return function(view) {
      var actual;
      actual = typeof view.container === 'string' ? document.querySelector(view.container) : view.container;
      if (typeof view.containerMethod === 'function') {
        return view.containerMethod(actual, view.el);
      } else {
        return actual[view.containerMethod](view.el);
      }
    };
  }
})();

module.exports = View = (function(superClass) {
  extend(View, superClass);

  _.extend(View.prototype, EventBroker);

  View.prototype.autoRender = false;

  View.prototype.autoAttach = true;

  View.prototype.container = null;

  View.prototype.containerMethod = $ ? 'append' : 'appendChild';

  View.prototype.regions = null;

  View.prototype.region = null;

  View.prototype.stale = false;

  View.prototype.noWrap = false;

  View.prototype.keepElement = false;

  View.prototype.subviews = null;

  View.prototype.subviewsByName = null;

  View.prototype.optionNames = ['autoAttach', 'autoRender', 'container', 'containerMethod', 'region', 'regions', 'noWrap'];

  function View(options) {
    var i, key, len, ref, region, render;
    if (options == null) {
      options = {};
    }
    ref = Object.keys(options);
    for (i = 0, len = ref.length; i < len; i++) {
      key = ref[i];
      if (indexOf.call(this.optionNames, key) >= 0) {
        this[key] = options[key];
      }
    }
    render = this.render;
    this.render = function() {
      var returnValue;
      if (this.disposed) {
        return false;
      }
      returnValue = render.apply(this, arguments);
      if (this.autoAttach) {
        this.attach.apply(this, arguments);
      }
      return returnValue;
    };
    this.subviews = [];
    this.subviewsByName = {};
    if (this.noWrap) {
      if (this.region) {
        region = mediator.execute('region:find', this.region);
        if (region != null) {
          this.el = region.instance.container != null ? region.instance.region != null ? $(region.instance.container).find(region.selector) : region.instance.container : region.instance.$(region.selector);
        }
      }
      if (this.container) {
        this.el = this.container;
      }
    }
    View.__super__.constructor.apply(this, arguments);
    this.delegateListeners();
    if (this.model) {
      this.listenTo(this.model, 'dispose', this.dispose);
    }
    if (this.collection) {
      this.listenTo(this.collection, 'dispose', (function(_this) {
        return function(subject) {
          if (!subject || subject === _this.collection) {
            return _this.dispose();
          }
        };
      })(this));
    }
    if (this.regions != null) {
      mediator.execute('region:register', this);
    }
    if (this.autoRender) {
      this.render();
    }
  }

  View.prototype.find = function(selector) {
    if ($) {
      return this.$el.find(selector);
    } else {
      return this.el.querySelector(selector);
    }
  };

  View.prototype.delegate = function(eventName, second, third) {
    var bound, event, events, handler, i, len, ref, selector;
    if (typeof eventName !== 'string') {
      throw new TypeError('View#delegate: first argument must be a string');
    }
    switch (arguments.length) {
      case 2:
        handler = second;
        break;
      case 3:
        selector = second;
        handler = third;
        if (typeof selector !== 'string') {
          throw new TypeError('View#delegate: ' + 'second argument must be a string');
        }
        break;
      default:
        throw new TypeError('View#delegate: ' + 'only two or three arguments are allowed');
    }
    if (typeof handler !== 'function') {
      throw new TypeError('View#delegate: ' + 'handler argument must be function');
    }
    bound = handler.bind(this);
    if ($) {
      events = eventName.split(' ').map((function(_this) {
        return function(name) {
          return name + ".delegateEvents" + _this.cid;
        };
      })(this)).join(' ');
      this.$el.on(events, selector, bound);
    } else {
      ref = eventName.split(' ');
      for (i = 0, len = ref.length; i < len; i++) {
        event = ref[i];
        View.__super__.delegate.call(this, event, selector, bound);
      }
    }
    return bound;
  };

  View.prototype._delegateEvents = function(events) {
    var handler, i, key, len, match, ref, value;
    ref = Object.keys(events);
    for (i = 0, len = ref.length; i < len; i++) {
      key = ref[i];
      value = events[key];
      handler = typeof value === 'function' ? value : this[value];
      if (!handler) {
        throw new Error("Method `" + value + "` does not exist");
      }
      match = /^(\S+)\s*(.*)$/.exec(key);
      this.delegate(match[1], match[2], handler);
    }
  };

  View.prototype.delegateEvents = function(events, keepOld) {
    var classEvents, i, len, ref;
    if (!keepOld) {
      this.undelegateEvents();
    }
    if (events) {
      return this._delegateEvents(events);
    }
    ref = utils.getAllPropertyVersions(this, 'events');
    for (i = 0, len = ref.length; i < len; i++) {
      classEvents = ref[i];
      if (typeof classEvents === 'function') {
        classEvents = classEvents.call(this);
      }
      this._delegateEvents(classEvents);
    }
  };

  View.prototype.undelegate = function(eventName, second) {
    var events, selector;
    if (eventName == null) {
      eventName = '';
    }
    if (typeof eventName !== 'string') {
      throw new TypeError('View#undelegate: first argument must be a string');
    }
    switch (arguments.length) {
      case 2:
        if (typeof second === 'string') {
          selector = second;
        }
        break;
      case 3:
        selector = second;
        if (typeof selector !== 'string') {
          throw new TypeError('View#undelegate: ' + 'second argument must be a string');
        }
    }
    if ($) {
      events = eventName.split(' ').map((function(_this) {
        return function(name) {
          return name + ".delegateEvents" + _this.cid;
        };
      })(this)).join(' ');
      return this.$el.off(events, selector);
    } else {
      if (eventName) {
        return View.__super__.undelegate.call(this, eventName, selector);
      } else {
        return this.undelegateEvents();
      }
    }
  };

  View.prototype.delegateListeners = function() {
    var eventName, i, j, key, len, len1, method, ref, ref1, ref2, target, version;
    if (!this.listen) {
      return;
    }
    ref = utils.getAllPropertyVersions(this, 'listen');
    for (i = 0, len = ref.length; i < len; i++) {
      version = ref[i];
      if (typeof version === 'function') {
        version = version.call(this);
      }
      ref1 = Object.keys(version);
      for (j = 0, len1 = ref1.length; j < len1; j++) {
        key = ref1[j];
        method = version[key];
        if (typeof method !== 'function') {
          method = this[method];
        }
        if (typeof method !== 'function') {
          throw new Error('View#delegateListeners: ' + ("listener for `" + key + "` must be function"));
        }
        ref2 = key.split(' '), eventName = ref2[0], target = ref2[1];
        this.delegateListener(eventName, target, method);
      }
    }
  };

  View.prototype.delegateListener = function(eventName, target, callback) {
    var prop;
    if (target === 'model' || target === 'collection') {
      prop = this[target];
      if (prop) {
        this.listenTo(prop, eventName, callback);
      }
    } else if (target === 'mediator') {
      this.subscribeEvent(eventName, callback);
    } else if (!target) {
      this.on(eventName, callback, this);
    }
  };

  View.prototype.registerRegion = function(name, selector) {
    return mediator.execute('region:register', this, name, selector);
  };

  View.prototype.unregisterRegion = function(name) {
    return mediator.execute('region:unregister', this, name);
  };

  View.prototype.unregisterAllRegions = function() {
    return mediator.execute({
      name: 'region:unregister',
      silent: true
    }, this);
  };

  View.prototype.subview = function(name, view) {
    var byName, subviews;
    subviews = this.subviews;
    byName = this.subviewsByName;
    if (name && view) {
      this.removeSubview(name);
      subviews.push(view);
      byName[name] = view;
      return view;
    } else if (name) {
      return byName[name];
    }
  };

  View.prototype.removeSubview = function(nameOrView) {
    var byName, index, name, subviews, view;
    if (!nameOrView) {
      return;
    }
    subviews = this.subviews;
    byName = this.subviewsByName;
    if (typeof nameOrView === 'string') {
      name = nameOrView;
      view = byName[name];
    } else {
      view = nameOrView;
      Object.keys(byName).some(function(key) {
        if (byName[key] === view) {
          return name = key;
        }
      });
    }
    if (!(name && (view != null ? view.dispose : void 0))) {
      return;
    }
    view.dispose();
    index = subviews.indexOf(view);
    if (index !== -1) {
      subviews.splice(index, 1);
    }
    return delete byName[name];
  };

  View.prototype.getTemplateData = function() {
    var data, source;
    data = this.model ? utils.serialize(this.model) : this.collection ? {
      items: utils.serialize(this.collection),
      length: this.collection.length
    } : {};
    source = this.model || this.collection;
    if (source) {
      if (typeof source.isSynced === 'function' && !('synced' in data)) {
        data.synced = source.isSynced();
      }
    }
    return data;
  };

  View.prototype.getTemplateFunction = function() {
    throw new Error('View#getTemplateFunction must be overridden');
  };

  View.prototype.render = function() {
    var el, html, templateFunc;
    if (this.disposed) {
      return false;
    }
    templateFunc = this.getTemplateFunction();
    if (typeof templateFunc === 'function') {
      html = templateFunc(this.getTemplateData());
      if (this.noWrap) {
        el = document.createElement('div');
        el.innerHTML = html;
        if (el.children.length > 1) {
          throw new Error('There must be a single top-level element ' + 'when using `noWrap`');
        }
        this.undelegateEvents();
        this.setElement(el.firstChild, true);
      } else {
        setHTML(this, html);
      }
    }
    return this;
  };

  View.prototype.attach = function() {
    if (this.region != null) {
      mediator.execute('region:show', this.region, this);
    }
    if (this.container && !document.body.contains(this.el)) {
      attach(this);
      return this.trigger('addedToDOM');
    }
  };

  View.prototype.disposed = false;

  View.prototype.dispose = function() {
    var i, j, len, len1, prop, ref, ref1, subview;
    if (this.disposed) {
      return;
    }
    this.unregisterAllRegions();
    ref = this.subviews;
    for (i = 0, len = ref.length; i < len; i++) {
      subview = ref[i];
      subview.dispose();
    }
    this.unsubscribeAllEvents();
    this.off();
    if (this.keepElement) {
      this.undelegateEvents();
      this.undelegate();
      this.stopListening();
    } else {
      this.remove();
    }
    ref1 = ['el', '$el', 'options', 'model', 'collection', 'subviews', 'subviewsByName', '_callbacks'];
    for (j = 0, len1 = ref1.length; j < len1; j++) {
      prop = ref1[j];
      delete this[prop];
    }
    this.disposed = true;
    return Object.freeze(this);
  };

  return View;

})(Backbone.NativeView || Backbone.View);


},{"../lib/event_broker":7,"../lib/utils":13,"../mediator":14,"backbone":"backbone","underscore":"underscore"}]},{},[1])
return require(1);
});
