define([
    'oroui/js/mediator',
    'oroui/js/app/controllers/base/controller'
], function(mediator, BaseController) {
    'use strict';

    BaseController.loadBeforeAction([
        'oroui/js/app/plugins/plugin-resizable-area'
    ], function(resizableArea) {
        mediator.on('resizable-area:set-previous-state', resizableArea.setPreviousState, resizableArea);
    });
});
