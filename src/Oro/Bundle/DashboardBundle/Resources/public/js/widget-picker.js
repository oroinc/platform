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
                var widgetContainer = $this.parents('.dashboard-widget-container');
                var self = event.data.widgetPicker;
                var controls = event.data.controls;
                self.startLoading(controls, widgetContainer);
                $.post(
                    routing.generate('oro_api_post_dashboard_widget_add_widget'),
                    {widgetName: $this.data('widget-name'), dashboardId: self.dashboardId},
                    function(response){
                        mediator.trigger('dashboard:widget:add', response);
                        self.endLoading(controls, widgetContainer);
                    }, 'json'
                );
            }
        },

        /**
         * @param {jQuery} controls collection
         * @param {jQuery} widgetContainer current widget container
         */
        startLoading: function(controls, widgetContainer){
            controls.addClass('disabled');
            var widgetButtonWrapper = widgetContainer.find('.dashboard-widgets-pick-wrapper');
            widgetButtonWrapper.append($('<div class="loading-content"></div>'));
            widgetButtonWrapper.find('.add-widget-button').hide();
        },

        /**
         * @param {jQuery} controls collection
         * @param {jQuery} widgetContainer current widget container
         */
        endLoading: function(controls, widgetContainer){
            controls.removeClass('disabled');
            var widgetButtonWrapper = widgetContainer.find('.dashboard-widgets-pick-wrapper');
            widgetButtonWrapper.find('.loading-content').remove();
            widgetButtonWrapper.find('.add-widget-button').show();
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
