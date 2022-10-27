define(function(require) {
    'use strict';

    const $ = require('jquery');

    return {
        onDashboardRemove: function(dashboardId) {
            $('[data-menu="' + dashboardId + '"]').remove();
            if (!$('[data-menu]').length) {
                $('.menu-divider').remove();
            }
        }
    };
});
