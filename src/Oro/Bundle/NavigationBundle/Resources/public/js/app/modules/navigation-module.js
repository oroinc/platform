define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const pageStateChecker = require('oronavigation/js/app/services/page-state-checker');

    mediator.setHandler('isPageStateChanged', pageStateChecker.isStateChanged);
});
