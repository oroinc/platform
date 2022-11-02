import mediator from 'oroui/js/mediator';
import messenger from 'oroui/js/messenger';

/**
 * Init messenger's handlers
 */
mediator.setHandler('addMessage', messenger.addMessage, messenger);
mediator.setHandler('showMessage', messenger.notificationMessage, messenger);
mediator.setHandler('showProcessingMessage', messenger.showProcessingMessage, messenger);
mediator.setHandler('showFlashMessage', messenger.notificationFlashMessage, messenger);
mediator.setHandler('showErrorMessage', messenger.showErrorMessage, messenger);
