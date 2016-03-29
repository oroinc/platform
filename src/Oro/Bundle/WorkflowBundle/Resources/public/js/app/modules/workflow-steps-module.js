define(function(require) {
    'use strict';

    var $ = require('jquery');
    var Backbone = require('backbone');
    var BaseController = require('oroui/js/app/controllers/base/controller');
    var mediator = require('oroui/js/mediator');
    var workflowStepsModel = new Backbone.Model();
    var workflowStepsView;

    mediator.setHandler('workflowSteps:update', function(data) {
        workflowStepsModel.set(data);
    });

    mediator.on('page:update', function() {
        workflowStepsModel.set({
            steps: [],
            currentStep: false
        });
    });

    /**
     * Init layout's handlers and listeners
     */
    BaseController.loadBeforeAction([
        'oroworkflow/js/app/views/page-element/workflow-steps-view'
    ], function(WorkflowStepView) {
        workflowStepsView = new WorkflowStepView({
            model: workflowStepsModel,
            el: $('.workflow-steps-placeholder'),
            autoRender: true
        });
    });
});
