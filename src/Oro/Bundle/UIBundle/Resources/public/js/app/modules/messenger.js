/*global require*/
require([
    'oroui/js/mediator',
    'oroui/js/messenger'
], function (mediator, messenger) {
    'use strict';

    /**
     * Init messenger's handlers
     */
    mediator.setHandler('addMessage', messenger.addMessage, messenger);
    mediator.setHandler('showMessage', messenger.notificationMessage, messenger);
    mediator.setHandler('showFlashMessage', messenger.notificationFlashMessage, messenger);
    mediator.setHandler('showErrorMessage', messenger.showErrorMessage, messenger);
});

