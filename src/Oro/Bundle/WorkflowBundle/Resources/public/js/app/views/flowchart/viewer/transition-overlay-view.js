define(function (require) {
    'use strict';
    var FlowchartJsPlumbOverlayView = require('../jsplumb/overlay-view'),
        FlowchartViewerTransitionOverlayView;

    FlowchartViewerTransitionOverlayView = FlowchartJsPlumbOverlayView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/viewer/transition.html'),
        ensureAttributes: function () {
            // css class is updated by jsPlumb, use attribute instead
            this.$el.attr('data-role', 'transition-overlay');
        }
    });

    return FlowchartViewerTransitionOverlayView;
});
