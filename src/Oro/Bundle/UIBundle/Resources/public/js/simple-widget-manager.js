define(function() {
    'use strict';

    var SimpleWidgetManager;

    SimpleWidgetManager = {
        widgets: [],

        /**
         * @param {Object} widget
         */
        addWidget: function(widget) {
            this.widgets.push(widget);
        },

        /**
         * @return {Array}
         */
        getWidgets: function() {
            return this.widgets;
        }
    };

    return SimpleWidgetManager;
});
