define(['backbone', 'oro/sidebar/constants'], function (Backbone, constants) {
    'use strict';

    /**
     * @export  oro/sidebar/widget-controller/model
     * @class oro.sidebar.widget-controller.Model
     * @extends Backbone.Model
     */
    var WidgetContainerModel = Backbone.Model.extend({
        defaults: {
            order: 0,
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
            switch (this.state) {
                case constants.WIDGET_MINIMIZED:
                    this.state = constants.WIDGET_MAXIMIZED;
                    break;

                case constants.WIDGET_MAXIMIZED:
                    this.state = constants.WIDGET_MINIMIZED;
                    break;
            }

            this.trigger('change');
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
