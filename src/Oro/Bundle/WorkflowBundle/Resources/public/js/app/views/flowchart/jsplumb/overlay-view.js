define(function (require) {
    'use strict';

    var FlowchartJsPlumbOverlayView,
        BaseView = require('oroui/js/app/views/base/view'),
        FlowchartJsPlumbAreaView = require('./area-view');

    FlowchartJsPlumbOverlayView = BaseView.extend({
        listen: {
            'change model': 'render'
        },

        className: function () {
            return 'jsplumb-overlay';
        },

        initialize: function (options) {
            if (!(options.areaView instanceof FlowchartJsPlumbAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            this.overlay = options.overlay;
            this.listenTo(this.areaView.flowchartState, 'change:transitionLabelsVisible', this.onLabelsToggle);
            FlowchartJsPlumbOverlayView.__super__.initialize.apply(this, arguments);
        },

        onLabelsToggle: function (flowchartState) {
            this.overlay.setVisible(flowchartState.get('transitionLabelsVisible'));
        }
    });

    return FlowchartJsPlumbOverlayView;
});
