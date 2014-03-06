/* global define */
define([
    'underscore', 'backbone',
    'oroworkflow/js/workflow-management/step/view/list',
    'oroworkflow/js/workflow-management/step/model',
    'oroworkflow/js/workflow-management/transition/model',
    'oroworkflow/js/workflow-management/step/view/edit',
    'oroworkflow/js/workflow-management/transition/view/edit',
    'oroentity/js/fields-loader'
],
function(_, Backbone,
     StepsListView,
     StepModel,
     TransitionModel,
     StepEditView,
     TransitionEditForm
) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroworkflow/js/workflow-management
     * @class   oro.WorkflowManagement
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        events: {
            'click .add-step-btn': 'addNewStep',
            'click .add-transition-btn': 'addNewTransition'
        },

        options: {
            stepsEl: null,
            saveBtnEl: null,
            model: null,
            entities: []
        },

        initialize: function() {
            this.model.get('steps').add(this._getStartStep());
            this.stepListView = new StepsListView({
                el: this.$(this.options.stepsEl),
                collection: this.model.get('steps'),
                workflow: this.model
            });
            this.$entitySelectEl = this.$('[name$="[related_entity]"]');
            this.initEntityFieldsLoader();

            this.listenTo(this.model.get('steps'), 'requestAddTransition', this.addNewStepTransition);
            this.listenTo(this.model.get('steps'), 'requestEdit', this.openManageStepForm);
            this.listenTo(this.model.get('steps'), 'destroy', this.onStepRemove);

            this.listenTo(this.model.get('transitions'), 'requestEdit', this.openManageTransitionForm);

            this.$saveBtn = $(this.options.saveBtnEl);
            this.$saveBtn.on('click', _.bind(this.saveConfiguration, this));
            this.model.url = this.$saveBtn.data('url');
        },

        initEntityFieldsLoader: function() {
            this.$entitySelectEl.fieldsLoader({
                router: 'oro_api_get_entity_fields',
                routingParams: {"with-relations": 1,"with-entity-details": 1, "deep-level": 2}
            });
        },

        saveConfiguration: function(e) {
            e.preventDefault();
            this.model.save();
        },

        _getStartStep: function() {
            var startStepModel = new StepModel({
                'name': 'step:starting_point',
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
            this.openManageStepForm(new StepModel());
        },

        addNewTransition: function() {
            this.addNewStepTransition(null);
        },

        addNewStepTransition: function(step) {
            this.openManageTransitionForm(new TransitionModel(), step);
        },

        openManageTransitionForm: function(transition, step_from) {
            var transitionEditView = new TransitionEditForm({
                'model': transition,
                'workflow': this.model,
                'step_from': step_from,
                'entity_select_el': this.$entitySelectEl,
                'entities': this.options.entities
            });
            transitionEditView.on('transitionAdd', this.addTransition, this);
            transitionEditView.render();
        },

        addTransition: function(transition, stepFrom) {
            console.log(transition);

            if (!this.model.get('transitions').get(transition.cid)) {
                var stepFrom = this.model.getStepByName(stepFrom);
                transition.set('is_start', stepFrom.get('_is_start'));

                this.model
                    .get('transitions')
                    .add(transition);

                stepFrom
                    .getAllowedTransitions(this.model)
                    .add(transition);
            }
        },

        openManageStepForm: function(step) {
            var stepEditView = new StepEditView({
                'model': step
            });
            stepEditView.on('stepAdd', this.addStep, this);
            stepEditView.render();
        },

        addStep: function(step) {
            if (!this.model.get('steps').get(step.cid)) {
                this.model.get('steps').add(step);
            }
        },

        onStepRemove: function(step) {
            var removeTransitions = function (models) {
                //Cloned because of iterator elements removing in loop
                _.each(_.clone(models), function(transition) {
                    transition.destroy();
                });
            };

            //Remove step transitions
            removeTransitions(step.getAllowedTransitions(this.model).models);
            //Remove transitions which lead into removed step
            removeTransitions(this.model.get('transitions').where({'step_to': step.get('name')}));
        },

        render: function() {
            this.renderSteps();
            return this;
        }
    });
});
