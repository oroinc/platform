define(function(require) {
    'use strict';

    var $ = require('jquery');
    var routing = require('routing');
    var messenger = require('oroui/js/messenger');
    var __ = require('orotranslation/js/translator');

    return {
        create: function(templateId, relatedEntityId) {
            var url = routing.generate(
                'oro_api_get_emailtemplate_compiled',
                {'id': templateId, 'entityId': relatedEntityId}
            );

            return $.ajax(url, {dataType: 'json'}).then(
                function(data, textStatus, jqXHR) {
                    return data;
                },
                function(jqXHR, textStatus, errorThrown) {
                    messenger.showErrorMessage(__('oro.email.emailtemplate.load_failed'));
                    return errorThrown;
                }
            );
        }
    };
});
