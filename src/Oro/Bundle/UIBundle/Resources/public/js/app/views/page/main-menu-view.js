define([
    './../base/page-region-view',
    'underscore',
    'jquery',
    'oroui/js/mediator'
], function(PageRegionView, _, $, mediator) {
    'use strict';

    var PageMainMenuView;

    PageMainMenuView = PageRegionView.extend({
        template: function(data) {
            return data.mainMenu;
        },
        pageItems: ['mainMenu', 'currentRoute'],

        initialize: function(options) {
            // Array of search callback, that match route to menu item
            this.routeMatchSearchers = [];
            // Local cache of route to menu item
            this.routeMatchedMenuItemsCache = {};

            PageMainMenuView.__super__.initialize.call(this, options);
        },

        render: function() {
            var data = this.getTemplateData();
            var currentRoute = this.getCurrentRoute(data);

            if (data) {
                if (!_.isUndefined(data.mainMenu)) {
                    PageMainMenuView.__super__.render.call(this);
                    this.initRouteMatches();
                } else {
                    this._deferredRender();
                    this.initLayout()
                        .done(_.bind(this._resolveDeferredRender, this));
                }
            } else {
                this.initRouteMatches();
            }

            this.toggleActiveMenuItem(currentRoute);

            mediator.trigger('mainMenuUpdated', this);
            this.$el.trigger('mainMenuUpdated');

            return this;
        },

        /**
         * Defines current route name
         * @param {Object=} data
         * @returns {string}
         */
        getCurrentRoute: function(data) {
            return (data && data.currentRoute) ||
                mediator.execute('retrieveOption', 'startRouteName');
        },

        /**
         * Initialize route matcher callbacks.
         */
        initRouteMatches: function() {
            this.routeMatchSearchers = [];
            this.routeMatchedMenuItemsCache = {};
            var createRouteSearchCallback = function(matchRule, $el) {
                var matcherCallback;
                if (matchRule.indexOf('*') > -1 || matchRule.indexOf('/') > -1) {
                    if (matchRule.indexOf('*') > -1) {
                        matchRule = '^' + matchRule.replace('*', '\\w+') + '$';
                    } else {
                        matchRule = matchRule.replace(/^\/|\/$/g, '');
                    }
                    // RegExp matcher
                    matcherCallback = function(route) {
                        var matchRegExp = new RegExp(matchRule, 'ig');
                        if (matchRegExp.test(route)) {
                            return $el;
                        }
                    };
                } else {
                    // Simple equal matcher
                    matcherCallback = function(route) {
                        if (route === matchRule) {
                            return $el;
                        }
                    };
                }

                return matcherCallback;
            };

            var self = this;
            this.$el
                .find('[data-routes]')
                .each(function(idx, el) {
                    var $el = $(el);
                    _.each($el.data('routes'), function(matchRule) {
                        self.routeMatchSearchers.push(createRouteSearchCallback(matchRule, $el));
                    });
                });
        },

        /**
         * Get active menu item element.
         *
         * @param {string} route
         * @returns {jQuery.Element}
         */
        getMatchedMenuItem: function(route) {
            var match;
            if (this.routeMatchedMenuItemsCache.hasOwnProperty(route)) {
                match = this.routeMatchedMenuItemsCache[route];
            } else {
                match = this.$el.find('[data-route="' + route + '"]');
                if (!match.length) {
                    _.find(this.routeMatchSearchers, function(searcher) {
                        match = searcher(route);
                        return match;
                    });
                }
            }

            if (match && match.length) {
                this.routeMatchedMenuItemsCache[route] = match;
                if (match.length > 1) {
                    match = _.find(match, function(el) {
                        var link = $(el).find('a[href]:first')[0];
                        return link ? mediator.execute('compareUrl', link.pathname) : false;
                    });
                }
            }

            return $(match);
        },

        /**
         * Add active CSS class to menu item and it's parents.
         *
         * @param {String} route
         */
        toggleActiveMenuItem: function(route) {
            var item = this.getMatchedMenuItem(route);
            if (!_.isUndefined(item)) {
                this.$el
                    .find('.active')
                    .removeClass('active');
                item.addClass('active');
                item.parents('.dropdown').addClass('active');
            }
        },

        /**
         * Get labels of active menu items.
         *
         * @returns {Array}
         */
        getActiveItems: function() {
            var activeMenuItemLabels = [];
            this.$el
                .find('.active')
                .each(function(idx, el) {
                    activeMenuItemLabels.push($.trim($(el).find('.title').first().text()));
                });

            return activeMenuItemLabels;
        }
    });

    return PageMainMenuView;
});
