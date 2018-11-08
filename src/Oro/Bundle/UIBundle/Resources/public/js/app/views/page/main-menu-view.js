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

        maxHeightModifier: 50,

        timeout: 100,

        events: function() {
            var events = {};
            if (this.$el.hasClass('main-menu-top')) {
                events = {
                    'mouseenter .dropdown': '_onDropdownMouseEnter',
                    'mouseleave .dropdown': '_onDropdownMouseLeave'
                };
            }
            return events;
        },

        listen: function() {
            var listen = {};
            if (this.$el.hasClass('main-menu-top')) {
                var originalMenuWidth = Math.ceil(this.$('.main-menu').outerWidth());
                listen['layout:reposition mediator'] = _.debounce(function() {
                    this.$el.toggleClass('narrow-mode', this.$el.width() < originalMenuWidth);
                }.bind(this), this.timeout);
            }
            return listen;
        },

        /**
         * @inheritDoc
         */
        constructor: function PageMainMenuView() {
            PageMainMenuView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            // Array of search callback, that match route to menu item
            this.routeMatchSearchers = [];
            // Local cache of route to menu item
            this.routeMatchedMenuItemsCache = {};

            this.positions = this.getPositions();

            this.initRouteMatches();
            PageMainMenuView.__super__.initialize.call(this, options);
        },

        delegateEvents: function(events) {
            PageMainMenuView.__super__.delegateEvents.call(this, events);

            // can't use event delegation, in some cases bubbling will be break
            this.$('a').on('click' + this.eventNamespace(), _.bind(this._onMenuItemClick, this));
        },

        undelegateEvents: function() {
            if (this.$el) {
                this.$('a').off(this.eventNamespace());
            }

            PageMainMenuView.__super__.undelegateEvents.call(this);
        },

        render: function() {
            var data = this.getTemplateData();
            var currentRoute = this.getCurrentRoute(data);

            if (data && !_.isUndefined(data.mainMenu)) {
                PageMainMenuView.__super__.render.call(this);
                this.initRouteMatches();
            }

            this.toggleActiveMenuItem(currentRoute);

            mediator.trigger('mainMenuUpdated', this);
            this.$el.trigger('mainMenuUpdated');

            return this;
        },

        getPositions: function() {
            var start = 'align-menu-start';
            var end = 'align-menu-end';
            var itemStart = 'align-single-item-start';
            var itemEnd = 'align-single-item-end';

            return _.isRTL()
                ? [end, start, itemEnd, itemStart]
                : [start, end, itemStart, itemEnd];
        },

        _onMenuItemClick: function(e) {
            this.hideDropdownScroll($(e.currentTarget));
        },

        _onDropdownMouseEnter: function(e) {
            this.updateDropdownChildAlign($(e.currentTarget));
            this.updateDropdownChildPosition($(e.currentTarget));
            this.updateDropdownScroll($(e.currentTarget));
        },

        _onDropdownMouseLeave: function(e) {
            var dropdowns = $([]);

            if ($(e.currentTarget).hasClass('dropdown-level-1')) {
                dropdowns = dropdowns.add(e.currentTarget);
            }
            dropdowns = dropdowns.add('.dropdown', e.currentTarget);
            dropdowns.removeClass(this.positions.join(' '));
        },

        /**
         * Fix issues with open dropdown after click on menu item
         */
        hideDropdownScroll: function($link) {
            var $scrollable = $link.closest('.dropdown-menu-wrapper__scrollable');
            if (!$scrollable.length || $scrollable.parent().hasClass('accordion')) {
                return;
            }
            $scrollable.addClass('hidden');
        },

        updateDropdownScroll: function($toggle) {
            var $scrollable = $toggle.find('.dropdown-menu-wrapper__scrollable:first');
            if (!$scrollable.length) {
                return;
            }

            $scrollable.removeClass('hidden');

            var $scrollableParent = $scrollable.parent();

            // reset styles to recalc it
            var scrollableHeight = $scrollable.children(':first').outerHeight();
            $scrollable.css('max-height', scrollableHeight + 'px');
            $scrollableParent.css('margin-top', 0);

            var toggleHeight = $toggle.outerHeight();
            var scrollableParentHeight = $scrollableParent.outerHeight();
            var scrollableHeightModifier = 0;

            var scrollableTop = $scrollable.get(0).getBoundingClientRect().top;
            var availableHeight = window.innerHeight - scrollableTop;

            if (scrollableParentHeight <= availableHeight) {
                // scroll are not required
                return;
            }

            var maxHeight = availableHeight - this.maxHeightModifier;
            if (scrollableTop > availableHeight) {
                // change dropdown direction if necessary
                scrollableHeightModifier = scrollableParentHeight - scrollableHeight;

                if (scrollableParentHeight > scrollableTop) {
                    maxHeight = scrollableTop - scrollableHeightModifier - this.maxHeightModifier;
                } else {
                    maxHeight = scrollableHeight;
                }

                var marginTop = -1 * (maxHeight + scrollableHeightModifier) + toggleHeight;
                $scrollableParent.css('margin-top', marginTop + 'px');
            }
            $scrollable.css('max-height', maxHeight + 'px');
        },

        updateDropdownChildAlign: function($node) {
            var limit = this.calculateMenuPosition(this.$el);
            var $innerDropdown = $node.find('.dropdown-menu:first');
            var $innerDropdownChildren = $innerDropdown.children('.dropdown');
            var isDropdownChildrenOutside = false;

            // Align first level
            if ($node.hasClass('dropdown-level-1')) {
                $node.addClass(
                    this.positions[this.calculateMenuPosition($innerDropdown) > limit ? 0: 1]
                );
            }

            if (!$innerDropdownChildren.length) {
                return;
            }

            _.each($innerDropdownChildren, function(element) {
                if (this.calculateMenuPosition($(element).find('.dropdown-menu:first')) > limit) {
                    isDropdownChildrenOutside = true;
                }
            }, this);

            if (isDropdownChildrenOutside) {
                $innerDropdownChildren.addClass(this.positions[0]);
                $node.addClass(this.positions[2]);
            } else {
                $innerDropdownChildren.addClass(this.positions[1]);
                $node.addClass(this.positions[3]);
            }
        },

        calculateMenuPosition: function($element) {
            if (!$element.length) {
                return 0;
            }
            return _.isRTL()
                ? Math.ceil($element.offset().left)
                : Math.ceil($element.offset().left + $element.outerWidth());
        },

        updateDropdownChildPosition: function($toggle) {
            var $child = $toggle.children('.dropdown-menu-wrapper__child:first');

            if (!$child.length) {
                return;
            }

            // reset styles to recalc it
            $child.css({
                'margin-top': 0
            });

            // align elements vertically
            $child.offset({top: $toggle.offset().top});

            // change dropdown direction if necessary
            var childHeight = $child.outerHeight();
            var childTop = $child.get(0).getBoundingClientRect().top;
            if (childHeight + childTop > window.innerHeight) {
                var marginTop = -1 * (childHeight - $toggle.outerHeight());
                $child.css({
                    'margin-top': marginTop + 'px'
                });
            }
        },

        /**
         * Defines current route name
         *
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
                item.parents('.accordion-group').addClass('active');
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
