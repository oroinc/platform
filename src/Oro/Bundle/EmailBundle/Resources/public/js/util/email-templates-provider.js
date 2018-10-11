define(function(require) {
    'use strict';

    var $ = require('jquery');
    var routing = require('routing');

    return {
        create: function(templateId, relatedEntityId) {
            var url = routing.generate(
                'oro_api_get_emailtemplate_compiled',
                {id: templateId, entityId: relatedEntityId}
            );

            return $.ajax({
                url: url,
                dataType: 'json',
                errorHandlerMessage: ''
            }).then(function(data, textStatus, jqXHR) {
                return data;
            });
        }
    };
});
