define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const routing = require('routing');
    const systemAccessModeOrganizationProvider =
        require('oroorganization/js/app/tools/system-access-mode-organization-provider');

    return {
        create: function($form) {
            const url = routing.generate('oro_email_ajax_email_compile');
            const formData = new FormData();
            const organizationId = systemAccessModeOrganizationProvider.getOrganizationId();
            formData.append('_sa_org_id', organizationId);
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
