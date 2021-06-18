define(function(require, exports, module) {
    'use strict';

    const config = require('module-config').default(module.id);
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');
    const error = require('oroui/js/error');

    const viewportManager = {
        options: {
            /**
             * @default
             * Default values of breakpoints synchronized with scss breakpoints
             * @type {Array}
             *
             * [Default Breakpoints](https://github.com/oroinc/platform/blob/master/src/Oro/Bundle/UIBundle/Resources/public/blank/scss/settings/partials/_breakpoints.scss)
             */
            screenMap: []
        },

        /**
         * @property {Object}
         */
        screenByTypes: {},

        /**
         * @property {Object}
         */
        viewport: {
            width: 0,
            type: null
        },

        /**
         * @property {Object}
         */
        allowTypes: null,

        /**
         * CSS variable prefix
         * @property {String}
         */
        breakpointCSSVarPrefix: '--breakpoints-',

        /**
         * @inheritdoc
         * @param options
         */
        initialize: function(options) {
            this.screenByTypes = this._prepareScreenMaps(options.cssVariables || {});

            this.viewport = {
                isMobile: _.isMobile(),
                isApplicable: _.bind(this.isApplicable, this)
            };

            mediator.on('layout:reposition', _.debounce(this._onResize, 50), viewportManager);
        },

        /**
         * Get current viewport
         * @returns {null}
         */
        getViewport: function() {
            if (!this.viewport.type) {
                this._calcViewport();
            }
            return this.viewport;
        },

        /**
         * Get breakpoints object or specific property by name
         * @param name
         * @returns {*}
         */
        getScreenType: function(name) {
            if (_.isUndefined(name)) {
                return this.screenByTypes;
            }

            return this._getScreenByTypes(name);
        },

        /**
         * Check viewport ability
         * @param testViewport
         * @returns {boolean}
         */
        isApplicable: function(testViewport) {
            this.getViewport();
            let checker;
            let isApplicable = true;

            _.each(testViewport, function(testValue, check) {
                checker = this['_' + check + 'Checker'];
                if (checker && isApplicable) {
                    isApplicable = checker.call(this, testViewport[check]);
                }
            }, this);

            return isApplicable;
        },

        getAllowScreenTypes: function(screenType) {
            if (!_.isArray(screenType)) {
                screenType = [screenType];
            }

            return _.intersection(this.viewport.allowTypes, screenType);
        },

        /**
         * Collect and resolve CSS variables by breakpoint prefix
         * @param cssVariables
         * @returns {*}
         * @private
         * See [documentation](https://github.com/oroinc/platform/tree/master/src/Oro/Bundle/UIBundle/Resources/doc/reference/client-side/css-variables.md)
         */
        _collectCSSBreakpoints: function(cssVariables) {
            const regexpMax = /(max-width:\s?)([(\d+)]*)/g;
            const regexpMin = /(min-width:\s?)([(\d+)]*)/g;

            return _.reduce(cssVariables, function(collection, cssVar, varName) {
                if (new RegExp(this.breakpointCSSVarPrefix).test(varName)) {
                    let _result;

                    const matchMax = cssVar.match(regexpMax);
                    const matchMin = cssVar.match(regexpMin);

                    if (matchMax || matchMin) {
                        _result = {
                            name: varName.replace(this.breakpointCSSVarPrefix, '')
                        };

                        matchMax ? _result['max'] = parseInt(matchMax[0].replace('max-width:', '')) : null;
                        matchMin ? _result['min'] = parseInt(matchMin[0].replace('min-width:', '')) : null;

                        collection.push(_result);
                    }
                }

                return collection;
            }, [], this);
        },

        /**
         * Prepare breakpoint config object
         * @param cssVariables
         * @returns {*}
         * @private
         */
        _prepareScreenMaps: function(cssVariables) {
            const moduleScreenMap = this._getModuleScreenMaps();
            const cssVariablesScreenMap = this._collectCSSBreakpoints(cssVariables);

            let screenMap = _.filter(
                _.extend(
                    {},
                    _.indexBy(this.options.screenMap, 'name'),
                    this._isValidScreenMap(cssVariablesScreenMap)
                        ? _.indexBy(cssVariablesScreenMap, 'name')
                        : {},
                    this._isValidScreenMap(moduleScreenMap)
                        ? _.indexBy(moduleScreenMap, 'name')
                        : {}
                ), function(value) {
                    return !value.skip;
                }
            );

            screenMap = _.map(screenMap, function(screens) {
                if (!screens.max) {
                    screens.max = Infinity;
                }

                if (!screens.min) {
                    screens.min = 0;
                }

                return screens;
            });

            screenMap = this._sortScreenTypes(screenMap);

            this.options.screenMap = screenMap;

            screenMap = _.indexBy(screenMap, 'name');
            mediator.trigger('viewport:ready', screenMap);

            return screenMap;
        },

        _sortScreenTypes: function(screenMap) {
            return screenMap.sort(function(a, b) {
                const aMax = a.max || Infinity;
                const aMin = a.min || 0;
                const bMax = b.max || Infinity;
                const bMin = b.min || 0;
                return aMax - bMax || aMin - bMin;
            });
        },

        /**
         * Get from screen map from require config
         * @returns {Array}
         * @private
         */
        _getModuleScreenMaps: function() {
            let arr = [];

            if (!_.isUndefined(config.screenMap)) {
                arr = config.screenMap;
            }

            return arr;
        },

        /**
         * Get need screen type
         * @param screenType
         * @returns {*}
         * @private
         */
        _getScreenByTypes: function(screenType) {
            const defaultVal = null;

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

        /**
         * Check is not empty array
         * @param array
         * @returns {boolean}
         * @private
         */
        _isValidScreenMap: function(array) {
            return !!(_.isArray(array) && array.length);
        },

        /**
         * On change viewport size handler
         * @private
         */
        _onResize: function() {
            const oldViewportType = this.viewport.type;
            this._calcViewport();

            if (!oldViewportType || oldViewportType !== this.viewport.type) {
                mediator.trigger('viewport:change', this.viewport);
            }
        },

        /**
         * Calculate properties
         * @private
         */
        _calcViewport: function() {
            const viewportWidth = window.innerWidth;
            this.viewport.width = viewportWidth;
            const screenMap = this.options.screenMap;
            let inRange;
            let screen;
            const _result = [];

            for (let i = 0, stop = screenMap.length; i < stop; i++) {
                screen = screenMap[i];

                inRange = this._isInRange({
                    max: screen.max,
                    min: screen.min,
                    size: viewportWidth
                });

                if (inRange) {
                    _result.push(screen.name);
                }
            }

            this.viewport.allowTypes = _result;
            this.viewport.type = _.min(this.viewport.allowTypes, function(type) {
                return Math.abs(viewportWidth - this.screenByTypes[type].max);
            }, this);
        },

        /**
         *
         * @param o
         * @returns {*|boolean}
         * @private
         */
        _isInRange: function(o) {
            o.max = o.max || Infinity;
            o.min = o.min || 0;
            o.size = o.size || false;

            return o.size && o.min <= o.size && o.size <= o.max;
        },

        /**
         * Screen type criteria
         * @param screenType
         * @returns {boolean}
         * @private
         */
        _screenTypeChecker: function(screenType) {
            return screenType === 'any' || !!this.getAllowScreenTypes(screenType).length;
        },

        /**
         * Max screen type criteria
         * @param maxScreenType
         * @returns {boolean}
         * @private
         */
        _maxScreenTypeChecker: function(maxScreenType) {
            if (maxScreenType === 'any') {
                return true;
            }

            const maxViewport = this._getScreenByTypes(maxScreenType);

            return this.viewport.allowTypes.some(screenType => {
                const viewport = this._getScreenByTypes(screenType);

                return (_.isObject(viewport) && _.isObject(maxViewport))
                    ? viewport.max <= maxViewport.max
                    : false;
            });
        },

        /**
         * Min screen type criteria
         * @param minScreenType
         * @returns {boolean}
         * @private
         */
        _minScreenTypeChecker: function(minScreenType) {
            if (minScreenType === 'any') {
                return true;
            }

            const minViewport = this._getScreenByTypes(minScreenType);

            return this.viewport.allowTypes.some(screenType => {
                const viewport = this._getScreenByTypes(screenType);

                return (_.isObject(viewport) && _.isObject(minViewport))
                    ? viewport.max >= minViewport.max
                    : false;
            });
        },

        /**
         * Width criteria
         * @param width
         * @returns {boolean}
         * @private
         */
        _widthChecker: function(width) {
            return this.viewport.width === width;
        },

        /**
         * Min width criteria
         * @param minWidth
         * @returns {boolean}
         * @private
         */
        _minWidthChecker: function(minWidth) {
            return this.viewport.width >= minWidth;
        },

        /**
         * Max width criteria
         * @param maxWidth
         * @returns {boolean}
         * @private
         */
        _maxWidthChecker: function(maxWidth) {
            return this.viewport.width <= maxWidth;
        },

        /**
         * Is mobile criteria
         * @param isMobile
         * @returns {boolean}
         * @private
         */
        _isMobileChecker: function(isMobile) {
            return this.viewport.isMobile === isMobile;
        }
    };

    return viewportManager;
});
