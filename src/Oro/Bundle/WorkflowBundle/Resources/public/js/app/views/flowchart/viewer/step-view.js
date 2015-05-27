define(function (require) {
    'use strict';
    var FlowchartJsPlubmBoxView = require('../jsplumb/box-view'),
        FlowchartViewerStepView;

    FlowchartViewerStepView = FlowchartJsPlubmBoxView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/viewer/step.html')
    });

    return FlowchartViewerStepView;
});
