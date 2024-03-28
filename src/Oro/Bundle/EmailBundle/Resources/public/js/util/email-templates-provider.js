define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const routing = require('routing');

    return {
        create: function($form) {
            const url = routing.generate('oro_email_ajax_email_compile');
            const formData = new FormData();
            _.each(['from', 'to', 'template', 'entityId', 'entityClass'], name => {
                formData.append('oro_email_email[' + name + ']', $form.find('[name$="[' + name + ']"]').val());
            });

            return $.post({
                url: url,
                data: null,
                contentType: false,
                beforeSend: function(xhr, options) {
                    options.data = formData;
                },
                errorHandlerMessage: ''
            }).then(function(data, textStatus, jqXHR) {
                return data;
            });
        }
    };
});
