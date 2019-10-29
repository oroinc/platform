define(function(require, exports, module) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const sync = require('orosync/js/sync');
    const messenger = require('oroui/js/messenger');
    const __ = require('orotranslation/js/translator');

    const topic = require('module-config').default(module.id).topic;

    const showNotification = function(message) {
        if (message.finished) {
            messenger.notificationMessage('warning', __('oro.attribute.attributes_import_has_finished'));
        }
    };

    const onPageChange = function() {
        sync.unsubscribe(topic, showNotification);
        mediator.off('page:request', onPageChange);
    };

    return function() {
        sync.subscribe(topic, showNotification);
        mediator.on('page:request', onPageChange);
    };
});
