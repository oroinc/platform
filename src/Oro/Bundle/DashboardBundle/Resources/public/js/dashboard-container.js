/*global define*/
define(['jquery', 'underscore', 'backbone', 'oroui/js/mediator', 'oroui/js/widget-manager',
    'orodashboard/js/widget/dashboard-item', 'jquery-ui'
    ], function ($, _, Backbone, mediator, widgetManager, DashboardItemWidget) {
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
                            'Drag your widget here.' +
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
            this.options = _.extend(this.options, options);

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
                    connectWith: this.options.columnsSelector
                });
        },

        /**
         * Add dashboard widget
         *
         * @param {DashboardItemWidget} widget
         */
        add: function(widget) {
            this.widgets[widget.getWid()] = widget;
            this.getWidgetDraggableElement(widget).draggable();
        },

        /**
         *
         * @param {DashboardItemWidget} widget
         * @param {function} callback
         * @returns {HTMLElement}
         */
        getWidgetDraggableElement: function(widget, callback) {
            return widget.widget.parent();
        }
    };

    return dashboardContainer;
});
