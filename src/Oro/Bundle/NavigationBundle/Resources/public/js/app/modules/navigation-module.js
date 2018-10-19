define(function(require) {
    'use strict';

    var mediator = require('oroui/js/mediator');

    mediator.setHandler('isPageStateChanged', function() {}); // default handler returns undefined
});
