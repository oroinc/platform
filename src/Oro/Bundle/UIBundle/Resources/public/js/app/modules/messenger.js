/*global require*/
require([
    'oroui/js/mediator',
    'oroui/js/messenger'
], function (mediator, messenger) {
    'use strict';

    /**
     * Init messenger's handlers
     */
    mediator.setHandler('showMessage', messenger.notificationMessage, messenger);
    mediator.setHandler('showFlashMessage', messenger.notificationFlashMessage, messenger);
});

