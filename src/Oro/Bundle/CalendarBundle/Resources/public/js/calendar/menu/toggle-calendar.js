define(['oroui/js/app/views/base/view'
    ], function(BaseView) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/menu/toggle-calendar
     * @class   orocalendar.calendar.menu.ToggleCalendar
     * @extends oroui/js/app/views/base/view
     */
    return BaseView.extend({

        initialize: function(options) {
            this.connectionsView = options.connectionsView;
        },

        execute: function(model) {
            this.connectionsView.toggleCalendar(model);
        }
    });
});
