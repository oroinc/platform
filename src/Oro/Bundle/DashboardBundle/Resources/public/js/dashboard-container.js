/*global define*/
define(['jquery', 'underscore', 'backbone', 'routing', 'orotranslation/js/translator', 'oroui/js/mediator',
    'oroui/js/widget-manager', 'orodashboard/js/widget/dashboard-item', 'jquery-ui'
    ], function ($, _, Backbone, routing, __, mediator, widgetManager, DashboardItemWidget) {
    'use strict';

    /**
     * @export orodashboard/js/dashboard-container
     * @class  orodashboard.DashboardContainer
     */
    var dashboardContainer = {
        /**
         * @property {Object}
         */
        widgets: {},

        /**
         * @property {Object}
         */
        options: {
            widgetIds: [],
            handle: ".dashboard-widget > .title",
            columnsSelector: '.dashboard-column',
            placeholder: {
                element: function(currentItem) {
                    var height = $(currentItem).height();
                    return $(
                        '<div><div class="widget-placeholder" style="height: ' + height + 'px;">' +
                            __('oro.dashboard.drop_placeholder_label') +
                        '</div></div>'
                    )[0];
                },
                update: function(container, p) {
                    return;
                }
            }
        },

        /**
         * Initialize dashboard
         *
         * @param {Object} options
         */
        initialize: function(options) {
            var self = this;
            this.options = _.extend({}, this.options, options);
            this.options.urls = {
                savePositions: routing.generate('oro_api_positions_dashboard_widget', {dashboardId: this.options.dashboardId})
            };

            mediator.on('dashboard:widget:add', function(elementHtml){
                self.addToDashboard(elementHtml);
            });

            _.each(this.options.widgetIds, function (wid) {
                widgetManager.getWidgetInstance(
                    wid,
                    function (widget) {
                        self.add(widget);
                    }
                );
            });

            $(this.options.columnsSelector)
                .sortable({
                    handle: this.options.handle,
                    placeholder: this.options.placeholder,
                    connectWith: this.options.columnsSelector,
                    stop: function(event, ui) {
                        self.saveLayoutPosition();
                    }
                });
        },

        addToDashboard: function(html){
            $('#dashboard-column-1').prepend(html);
            $(this.options.columnsSelector).sortable('refresh');
        },

        /**
         * Save layout position
         */
        saveLayoutPosition: function() {
            var self = this;
            var data = {
                layoutPositions: {}
            };
            $(this.options.columnsSelector).each(function(index, columnElement) {
                var columnIndex = $(columnElement).data('column');
                $('> div', columnElement).each(function (widgetIndex, widgetContainer) {
                    var wid = $('.widget-content', widgetContainer).data('wid');
                    if (self.widgets[wid]) {
                        var id = self.widgets[wid].state.id;
                        data.layoutPositions[id] = [columnIndex, widgetIndex];
                    }
                });
            });

            $.ajax({
                url: this.options.urls.savePositions,
                type: 'PUT',
                data: $.param(data)
            });
        },

        /**
         * Add dashboard widget
         *
         * @param {DashboardItemWidget} widget
         */
        add: function(widget) {
            this.widgets[widget.getWid()] = widget;
        }
    };

    return dashboardContainer;
});
