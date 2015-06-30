define(function (require) {
    'use strict';

    var FlowchartJsPlubmOverlayView,
        BaseView = require('oroui/js/app/views/base/view'),
        FlowchartJsPlubmAreaView = require('./area-view');

    FlowchartJsPlubmOverlayView = BaseView.extend({
        listen: {
            'change model': 'render'
        },

        className: function () {
            return 'jsplumb-overlay';
        },

        initialize: function (options) {
            if (!(options.areaView instanceof FlowchartJsPlubmAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            this.overlay = options.overlay;
            this.listenTo(this.areaView.flowchartState, 'change:transitionLabelsVisible', this.onLabelsToggle);
            FlowchartJsPlubmOverlayView.__super__.initialize.apply(this, arguments);
        },

        onLabelsToggle: function (flowchartState) {
            this.overlay.setVisible(flowchartState.get('transitionLabelsVisible'));
        }
    });

    return FlowchartJsPlubmOverlayView;
});
