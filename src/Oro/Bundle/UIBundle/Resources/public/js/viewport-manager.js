define([
    'module',
    'oroui/js/mediator',
    'jquery',
    'underscore',
    'oroui/js/error'
], function(module, mediator, $, _, error) {
    'use strict';

    var viewportManager;

    var screenMap = [
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
    ];

    viewportManager = {
        options: {
            screenMap: screenMap
        },

        screenByTypes: {},

        viewport: null,

        initialize: function() {
            this._prepareScreenMaps();

            var screenMap = this.options.screenMap;

            _.each(screenMap, function(screen, i) {
                var smallerScreen = screenMap[i - 1] || null;
                screen.min = smallerScreen ? smallerScreen.max + 1 : 0;
                this.screenByTypes[screen.name] = screen;
            }, this);

            this.viewport = {
                width: 0,
                type: null,
                isMobile: _.isMobile(),
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

        _prepareScreenMaps: function() {
            var moduleScreenMap = this._getModuleScreenMaps();

            if (this._isValidScreenMap(moduleScreenMap)) {
                this.options.screenMap = _.filter(
                    _.uniq(
                        _.flatten([moduleScreenMap, this.options.screenMap]),
                        false,
                        function(item) {
                            return item.name;
                        }
                    ),
                    function(item) {
                        return !item.skip;
                    }
                );
            }

            this.options.screenMap = _.sortBy(this.options.screenMap, 'max');
        },

        _getModuleScreenMaps: function() {
            var arr = [];

            if (!_.isUndefined(module.config().screenMap)) {
                arr = module.config().screenMap;
            }

            return arr;
        },

        _getScreenByTypes: function(screenType) {
            var defaultVal = null;

            if (_.isNull(screenType)) {
                return defaultVal;
            }

            if (_.has(this.screenByTypes, screenType)) {
                return this.screenByTypes[screenType];
            } else {
                error.showErrorInConsole('The screen type "' + screenType + '" not defined ');
                return defaultVal;
            }
        },

        _isValidScreenMap: function(array) {
            return _.isArray(array) && array.length;
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
            var viewport = this._getScreenByTypes(this.viewport.type);
            var minViewport = this._getScreenByTypes(minScreenType);
            return minScreenType === 'any' || (_.isObject(viewport) ? viewport.max >= minViewport.max : false);
        },

        _maxScreenTypeChecker: function(maxScreenType) {
            var viewport = this._getScreenByTypes(this.viewport.type);
            var maxViewport = this._getScreenByTypes(maxScreenType);
            return maxScreenType === 'any' || (_.isObject(viewport) ? viewport.max <= maxViewport.max : false);
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
