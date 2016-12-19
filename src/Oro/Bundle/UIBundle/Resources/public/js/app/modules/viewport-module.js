define(function(require) {
    'use strict';

    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var viewportManager = require('oroui/js/viewport-manager');

    mediator.on('layout:reposition',  _.debounce(viewportManager.onResize, 50), viewportManager);
});
