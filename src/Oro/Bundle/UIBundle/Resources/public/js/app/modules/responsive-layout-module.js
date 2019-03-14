define(function(require) {
    'use strict';

    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var responsiveLayout = require('oroui/js/responsive-layout');

    mediator.setHandler('responsive-layout:update', _.debounce(responsiveLayout.update));
});
