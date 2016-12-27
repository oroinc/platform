define([
    'module',
    'oroui/js/mediator',
    'jquery',
    'underscore'
], function(module, mediator, $, _) {
    'use strict';

    var viewportManager;

    var defaults = $.extend(true, {
        screenMap: [
            {
                name: 'desktop',
                min: 1100
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

        viewport: null,

        getViewport: function() {
            return this.viewport || this.calcViewport();
        },

        onResize: function() {
            var oldViewport = this.viewport;
            var viewport = this.calcViewport();

            if (!oldViewport || !_.isEqual(oldViewport.screenTypes, viewport.screenTypes)) {
                mediator.trigger('viewport:change', viewport);
            }
        },

        calcViewport: function() {
            var viewportWidth = window.innerWidth;
            var screenMap = this.options.screenMap;

            var screenTypes = {};
            var inRange;
            for (var i = 0, stop = screenMap.length; i < stop; i++) {
                inRange = this._isInRange({
                    max: screenMap[i].max,
                    min: screenMap[i].min,
                    size: viewportWidth
                });

                screenTypes[screenMap[i].name] = inRange;
            }

            screenTypes.any = true;

            this.viewport = {
                width: viewportWidth,
                screenTypes: screenTypes
            };

            return this.viewport;
        },

        _isInRange: function(o) {
            o.max = o.max || Infinity;
            o.min = o.min || 0;
            o.size = o.size || false;

            return o.size && o.min <= o.size && o.size <= o.max;
        }
    };

    return viewportManager;
});
