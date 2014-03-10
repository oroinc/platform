/*global define*/

define(['backbone', '../constants'], function (Backbone, constants) {
    'use strict';

    /**
     * @export  orosidebar/js/widget-container/model
     * @class   orosidebar.widgetContainer.Model
     * @extends Backbone.Model
     */
    var WidgetContainerModel = Backbone.Model.extend({
        defaults: {
            widgetName: '',
            position: 0,
            title: '',
            icon: '#',
            module: '',
            settings: {},
            state: constants.WIDGET_MINIMIZED
        },

        initialize: function () {
            this.stateSnapshot = this.get('state');
            this.isDragged = false;
        },

        /**
         * Toggles state of widget container between minimized and maximized
         */
        toggleState: function () {
            var model = this;
            var state = model.get('state');

            if (state === constants.WIDGET_MAXIMIZED_HOVER) {
                return;
            }

            if (state === constants.WIDGET_MINIMIZED) {
                model.set('state', constants.WIDGET_MAXIMIZED);
            } else {
                model.set('state', constants.WIDGET_MINIMIZED);
            }
        },

        /**
         * Saves state of widget container
         */
        snapshotState: function () {
            this.stateSnapshot = this.get('state');
        },

        /**
         * Restores state of widget container
         */
        restoreState: function () {
            this.set({ state: this.stateSnapshot });
        },

        /**
         * Update from original data
         */
        update: function (widgetData) {
            this.set(_.omit(widgetData, 'settings', 'placement'));
        }
    });

    return WidgetContainerModel;
});
