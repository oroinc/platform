define(['backbone', 'oro/constants'], function (Backbone, Constants) {
    'use strict';

    var WidgetModel = Backbone.Model.extend({
        defaults: {
            title: '',
            icon: '#',
            module: '',
            settings: {}
        },

        initialize: function () {
            this.state = Constants.WIDGET_MINIMIZED;
            this.stateSnapshot = this.state;
            this.isDragged = false;
        },

        toggleState: function () {
            switch (this.state) {
                case Constants.WIDGET_MINIMIZED:
                    this.state = Constants.WIDGET_MAXIMIZED;
                    break;

                case Constants.WIDGET_MAXIMIZED:
                    this.state = Constants.WIDGET_MINIMIZED;
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

    return WidgetModel;
});