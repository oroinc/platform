/*global define*/
define(['underscore', 'oroui/js/modal', 'oroui/js/mediator', 'orotranslation/js/translator'],
    function (_, Modal, mediator, __) {
    'use strict';

    /**
     * @export  orodashboard/js/widget-picker
     * @class   orodashboard.WidgetPicker
     */
    return {
        dialog: null,
        init: function(){
            this.dialog = new Modal({
                'content': $('#available-dashboard-widgets').html(),
                'className': 'modal dashboard-widgets-wrapper',
                'title': __('oro.dashboard.add_dashboard_widgets.title')
            });
            $('.dashboard-widgets-add').bind('click', { widgetPicker: this }, this.clickAddWidgetDelegate);
        },

        clickAddWidgetDelegate: function(event){
            event.data.widgetPicker.dialog.open();
        }
    };
});
