define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const FlowchartJsPlumbAreaView = require('./area-view');

    const FlowchartJsPlumbOverlayView = BaseView.extend({
        listen: {
            'change model': 'render'
        },

        className: function() {
            return 'jsplumb-overlay';
        },

        /**
         * @inheritdoc
         */
        constructor: function FlowchartJsPlumbOverlayView(options) {
            FlowchartJsPlumbOverlayView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (!(options.areaView instanceof FlowchartJsPlumbAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            this.overlay = options.overlay;
            this.listenTo(this.areaView.flowchartState, 'change:transitionLabelsVisible', this.onLabelsToggle);
            FlowchartJsPlumbOverlayView.__super__.initialize.call(this, options);
        },

        onLabelsToggle: function(flowchartState) {
            this.overlay.setVisible(flowchartState.get('transitionLabelsVisible'));
        }
    });

    return FlowchartJsPlumbOverlayView;
});
