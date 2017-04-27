define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');

    var layoutHelper = {

        elementContext: $('#container'),

        /**
         * @param {string} elementSelector
         * @param {jQuery} elementContext
         */
        setAvailableHeight: function(elementSelector, elementContext) {
            var $element = $(elementSelector, elementContext || this.elementContext);

            var calculateHeight = function() {
                var height = $(window).height() - $element.offset().top;
                $element.css({
                    'height': height,
                    'min-height': height
                });
            };

            layout.onPageRendered(calculateHeight);
            $(window).on('resize', _.debounce(calculateHeight, 50));
            mediator.on('page:afterChange', calculateHeight);
            mediator.on('layout:adjustReloaded', calculateHeight);
            mediator.on('layout:adjustHeight', calculateHeight);

            calculateHeight();
        }
    };

    return layoutHelper;
});
