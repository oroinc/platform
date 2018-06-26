define([
    'oroui/js/mediator',
    'oroui/js/app/plugins/plugin-resizable-area'
], function(mediator, resizableArea) {
    'use strict';

    mediator.on('layoutInit', resizableArea.setPreviousState, resizableArea);
});
