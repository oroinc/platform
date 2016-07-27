define(function(require) {
    'use strict';

    var BaseModel = require('oroui/js/app/models/base/model');
    var BaseController = require('oroui/js/app/controllers/base/controller');
    var mediator = require('oroui/js/mediator');
    var workflowStepsModel = new BaseModel();

    mediator.setHandler('workflowSteps:update', function(data) {
        workflowStepsModel.set(data);
    });

    mediator.on('page:update', function() {
        workflowStepsModel.set({
            stepsData: {}
        });
    });

    /**
     * Init layout's handlers and listeners
     */
    BaseController.loadBeforeAction([
        'oroworkflow/js/app/views/page-element/workflow-steps-view'
    ], function(WorkflowStepView) {
        BaseController.addToReuse('workflowSteps', WorkflowStepView, {
            model: workflowStepsModel,
            el: '.workflow-steps-placeholder',
            autoRender: true
        });
    });
});
