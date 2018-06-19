define(function(require) {
    'use strict';

    var mediator = require('oroui/js/mediator');
    var sync = require('orosync/js/sync');
    var messenger = require('oroui/js/messenger');
    var __ = require('orotranslation/js/translator');

    var topic = require('module').config().topic;

    var showNotification = function(message) {
        if (message.finished) {
            messenger.notificationMessage('warning', __('oro.attribute.attributes_import_has_finished'));
        }
    };

    var onPageChange = function() {
        sync.unsubscribe(topic, showNotification);
        mediator.off('page:request', onPageChange);
    };

    return function() {
        sync.subscribe(topic, showNotification);
        mediator.on('page:request', onPageChange);
    };
});
