define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var WidgetPickerModal = require('orodashboard/js/widget-picker-modal');

    return {
        /**
         * @property {integer}
         */
        targetColumn: 0,

        /**
         * @property {Backbone.BootstrapModal}
         */
        dialog: null,

        /**
         * @property {integer}
         */
        dashboardId: null,

        /**
         * @param {integer} dashboardId
         */
        init: function(dashboardId) {
            this.targetColumn = 0;
            this.dashboardId = dashboardId;
            $('.dashboard-widgets-add').bind('click', _.bind(this._onClickAddWidget, this));
        },

        loadWidget: function(widgetName) {
            return $.post(
                routing.generate('oro_api_post_dashboard_widget_add_widget'),
                {
                    widgetName: widgetName,
                    dashboardId: this.dashboardId,
                    targetColumn: this.targetColumn
                },
                function(response) {
                    mediator.trigger('dashboard:widget:add', response);
                },
                'json'
            );
        },

        /**
         * @param {Event} event
         * @private
         */
        _onClickAddWidget: function(event) {
            event.preventDefault();
            var columnIndex = $(event.target).closest('.dashboard-column').index();
            this.targetColumn = (columnIndex === -1) ? 0 : columnIndex;

            var dialog = new WidgetPickerModal({
                content: $('#available-dashboard-widgets').html(),
                className: 'modal oro-modal-normal dashboard-widgets-wrapper',
                title: __('oro.dashboard.add_dashboard_widgets.title'),
                loadWidget: _.bind(this.loadWidget, this),
                cancelText: __('Close')
            });
            dialog.open();
        }
    };
});
