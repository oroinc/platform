define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const layout = require('oroui/js/layout');

    const layoutHelper = {

        elementContext: $('#container'),

        /**
         * @param {string} elementSelector
         * @param {jQuery} elementContext
         */
        setAvailableHeight: function(elementSelector, elementContext) {
            const $element = $(elementSelector, elementContext || this.elementContext);

            const calculateHeight = function() {
                const height = $(window).height() - $element.offset().top;
                $element.css({
                    'height': height,
                    'min-height': height
                });
            };

            layout.onPageRendered(calculateHeight);
            $(window).on('resize', _.debounce(calculateHeight, 50));
            mediator.on('page:afterChange', calculateHeight);
            mediator.on('layout:adjustHeight', calculateHeight);

            calculateHeight();
        }
    };

    return layoutHelper;
});
