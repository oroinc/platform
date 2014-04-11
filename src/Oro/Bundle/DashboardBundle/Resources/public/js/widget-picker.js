/*global define*/
define(['underscore', 'oroui/js/modal', 'oroui/js/mediator', 'orotranslation/js/translator', 'routing'],
    function (_, modal, mediator, __, routing) {
    'use strict';

    /**
     * @export  orodashboard/js/widget-picker
     * @class   orodashboard.WidgetPicker
     */
    return {
        dialog: null,
        dashboardId: null,
        init: function(dashboardId){
            var self = this;
            self.dashboardId = dashboardId;
            var widgetPickerDialog = modal.extend({
               open: function() {
                   Backbone.BootstrapModal.prototype.open.apply(this, arguments);
                   var controls = $('.add-widget-button');
                   $('.dashboard-widget-container').bind('click', {}, function(event){
                       event.stopImmediatePropagation();
                       if (!$(event.target).hasClass('add-widget-button')) {
                           $(this).find('.add-widget-button').click();
                       }
                   });
                   controls.bind('click', {widgetPicker: self, controls: controls}, self.clickAddToDashboardDelegate);
                }
            });

            this.dialog = new widgetPickerDialog({
                'content': $('#available-dashboard-widgets').html(),
                'className': 'modal dashboard-widgets-wrapper',
                'title': __('oro.dashboard.add_dashboard_widgets.title')
            });

            $('.dashboard-widgets-add').bind('click', { widgetPicker: this}, this.clickAddWidgetDelegate);
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
                var endLoading = event.data.widgetPicker.endLoading.bind(
                    event.data.widgetPicker, text, event.data.controls, $this.parents('.dashboard-widget-container')
                );
                var routingParams = {widgetName: $this.data('widget-name'), id: event.data.widgetPicker.dashboardId};
                var url = routing.generate('oro_dashboard_widget_add', routingParams);
                require(['text!'+url+'#rand='+Math.random()], function(html){
                    mediator.trigger('dashboard:widget:add', html);
                    endLoading();
                });
            }
        },

        /**
         * @param {jQuery} controls collection
         */
        startLoading: function(controls){
            controls.addClass('disabled');
            controls.html(__('oro.dashboard.add_dashboard_widgets.adding'));
        },

        /**
         * @param {string} text
         * @param {jQuery} controls collection
         * @param {jQuery} widgetContainer single element
         */
        endLoading: function(text, controls, widgetContainer){
            controls.removeClass('disabled');
            controls.html(text);
            var previous = widgetContainer.css('background-color');
            var animateFinish = function () {
                animateFinish = function(){
                    widgetContainer.css({backgroundColor: ''});
                };
                widgetContainer.animate({backgroundColor: previous}, animateFinish);
            };

            widgetContainer.animate({backgroundColor: "#F5F55B"}, 50, animateFinish);
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
