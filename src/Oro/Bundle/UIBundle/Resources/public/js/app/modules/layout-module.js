define([
    'oroui/js/mediator',
    'oroui/js/app/controllers/base/controller'
], function(mediator, BaseController) {
    'use strict';

    /**
     * Init layout's handlers and listeners
     */
    BaseController.loadBeforeAction([
        'oroui/js/layout'
    ], function(layout) {
        mediator.setHandler('layout:init', layout.init, layout);
        mediator.setHandler('layout:dispose', layout.unstyleForm, layout);
        mediator.setHandler('layout:getPreferredLayout', layout.getPreferredLayout, layout);
        mediator.setHandler('layout:getAvailableHeight', layout.getAvailableHeight, layout);
        mediator.setHandler('layout:enablePageScroll', layout.enablePageScroll, layout);
        mediator.setHandler('layout:disablePageScroll', layout.disablePageScroll, layout);
        mediator.setHandler('layout:hasHorizontalScroll', layout.disablePageScroll, layout);
        mediator.setHandler('layout:scrollbarWidth', layout.scrollbarWidth, layout);
        mediator.on('page:beforeChange', layout.pageRendering, layout);
        mediator.on('page:afterChange', layout.pageRendered, layout);
    });
});
