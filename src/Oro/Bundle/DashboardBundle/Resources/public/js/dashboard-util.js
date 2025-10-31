import $ from 'jquery';

export default {
    onDashboardRemove: function(dashboardId) {
        $('[data-menu="' + dashboardId + '"]').remove();
        if (!$('[data-menu]').length) {
            $('.menu-divider').remove();
        }
    }
};
