define(function(require) {
    'use strict';

    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');

    /**
     * Init layout's handlers and listeners
     */
    mediator.setHandler('layout:init', layout.init, layout);
    mediator.setHandler('layout:dispose', layout.dispose, layout);
    mediator.setHandler('layout:getPreferredLayout', layout.getPreferredLayout, layout);
    mediator.setHandler('layout:getAvailableHeight', layout.getAvailableHeight, layout);
    mediator.setHandler('layout:enablePageScroll', layout.enablePageScroll, layout);
    mediator.setHandler('layout:disablePageScroll', layout.disablePageScroll, layout);
    mediator.setHandler('layout:hasHorizontalScroll', layout.disablePageScroll, layout);
    mediator.setHandler('layout:scrollbarWidth', layout.scrollbarWidth, layout);
    mediator.setHandler('layout:adjustLabelsWidth', layout.adjustLabelsWidth, layout);
    mediator.on('page:beforeChange', layout.pageRendering, layout);
    mediator.on('page:afterChange', layout.pageRendered, layout);
});
