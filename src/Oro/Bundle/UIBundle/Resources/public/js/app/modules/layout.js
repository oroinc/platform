/*global require*/
require([
    'oroui/js/mediator',
    'oroui/js/app/controllers/base/controller'
], function (mediator, BaseController) {
    'use strict';

    /**
     * Init layout's handlers and listeners
     */
    BaseController.loadBeforeAction([
        'oroui/js/layout'
    ], function (layout) {
        mediator.setHandler('layout:init', layout.init, layout);
        mediator.setHandler('layout:dispose', layout.unstyleForm, layout);
        mediator.on('page:beforeChange', layout.pageRendering, layout);
        mediator.on('page:afterChange', layout.pageRendered, layout);
    });
});

