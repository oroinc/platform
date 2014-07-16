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
        routeMatchSearchers: [],
        matchesCache: {},

        render: function () {
            var data = this.getTemplateData();
            if (data) {
                if (!_.isUndefined(data.mainMenu)) {
                    PageMainMenuView.__super__.render.call(this);
                    this.initRouteMatches();
                } else if (!_.isUndefined(data.currentRoute)) {
                    this.toggleActiveMenuItem(data.currentRoute);
                    mediator.trigger('mainMenuUpdated', this.getActiveItems());
                }

                this.$el.trigger('mainMenuUpdated');
            } else {
                this.initRouteMatches();
            }
        },

        initRouteMatches: function() {
            var createRouteSearchCallback = function(matchRule, $el) {
                if (matchRule.indexOf('*') > -1 || matchRule.indexOf('/') > -1) {
                    if (matchRule.indexOf('*') > -1) {
                        matchRule = '^' + matchRule.replace('*', '\\w+') + '$';
                    } else {
                        matchRule = matchRule.replace(/^\/|\/$/g, '');
                    }
                    // RegExp matcher
                    return function (route) {
                        var matchRegExp = new RegExp(matchRule, 'ig');
                        if (matchRegExp.test(route)) {
                            return $el;
                        }
                    };
                } else {
                    // Simple equal matcher
                    return function(route) {
                        if (route === matchRule) {
                            return $el;
                        }
                    };
                }
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

        getMatchesMenuItem: function(route) {
            if (this.matchesCache.hasOwnProperty(route)) {
                return this.matchesCache[route];
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
                this.matchesCache[route] = match;
                return match;
            }

            return false;
        },

        toggleActiveMenuItem: function(route) {
            var item = this.getMatchesMenuItem(route);
            if (item) {
                this.$el
                    .find('.active')
                    .removeClass('active');
                item.addClass('active');
                item.parents('.dropdown').addClass('active');
            }
        },

        getActiveItems: function() {
            var breadcrumbs = [];
            this.$el
                .find('.active')
                .each(function(idx, el) {
                    breadcrumbs.push($.trim($(el).find('.title').first().text()));
                });

            return breadcrumbs;
        }
    });

    return PageMainMenuView;
});
