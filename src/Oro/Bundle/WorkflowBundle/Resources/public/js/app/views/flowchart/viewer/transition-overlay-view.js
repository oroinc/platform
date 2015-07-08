define(function(require) {
    'use strict';

    var FlowchartJsPlumbOverlayView = require('../jsplumb/overlay-view');
    var FlowchartViewerTransitionOverlayView;

    FlowchartViewerTransitionOverlayView = FlowchartJsPlumbOverlayView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/viewer/transition.html'),
        attributes: {
            'data-role': 'transition-overlay'
        }
    });

    return FlowchartViewerTransitionOverlayView;
});
