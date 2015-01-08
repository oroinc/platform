/*global define*/
define(function (require) {
    'use strict';

    var mediator = require('oroui/js/mediator')
    return {
        onDashboardRemove: function (dashboardId) {
            $('[data-menu="' + dashboardId + '"]').remove();
            if (!$('[data-menu]').length) {
                $('.menu-divider').remove();
            }
        }
    };
});
