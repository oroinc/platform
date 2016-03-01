define([
    'jquery',
    'underscore',
    'oroui/js/mediator'
], function($, _, mediator) {
    'use strict';

    var widgetManager;

    /**
     * @export oroui/js/widget-manager
     * @name   oro.widgetManager
     */
    widgetManager = {
        widgets: {},
        aliases: {},

        /**
         * Reset manager to initial state.
         */
        resetWidgets: function() {
            _.each(this.widgets, function(widget) {
                // if widget is not actual any more -- remove it
                if (!widget.isActual()) {
                    widget.remove();
                }
            });
        },

        /**
         * Add widget instance to registry.
         *
         * @param {oroui.widget.AbstractWidget} widget
         */
        addWidgetInstance: function(widget) {
            this.widgets[widget.getWid()] = widget;
            mediator.trigger('widget_registration:wid:' + widget.getWid(), widget);
            if (widget.getAlias()) {
                this.aliases[widget.getAlias()] = widget.getWid();
                mediator.trigger('widget_registration:' + widget.getAlias(), widget);
            }
        },

        /**
         * Get widget instance by widget identifier and pass it to callback when became available.
         *
         * @param {string} wid unique widget identifier
         * @param {Function} callback widget instance handler
         */
        getWidgetInstance: function(wid, callback) {
            if (this.widgets.hasOwnProperty(wid)) {
                callback(this.widgets[wid]);
            } else {
                mediator.once('widget_registration:wid:' + wid, callback);
            }
        },

        /**
         * Get widget instance by alias and pass it to callback when became available.
         *
         * @param {string} alias widget alias
         * @param {Function} callback widget instance handler
         */
        getWidgetInstanceByAlias: function(alias, callback) {
            if (this.aliases.hasOwnProperty(alias)) {
                this.getWidgetInstance(this.aliases[alias], callback);
            } else {
                mediator.once('widget_registration:' + alias, callback);
            }
        },

        /**
         * Remove widget instance from registry.
         *
         * @param {string} wid unique widget identifier
         */
        removeWidget: function(wid) {
            var widget = this.widgets[wid];
            if (widget) {
                delete this.aliases[widget.getAlias()];
            }
            delete this.widgets[wid];
        }
    };

    return widgetManager;
});
