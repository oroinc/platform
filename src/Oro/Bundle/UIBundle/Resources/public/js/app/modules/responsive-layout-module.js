define(function(require) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const responsiveLayout = require('oroui/js/responsive-layout');

    mediator.setHandler('responsive-layout:update', _.debounce(responsiveLayout.update));
});
