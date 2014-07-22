/*jslint nomen:true*/
/*global define*/
define([
    './../base/page-region-view',
    'underscore',
    'jquery',
    'oroui/js/mediator'
], function (PageRegionView, _, $, mediator) {
    'use strict';

    var PageMainMenuView;

    PageMainMenuView = PageRegionView.extend({
        template: function (data) {
            return data.mainMenu;
        },
        pageItems: ['mainMenu', 'currentRoute'],

        initialize: function (options) {
            // Array of search callback, that match route to menu item
            this.routeMatchSearchers = [];
            // Local cache of route to menu item
            this.routeMatchedMenuItemsCache = {};

            PageMainMenuView.__super__.initialize.call(this, options);
        },

        render: function () {
            var data = this.getTemplateData();
            if (data) {
                if (!_.isUndefined(data.mainMenu)) {
                    PageMainMenuView.__super__.render.call(this);
                    this.initRouteMatches();
                } else if (!_.isUndefined(data.currentRoute)) {
                    this.toggleActiveMenuItem(data.currentRoute);
                }
            } else {
                this.initRouteMatches();
            }

            mediator.trigger('mainMenuUpdated', this);
            this.$el.trigger('mainMenuUpdated');
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
                    matcherCallback = function (route) {
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
                    _.each($el.data('routes'), function (matchRule) {
                        self.routeMatchSearchers.push(createRouteSearchCallback(matchRule, $el));
                    });
                });
        },

        /**
         * Get active menu item element.
         *
         * @param {String} route
         * @returns {HTMLElement}
         */
        getMatchedMenuItem: function(route) {
            if (this.routeMatchedMenuItemsCache.hasOwnProperty(route)) {
                return this.routeMatchedMenuItemsCache[route];
            }

            var match = this.$el.find('[data-route="' + route + '"]');
            if (!match.length) {
                for (var i = 0; i < this.routeMatchSearchers.length; i++) {
                    match = this.routeMatchSearchers[i](route);
                    if (!_.isUndefined(match)) {
                        break;
                    }
                }
            }

            if (match && match.length) {
                this.routeMatchedMenuItemsCache[route] = match;
                return match;
            }
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
