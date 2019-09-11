define(function(require) {
    'use strict';

    var DeviceSwitcherView;
    var document = window.document;
    var location = window.location;
    var history = window.history;
    var COOKIE_KEY = 'demo_version';
    var COOKIE_VALUE = 'mobile';
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var DeviceInnerPageView = require('oroviewswitcher/js/app/views/device-inner-page-view');
    var LoadingBarView = require('oroui/js/app/views/loading-bar-view');
    var persistentStorage = require('oroui/js/persistent-storage');
    var config = require('module').config();
    var stateDefault = {
        items: [{
            name: 'desktop',
            title: 'Desktop',
            mobile: false
        }, {
            name: 'pad',
            title: 'Pad',
            mobile: true
        }, {
            name: 'phone',
            title: 'Phone',
            mobile: true
        }]
    };

    DeviceSwitcherView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'updateUrlDeviceFragment', 'updateFaviconPage',
            'state', 'pageModel', 'switcherStyle'
        ]),
        /**
         * @type {String}
         */
        urlBase: '/',

        /**
         * @type {String}
         */
        logoutUrl: '/',

        /**
         * @type {String}
         */
        frameUrlSegment: '/index.php',

        /**
         * @type {RegExp}
         */
        urlRegExp: null,

        /**
         * @type {String}
         */
        frameName: 'port',

        /**
         * @type {String}
         */
        frameTitle: 'Oro demo application',

        /**
         * @type {String}
         */
        originalTitle: null,

        /**
         * @type {Object}
         */
        loadingView: null,

        /**
         * @type {String}
         */
        loadingTrigger: '[data-role="login-btn"], [data-click-action="logout"]',

        /**
         * @type {String}
         */
        logoutSelector: '[data-click-action="logout"]',

        /**
         * @type {Boolean}
         */
        _hideLogo: false,

        /**
         * @type {Backbone.Model}
         */
        pageModel: null,

        /**
         * @type {Boolean}
         */
        updateUrlDeviceFragment: true,

        /**
         * @type {Boolean}
         */
        updateFaviconPage: true,

        /**
         * @type {Object}
         */
        state: stateDefault,

        /**
         * @type {String}
         */
        switcherStyle: null,

        /**
         * @inheritDoc
         */
        listen: {
            'demo-page-action:logout mediator': 'onLogout'
        },

        /**
         * @inheritDoc
         */
        constructor: function DeviceSwitcherView(options) {
            DeviceSwitcherView.__super__.constructor.call(this, options);
        },

        /**
         * Initializes demo page component
         *
         * @param {Object} options
         */
        initialize: function(options) {
            var updateFavicon = _.once(_.bind(this.updateFavicon, this));

            if (config.frameUrlSegment) {
                this.frameUrlSegment = config.frameUrlSegment;
            }
            if (config.state) {
                this.state = config.state;
            }
            this.originalTitle = document.title;

            this._addStyleSheet();
            this.createUrlRegExp();
            this.updateCookie();
            this.createPageView(options._sourceElement[0]);
            document.addEventListener('load', _.bind(function(e) {
                var url;
                var logoutLink;
                var loginLink;
                var logoutHref;
                var htmlElement;
                var iframe = this.getFrameWindow();
                this.iframe = iframe;
                this.hideLoader();
                this.bindLoadingTrigger();
                if (e.target.contentWindow === iframe) {
                    htmlElement = iframe.document.querySelector('html');
                    logoutLink = $(htmlElement).find('a[href*="logout"]')[0];
                    loginLink = $(htmlElement).find('a[href*="login"]')[0];
                    if (logoutLink) {
                        logoutHref = logoutLink.getAttribute('href');
                        this.logoutUrl = logoutHref;
                        if (logoutHref.indexOf(this.frameUrlSegment) < 0) {
                            logoutLink.setAttribute('href', this.frameUrlSegment + logoutHref);
                        }
                    } else if (loginLink) {
                        this.logoutUrl = loginLink.getAttribute('href');
                    }
                    this.startTrackUrlChanges();
                    url = iframe.location.pathname.split(this.frameUrlSegment).pop();
                    this.updateUrl(url);

                    if (this.updateFaviconPage) {
                        updateFavicon();
                    }
                    // add mobile scroll emulation if necessary
                    if (iframe.document.querySelectorAll('.mobile-version').length) {
                        if (iframe.require) {
                            iframe.require(['bowerassets/jquery.nicescroll/dist/jquery.nicescroll.min'], function() {
                                iframe.$('.mobile-version').first().niceScroll({
                                    cursorcolor: 'rgba(0, 0, 0, 0.5)',
                                    cursorborder: 'none',
                                    touchbehavior: true,
                                    zindex: 10000
                                });
                            });
                        } else {
                            // In case when page has no other third-part libraries use OverlayScrollbars since it has no dependencies
                            var script = iframe.document.createElement('script');
                            var bodyElement = htmlElement.querySelector('body');
                            htmlElement.querySelector('head').appendChild(script);
                            script.addEventListener('load', _.bind(function() {
                                new iframe.OverlayScrollbars(bodyElement, {
                                    className: 'os-theme-dark',
                                    resize: 'none',
                                    scrollbars: {
                                        autoHideDelay: 400,
                                        autoHide: 'scroll'
                                    },
                                    overflowBehavior: {
                                        x: 'hidden'
                                    }
                                });
                            }, this));
                            script.src = '/bundles/npmassets/overlayscrollbars/js/OverlayScrollbars.js';
                        }
                    }
                }
            }, this), true);
            this.initLoadingView();
            DeviceSwitcherView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Creates regular expression for URL parsing and fetching viewName part
         */
        createUrlRegExp: function() {
            var views;
            var matcher;
            views = _.map(this.state.items, function(item) {
                return item.name;
            });
            matcher = this.urlBase.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
            matcher += '(' + views.join('|') + ')?';
            this.urlRegExp = new RegExp('^' + matcher + '\\/');
        },

        /**
         * Creates main view for demo page
         *
         * @param {HTMLElement} el
         */
        createPageView: function(el) {
            this.pageView = new DeviceInnerPageView({
                model: this.pageModel,
                el: el,
                data: {
                    frameName: this.frameName,
                    frameTitle: this.frameTitle,
                    items: this.state.items,
                    activeView: this.getActiveView(),
                    url: this.getAppUrl(),
                    hideLogo: this._hideLogo
                }
            });
            this.listenTo(this.pageView, 'view-switch', this.onViewSwitch);
        },

        /**
         * Handles viewName switch
         *
         * @param {script} viewName
         */
        onViewSwitch: function(viewName) {
            var pageView;
            var frameWindow;
            var url = this.getFrameWindow().location.pathname;
            var stateItem = this.getStateItem(viewName);
            var oldStateItem = this.getStateItem(this.getActiveView());
            this.updateUrl(url, viewName);
            this.updateCookie(viewName);
            if (stateItem.mobile !== oldStateItem.mobile) {
                // flag mobile is change -- reload iframe
                this.stopTrackingUrlChanges();
                pageView = this.pageView;
                frameWindow = this.getFrameWindow();
                $(frameWindow).one('unload', function() {
                    pageView.setActiveView(viewName);
                });
                frameWindow.location.reload();
            } else {
                this.pageView.setActiveView(viewName);
            }

            if (!this.updateUrlDeviceFragment) {
                persistentStorage.setItem('currentDevice', viewName);
            }
        },

        /**
         * Fetches viewName from url
         *
         * @returns {string}
         */
        getActiveView: function() {
            var activeView;
            if (this.updateUrlDeviceFragment) {
                var matches = location.pathname.match(this.urlRegExp);
                activeView = matches && matches[1] ? matches[1] : this.state.items[0].name;
            } else {
                var currentDeviceName = persistentStorage.getItem('currentDevice');

                activeView = currentDeviceName ? currentDeviceName : this.state.items[0].name;
            }

            return activeView;
        },

        /**
         * Fetches appUrl part from url
         *
         * @returns {string}
         */
        getAppUrl: function() {
            var url;
            if (location.pathname === '/') {
                url = this.frameUrlSegment + '/';
            } else {
                url = location.pathname.replace(this.urlRegExp, this.frameUrlSegment + '/');
            }
            return url + location.search + location.hash;
        },

        /**
         * Listen to app navigation events and updates the url
         */
        startTrackUrlChanges: function() {
            var frameWindow = this.getFrameWindow();
            // requirejs can be absent on a page (e.i. login page)
            if (frameWindow.require) {
                frameWindow.require(['oroui/js/mediator'], function(appMediator) {
                    this.listenTo(appMediator, 'page:afterChange route:change', function() {
                        this.updateUrl(appMediator.execute('currentUrl'));
                    });
                }.bind(this));
            }
        },

        /**
         * Stops listening to app events
         */
        stopTrackingUrlChanges: function() {
            var require = this.getFrameWindow().require;
            if (require) {
                this.stopListening(require('oroui/js/mediator'));
            }
        },

        /**
         * Combines full url (viewName + appUrl) and updates history
         *
         * @param {string} url
         * @param {string} activeView
         */
        updateUrl: function(url, activeView) {
            var viewName;
            var deviceFragment;
            var title;
            viewName = activeView ? activeView : this.getActiveView();

            deviceFragment = this.updateUrlDeviceFragment ? this.urlBase + viewName : '';

            url = this.urlBase + deviceFragment + url.replace(this.frameUrlSegment, '/').replace(/\/\//g, '/');
            url = url.replace(/\/\//g, '/');
            title = this._isLoginPage(url) ? this._updateOriginalTitle(viewName) : this.getFrameWindow().document.title;
            history.replaceState({}, title, url);

            document.title = title;

            this.updateLoginState();
        },

        /**
         * Updates version cookie
         *
         * @param {string} viewName
         */
        updateCookie: function(viewName) {
            var item;
            var cookie;
            var date;
            viewName = viewName || this.getActiveView();
            cookie = COOKIE_KEY + '=' + COOKIE_VALUE + '; path=/';
            item = this.getStateItem(viewName);
            if (!item.mobile) {
                // if not mobile -- remove cookie
                date = new Date();
                date.setTime(date.getTime() - 1000);
                cookie += '; expires=' + date.toUTCString();
            }
            document.cookie = cookie;
        },

        /**
         * Updates favicon
         */
        updateFavicon: function() {
            var frameIconLink = this.getFrameWindow().document.querySelector('link[rel$=icon]');
            if (frameIconLink === void 0) {
                $('link[rel$=icon]').remove();
            } else {
                if ($('link[rel$=icon]').length === 0) {
                    $('head').append($('<link/>', {rel: frameIconLink.rel, type: 'image/x-icon'}));
                }
                $('link[rel$=icon]').attr('href', frameIconLink.href);
                try {
                    window.localStorage.setItem('favicon', frameIconLink.href);
                } catch (ex) {}
            }
        },

        /**
         * Fetches window object of the frame
         *
         * @returns {Window}
         */
        getFrameWindow: function() {
            return window.frames[this.frameName];
        },

        /**
         * Looks through state items and fetches an item related to the viewName
         *
         * @param {string} viewName
         * @returns {Object}
         */
        getStateItem: function(viewName) {
            var item;
            item = _.find(this.state.items, function(item) {
                return item.name === viewName;
            });
            return item;
        },

        _addStyleSheet: function() {
            if (this.switcherStyle) {
                $(document.head).append('<link rel="stylesheet" href="' + this.switcherStyle + '">');
            }
        },

        /**
         * Check if current page is login page
         * @param url
         * @returns {boolean}
         * @private
         */
        _isLoginPage: function(url) {
            return /login/.test(url);
        },

        /**
         * Update original title with suffix
         * @param viewName
         * @returns {string}
         * @private
         */
        _updateOriginalTitle: function(viewName) {
            return this.originalTitle + ' at ' + this.getStateItem(viewName).title;
        },

        /**
         * Initialize loading bar
         */
        initLoadingView: function() {
            this.loadingView = new LoadingBarView();
        },

        /**
         * Bind click event into iframe
         */
        bindLoadingTrigger: function() {
            var iframe = this.getFrameWindow();

            // Bind click event across iframe
            $(iframe.frameElement).contents().find(this.loadingTrigger).one('click', _.bind(function() {
                this.loadingView.showLoader();
            }, this));

            // Bind click event in the pageView
            this.pageView.$el.find(this.loadingTrigger).one('click', _.bind(function() {
                this.loadingView.showLoader();
            }, this));
        },

        /**
         * Hide loading bar via instance
         */
        hideLoader: function() {
            if (this.loadingView) {
                this.loadingView.hideLoader();
            }
        },

        /**
         * Updates user's state in page model
         */
        updateLoginState: function() {
            var iframe = this.getFrameWindow();

            this.pageModel.set({
                isLoggedIn: !this._isLoginPage(this.getAppUrl()),
                isAdminPanel: iframe.document.styleSheets[0].href.indexOf('/css/themes/oro/') !== -1
            });
        },

        /**
         * User logout and back to login demo page
         */
        onLogout: function() {
            this.iframe.location = this.logoutUrl;
        }
    });

    return DeviceSwitcherView;
});
