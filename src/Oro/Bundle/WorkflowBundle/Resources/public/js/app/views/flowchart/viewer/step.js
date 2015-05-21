define(function (require) {
    'use strict';
    var JsplubmBoxView = require('../jsplumb/box'),
        JsplumbWorkflowStepView;

    JsplumbWorkflowStepView = JsplubmBoxView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/viewer/step.html')
    });

    return JsplumbWorkflowStepView;
});
