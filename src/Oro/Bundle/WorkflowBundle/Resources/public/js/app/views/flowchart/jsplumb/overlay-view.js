define(function(require) {
    'use strict';

    var FlowchartJsPlumbOverlayView;
    var BaseView = require('oroui/js/app/views/base/view');
    var FlowchartJsPlumbAreaView = require('./area-view');

    FlowchartJsPlumbOverlayView = BaseView.extend({
        listen: {
            'change model': 'render'
        },

        className: function() {
            return 'jsplumb-overlay';
        },

        /**
         * @inheritDoc
         */
        constructor: function FlowchartJsPlumbOverlayView() {
            FlowchartJsPlumbOverlayView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (!(options.areaView instanceof FlowchartJsPlumbAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            this.overlay = options.overlay;
            this.listenTo(this.areaView.flowchartState, 'change:transitionLabelsVisible', this.onLabelsToggle);
            FlowchartJsPlumbOverlayView.__super__.initialize.apply(this, arguments);
        },

        onLabelsToggle: function(flowchartState) {
            this.overlay.setVisible(flowchartState.get('transitionLabelsVisible'));
        }
    });

    return FlowchartJsPlumbOverlayView;
});
