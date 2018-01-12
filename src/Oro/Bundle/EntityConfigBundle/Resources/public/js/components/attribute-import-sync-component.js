define(function(require) {
    'use strict';

    var mediator = require('oroui/js/mediator');
    var sync = require('orosync/js/sync');
    var messenger = require('oroui/js/messenger');
    var __ = require('orotranslation/js/translator');

    var config = require('module').config();

    var unsubscribe = function () {
        mediator.off('page:request', unsubscribe);
        sync.unsubscribe(config.topic);
    };

    mediator.on('page:request', unsubscribe);

    sync.subscribe(config.topic, function (response) {
        var message = JSON.parse(response);
        if (message.finished) {
            messenger.notificationMessage('warning', __('oro.attribute.attributes_import_has_finished'));
        }
    });
});
