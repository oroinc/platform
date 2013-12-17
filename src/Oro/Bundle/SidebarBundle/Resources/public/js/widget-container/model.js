/*global define*/

define(['backbone', 'oro/sidebar/constants'], function (Backbone, constants) {
    'use strict';

    /**
     * @export  oro/sidebar/widget-controller/model
     * @class oro.sidebar.widget-controller.Model
     * @extends Backbone.Model
     */
    var WidgetContainerModel = Backbone.Model.extend({
        defaults: {
            position: 0,
            title: '',
            icon: '#',
            module: '',
            settings: {}
        },

        initialize: function () {
            this.state = constants.WIDGET_MINIMIZED;
            this.stateSnapshot = this.state;
            this.isDragged = false;
        },

        /**
         * Toggles state of widget container between minimized and maximized
         */
        toggleState: function () {
            var model = this;

            if (model.state === constants.WIDGET_MAXIMIZED_HOVER) {
                return;
            }

            if (model.state === constants.WIDGET_MINIMIZED) {
                model.state = constants.WIDGET_MAXIMIZED;
            } else {
                model.state = constants.WIDGET_MINIMIZED;
            }

            model.trigger('change');
        },

        /**
         * Saves state of widget container
         */
        snapshotState: function () {
            this.stateSnapshot = this.state;
        },

        /**
         * Restores state of widget container
         */
        restoreState: function () {
            this.state = this.stateSnapshot;
        }
    });

    return WidgetContainerModel;
});
