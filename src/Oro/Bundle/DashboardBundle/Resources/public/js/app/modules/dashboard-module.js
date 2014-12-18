/*global require*/
require([
    'oroui/js/mediator'
], function (mediator) {
    'use strict';

    // reload page if dashboard is modified or deleted
    mediator.on('api:delete', function (url) {
        var parts;
        if (parts = /^(\/app_dev\.php|)\/api\/rest\/latest\/dashboards\/([0-9]+)$/i.exec(url)) {
            $('[data-menu="' + parts[2] + '"]').remove();
            if (!$('[data-menu]').length) {
                $('.menu-divider').remove();
            }
        }
    });
});
