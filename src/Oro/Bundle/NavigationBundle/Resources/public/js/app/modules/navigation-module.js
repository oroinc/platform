define(function(require) {
    'use strict';

    var mediator = require('oroui/js/mediator');
    var pageStateChecker = require('oronavigation/js/app/services/page-state-checker');

    mediator.setHandler('isPageStateChanged', pageStateChecker.isStateChanged);
});
