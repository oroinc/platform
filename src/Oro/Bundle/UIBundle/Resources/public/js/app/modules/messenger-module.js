define([
    'oroui/js/mediator',
    'oroui/js/app/controllers/base/controller'
], function(mediator, BaseController) {
    'use strict';

    /**
     * Init messenger's handlers
     */
    BaseController.loadBeforeAction([
        'oroui/js/messenger'
    ], function(messenger) {
        mediator.setHandler('addMessage', messenger.addMessage, messenger);
        mediator.setHandler('showMessage', messenger.notificationMessage, messenger);
        mediator.setHandler('showProcessingMessage', messenger.showProcessingMessage, messenger);
        mediator.setHandler('showFlashMessage', messenger.notificationFlashMessage, messenger);
        mediator.setHandler('showErrorMessage', messenger.showErrorMessage, messenger);
    });
});

