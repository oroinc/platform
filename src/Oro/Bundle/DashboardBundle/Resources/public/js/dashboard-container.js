/*global define*/
define(['underscore', 'backbone', 'oroui/js/mediator', 'oroui/js/widget-manager', 'orodashboard/js/widget/dashboard-item'
    ], function (_, Backbone, mediator, widgetManager, DashboardItemWidget) {
    'use strict';

    var $ = Backbone.$;

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
            widgetIds: []
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
        },

        /**
         * Add dashboard widget
         *
         * @param {DashboardItemWidget} widget
         */
        add: function(widget) {
            this.widgets[widget.getWid()] = widget;
            console.log(this);
        }
    };

    return dashboardContainer;
});
