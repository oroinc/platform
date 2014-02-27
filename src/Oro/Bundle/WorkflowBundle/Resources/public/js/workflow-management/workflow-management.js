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

            this.listenTo(this.stepListView, 'stepEdit', this.editStep);
        },

        _getStartStep: function() {
            return new StepModel({
                'label': '(Starting point)',
                'order': -1,
                '_is_start': true,
                'allowed_transitions': new TransitionCollection(this.model.getStartTransitions())
            });
        },

        renderSteps: function() {
            this.stepListView.render();
        },

        addNewStep: function() {
            this.openManageStepForm(
                new StepModel()
            );
        },

        editStep: function(stepName) {
            this.openManageStepForm(
                this.model.getStepByName(stepName)
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
            if (!this.model.getStepByName(step.get('name'))) {
                this.model.get('steps').add(step);
            }
            this.renderSteps();
        },

        _getFormElement: function(form, name) {
            var elId = this.$el.attr('id') + '_' + name;
            return this.$('#' + elId);
        },

        render: function() {
            this.renderSteps();
        }
    });
});
