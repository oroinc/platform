define(function(require) {
    'use strict';

    const FlowchartJsPlumbBaseView = require('./base-view');
    const FlowchartJsPlumbAreaView = require('./area-view');

    const FlowchartJsPlumbBoxView = FlowchartJsPlumbBaseView.extend({
        areaView: null,

        className: function() {
            return 'jsplumb-box';
        },

        isConnected: false,

        listen: {
            'change model': 'render',
            'change:position model': 'refreshPosition'
        },

        /**
         * @inheritdoc
         */
        constructor: function FlowchartJsPlumbBoxView(options) {
            FlowchartJsPlumbBoxView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (!(options.areaView instanceof FlowchartJsPlumbAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            FlowchartJsPlumbBoxView.__super__.initialize.call(this, options);

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
            this.$el.css({
                top: this.model.get('position')[1],
                left: this.model.get('position')[0]
            });
            this.areaView.debouncedRepaintEverything();
        },

        cleanup: function() {
            const instance = this.areaView.jsPlumbInstance;
            instance.detach(this.$el);
        }
    });

    return FlowchartJsPlumbBoxView;
});
