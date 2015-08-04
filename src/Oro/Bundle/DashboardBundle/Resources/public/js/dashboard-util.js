define(function(require) {
    'use strict';

    var $ = require('jquery');

    return {
        onDashboardRemove: function(dashboardId) {
            $('[data-menu="' + dashboardId + '"]').remove();
            if (!$('[data-menu]').length) {
                $('.menu-divider').remove();
            }
        }
    };
});
