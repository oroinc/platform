define(['backbone', 'oro/sidebar/constants'], function (Backbone, constants) {
    'use strict';

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

        snapshotState: function () {
            this.stateSnapshot = this.state;
        },

        restoreState: function () {
            this.state = this.stateSnapshot;
        }
    });

    return WidgetContainerModel;
});