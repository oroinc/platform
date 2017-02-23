define([
    'module',
    'oroui/js/mediator',
    'jquery',
    'underscore'
], function(module, mediator, $, _) {
    'use strict';

    var viewportManager;
    var isMobile = _.isMobile();

    var defaults = $.extend(true, {
        screenMap: [
            {
                name: 'desktop',
                max: Infinity
            },
            {
                name: 'tablet',
                max: 1099
            },
            {
                name: 'tablet-small',
                max: 992
            },
            {
                name: 'mobile-landscape',
                max: 640
            },
            {
                name: 'mobile',
                max: 414
            }
        ]
    }, module.config());

    viewportManager = {
        options: {
            screenMap: defaults.screenMap
        },

        screenByTypes: {},

        viewport: null,

        initialize: function() {
            var screenMap = this.options.screenMap = _.sortBy(this.options.screenMap, 'max');

            _.each(screenMap, function(screen, i) {
                var smallerScreen = screenMap[i - 1] || null;
                screen.min = smallerScreen ? smallerScreen.max + 1 : 0;
                this.screenByTypes[screen.name] = screen;
            }, this);

            this.viewport = {
                width: 0,
                type: null,
                isMobile: isMobile,
                isApplicable: _.bind(this.isApplicable, this)
            };

            mediator.on('layout:reposition',  _.debounce(this._onResize, 50), viewportManager);
        },

        getViewport: function() {
            if (!this.viewport.type) {
                this._calcViewport();
            }
            return this.viewport;
        },

        isApplicable: function(testViewport) {
            this.getViewport();
            var checker;
            var isApplicable = true;

            _.each(testViewport, function(testValue, check) {
                checker = this['_' + check + 'Checker'];
                if (checker && isApplicable) {
                    isApplicable = checker.call(this, testViewport[check]);
                }
            }, this);

            return isApplicable;
        },

        _onResize: function() {
            var oldViewportType = this.viewport.type;
            this._calcViewport();

            if (!oldViewportType || oldViewportType !== this.viewport.type) {
                mediator.trigger('viewport:change', this.viewport);
            }
        },

        _calcViewport: function() {
            var viewportWidth = window.innerWidth;
            this.viewport.width = viewportWidth;
            var screenMap = this.options.screenMap;

            var inRange;
            var screen;
            for (var i = 0, stop = screenMap.length; i < stop; i++) {
                screen = screenMap[i];
                inRange = this._isInRange({
                    max: screen.max,
                    min: screen.min,
                    size: viewportWidth
                });

                if (inRange) {
                    this.viewport.type = screen.name;
                    break;
                }
            }
        },

        _isInRange: function(o) {
            o.max = o.max || Infinity;
            o.min = o.min || 0;
            o.size = o.size || false;

            return o.size && o.min <= o.size && o.size <= o.max;
        },

        _screenTypeChecker: function(screenType) {
            return screenType === 'any' || this.viewport.type === screenType;
        },

        _minScreenTypeChecker: function(minScreenType) {
            var viewport = this.screenByTypes[this.viewport.type];
            var minViewport = this.screenByTypes[minScreenType];
            return minScreenType === 'any' || viewport.max >= minViewport.max;
        },

        _maxScreenTypeChecker: function(maxScreenType) {
            var viewport = this.screenByTypes[this.viewport.type];
            var maxViewport = this.screenByTypes[maxScreenType];
            return maxScreenType === 'any' || viewport.max <= maxViewport.max;
        },

        _widthChecker: function(width) {
            return this.viewport.width === width;
        },

        _minWidthChecker: function(minWidth) {
            return this.viewport.width >= minWidth;
        },

        _maxWidthChecker: function(maxWidth) {
            return this.viewport.width <= maxWidth;
        },

        _isMobileChecker: function(isMobile) {
            return this.viewport.isMobile === isMobile;
        }
    };

    return viewportManager;
});
