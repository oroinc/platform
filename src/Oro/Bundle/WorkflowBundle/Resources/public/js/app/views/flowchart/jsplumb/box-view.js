define(function(require) {
    'use strict';

    var FlowchartJsPlumbBoxView;
    var FlowchartJsPlumbBaseView = require('./base-view');
    var FlowchartJsPlumbAreaView = require('./area-view');
    var _ = require('underscore');

    FlowchartJsPlumbBoxView = FlowchartJsPlumbBaseView.extend({
        areaView: null,

        className: function() {
            return 'jsplumb-box';
        },

        isConnected: false,

        listen: {
            'change model': 'render',
            'change:position model': 'refreshPosition'
        },

        initialize: function(options) {
            if (!(options.areaView instanceof FlowchartJsPlumbAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            FlowchartJsPlumbBoxView.__super__.initialize.apply(this, arguments);

            // append $el to the area view
            this.areaView.$el.append(this.$el);
        },

        connect: function() {
            // set position once, right after render
            // all other changes should be done by jsPlumb
            // or jsPlumb.redraw must be called
            if (this.model.get('position')) {
                this.refreshPosition();
            } else {
                this.model.set('position', this.areaView.jsPlumbManager.getPositionForNew());
            }
        },

        refreshPosition: function() {
            var instance = this.areaView.jsPlumbInstance;
            instance.batch(_.bind(function() {
                this.$el.css({
                    top: this.model.get('position')[1],
                    left: this.model.get('position')[0]
                });
            }, this));
            this.areaView.jsPlumbInstance.repaintEverything();
        },

        cleanup: function() {
            var instance = this.areaView.jsPlumbInstance;
            instance.detach(this.$el);
        }
    });

    return FlowchartJsPlumbBoxView;
});
