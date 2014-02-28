/* global define */
define(['underscore', 'backbone', 'oro/workflow-management/step/view/list', 'oro/workflow-management/step/model',
    'oro/workflow-management/transition/collection', 'oro/workflow-management/step/view/edit'],
function(_, Backbone, StepsListView, StepModel, TransitionCollection, StepEditView) {
    'use strict';

    /**
     * @export  oro/workflow-management
     * @class   oro.WorkflowManagement
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        events: {
            'click .add-step-btn': 'addNewStep'
        },

        options: {
            stepsEl: null,
            metadataFormEl: null,
            model: null
        },

        initialize: function() {
            this.model.get('steps').add(this._getStartStep());
            this.stepListView = new StepsListView({
                el: this.$(this.options.stepsEl),
                collection: this.model.get('steps'),
                workflow: this.model
            });

            this.listenTo(this.model.get('steps'), 'requestEdit', this.openManageStepForm);
            this.listenTo(this.model.get('steps'), 'destroy', this.onStepRemove);
        },

        _getStartStep: function() {
            var startStepModel = new StepModel({
                'label': '(Starting point)',
                'order': -1,
                '_is_start': true
            });

            startStepModel
                .getAllowedTransitions(this.model)
                .reset(this.model.getStartTransitions());

            return startStepModel;
        },

        renderSteps: function() {
            this.stepListView.render();
        },

        addNewStep: function() {
            this.openManageStepForm(
                new StepModel()
            );
        },

        openManageStepForm: function(step) {
            var stepEditView = new StepEditView({
                model: step
            });
            stepEditView.on(
                'stepAdd',
                _.bind(this.addStep, this)
            );
            stepEditView.render();
        },

        addStep: function(step) {
            if (!this.model.get('steps').get(step.cid)) {
                this.model.get('steps').add(step);
            }
        },

        onStepRemove: function(step) {
            var stepTransitions = step.getAllowedTransitions(this.model);
            //Cloned because of iterator elements removing in loop
            _.each(_.clone(stepTransitions.models), function(transition) {
                transition.destroy();
            });
        },

        render: function() {
            this.renderSteps();
            return this;
        }
    });
});
