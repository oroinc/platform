/*global define*/
define(['underscore', 'oroui/js/modal', 'oroui/js/mediator', 'orotranslation/js/translator'],
    function (_, modal, mediator, __) {
    'use strict';

    /**
     * @export  orodashboard/js/widget-picker
     * @class   orodashboard.WidgetPicker
     */
    return {
        dialog: null,
        init: function(){
            var self = this;

            var widgetPickerDialog = modal.extend({
               open: function() {
                   Backbone.BootstrapModal.prototype.open.apply(this, arguments);
                   var controls = $('.add-widget-button');
                   var params = { widgetPicker: self, controls: controls };
                   controls.bind('click', params, self.clickAddToDashboardDelegate);
                }
            });

            this.dialog = new widgetPickerDialog({
                'content': $('#available-dashboard-widgets').html(),
                'className': 'modal dashboard-widgets-wrapper',
                'title': __('oro.dashboard.add_dashboard_widgets.title')
            });

            $('.dashboard-widgets-add').bind('click', { widgetPicker: this }, this.clickAddWidgetDelegate);
        },

        /**
         * @callback
         * @param {Event} event
         */
        clickAddToDashboardDelegate: function(event){
            var $this = $(this);
            if (!$this.hasClass('disabled')) {
                var text = $this.html();
                event.data.widgetPicker.startLoading(event.data.controls);
                console.log('write event trigger here');
                var endLoading = event.data.widgetPicker.endLoading.bind(
                    event.data.widgetPicker, text, event.data.controls, $this
                );
                setTimeout(endLoading, 1000);
            }
        },

        startLoading: function(controls){
            controls.addClass('disabled');
            controls.html(__('oro.dashboard.add_dashboard_widgets.adding'));
        },

        endLoading: function(text, controls, addedWidget){
            controls.removeClass('disabled');
            controls.html(text);
            var widgetContainer = addedWidget.parents('.dashboard-widget-container');
            var previous = widgetContainer.css('background-color');
            widgetContainer.animate(
                {backgroundColor: "#F5F55B"},
                50,
                'swing',
                function () {
                    widgetContainer.animate({backgroundColor: previous}, 500);
                }
            );
        },

        /**
         * @callback
         * @param {Event} event
         */
        clickAddWidgetDelegate: function(event){
            event.data.widgetPicker.dialog.open();
        }
    };
});
