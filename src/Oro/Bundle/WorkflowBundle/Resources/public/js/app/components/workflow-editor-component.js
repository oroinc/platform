/*global define, console*/
/** @exports WorkflowEditorComponent */
define(function (require) {
    'use strict';

    var WorkflowEditorComponent,
        mediator = require('oroui/js/mediator'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        messenger = require('oroui/js/messenger'),
        tools = require('oroui/js/tools'),
        routing = require('routing'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        Helper = require('oroworkflow/js/workflow-management/helper'),
        WorkflowManagement = require('oroworkflow/js/workflow-management'),
        WorkflowModel = require('oroworkflow/js/workflow-management/workflow/model'),
        StepCollection = require('oroworkflow/js/workflow-management/step/collection'),
        TransitionModel = require('oroworkflow/js/workflow-management/transition/model'),
        TransitionCollection = require('oroworkflow/js/workflow-management/transition/collection'),
        TransitionDefinitionCollection = require('oroworkflow/js/workflow-management/transition-definition/collection'),
        AttributeCollection = require('oroworkflow/js/workflow-management/attribute/collection'),
        TransitionEditForm = require('oroworkflow/js/workflow-management/transition/view/edit'),
        StepEditView = require('oroworkflow/js/workflow-management/step/view/edit'),
        StepModel = require('oroworkflow/js/workflow-management/step/model'),
        Confirmation = require('oroui/js/delete-confirmation');

    /**
     * Builds workflow editor UI.
     *
     * @class WorkflowEditorComponent
     * @augments BaseComponent
     */
    WorkflowEditorComponent = BaseComponent.extend(/** @lends WorkflowEditorComponent.prototype */{

        listen: {
            'requestAddTransition model': 'addNewStepTransition',
            'requestAddStep model': 'addNewStep',
            'requestEditStep model': 'openManageStepForm',
            'requestCloneStep model': 'cloneStep',
            'requestRemoveStep model': 'removeStep',
            'requestRemoveTransition model': 'removeTransition',
            'requestCloneTransition model': 'cloneTransition',
            'requestEditTransition model': 'openManageTransitionForm',
            'change:entity model': 'resetWorkflow',
            'saveWorkflow model': 'saveConfiguration'
        },

        /**
         * @constructor
         * @inheritDoc
         */
        initialize: function (options) {
            this.model = this.createWorkflowModel(options);
            this.addStartingPoint();

            this.workflowManagementView = new WorkflowManagement({
                el: options._sourceElement,
                stepsEl: '.workflow-definition-steps-list-container',
                model: this.model
            });

            this.workflowManagementView.render();
        },

        /**
         * Helper function. Callback for _.map;
         *
         * @param config {object}
         * @param name {string}
         * @returns {{}}
         * @private
         */
        _mergeName: function (config, name) {
            config.name = name;
            return config;
        },

        /**
         * Creates workflow model
         *
         * @param options
         * @returns {WorkflowModel}
         */
        createWorkflowModel: function (options) {
            var workflowModel, configuration;

            configuration = options.entity.configuration;
            configuration.steps = new StepCollection(_.map(configuration.steps, this._mergeName));
            configuration.transitions = new TransitionCollection(_.map(configuration.transitions, this._mergeName));
            configuration.transition_definitions = new TransitionDefinitionCollection(
                _.map(configuration.transition_definitions, this._mergeName));
            configuration.attributes = new AttributeCollection(_.map(configuration.attributes, this._mergeName));

            configuration.name = options.entity.name;
            configuration.label = options.entity.label;
            configuration.entity = options.entity.entity;
            configuration.entity_attribute = options.entity.entity_attribute;
            configuration.start_step = options.entity.startStep;
            configuration.steps_display_ordered = options.entity.stepsDisplayOrdered;

            workflowModel = new WorkflowModel(configuration);
            workflowModel.setSystemEntities(options.system_entities);

            return workflowModel;
        },

        addNewStepTransition: function (step) {
            var transition = new TransitionModel();
            this.openManageTransitionForm(transition, step);
        },

        openManageTransitionForm: function (transition, stepFrom) {
            if (this.model.get('steps').length === 1) {
                this._showModalMessage(__('At least one step should be added to add transition.'), __('Warning'));
                return;
            }
            if (!this.workflowManagementView.isEntitySelected()) {
                this._showModalMessage(__('Related entity must be selected to add transition.'), __('Warning'));
                return;
            }

            var transitionEditView = new TransitionEditForm({
                'model': transition,
                'workflow': this.model,
                'step_from': stepFrom,
                'entity_select_el': this.workflowManagementView.getEntitySelect(),
                'workflowContainer': this.workflowManagementView.$el
            });
            transitionEditView.on('transitionAdd', this.addTransition, this);
            transitionEditView.render();
        },

        openManageStepForm: function (step) {
            if (!this.workflowManagementView.isEntitySelected()) {
                this._showModalMessage(__('Related entity must be selected to add step.'), __('Warning'));
                return;
            }

            var stepEditView = new StepEditView({
                'model': step,
                'workflow': this.model,
                'workflowContainer': this.workflowManagementView.$el
            });
            stepEditView.on('stepAdd', this.addStep, this);
            stepEditView.render();
        },

        addStep: function (step) {
            if (!this.model.get('steps').get(step.cid)) {
                this.model.get('steps').add(step);
            }
        },

        cloneStep: function (step) {
            var clonedStep = this.model.cloneStep(step, true);
            this.openManageStepForm(clonedStep);
        },

        removeStep: function (model) {
            this._removeHandler(model, __('Are you sure you want to delete this step?'));
        },

        _showModalMessage: function (message, title, okText) {
            var confirm = new Confirmation({
                title: title || '',
                content: message,
                okText: okText || __('OK'),
                allowCancel: false
            });
            confirm.open();
        },

        _removeHandler: function (model, message) {
            var confirm = new Confirmation({
                content: message
            });
            confirm.on('ok', function () {
                model.destroy();
            });
            confirm.open();
        },

        resetWorkflow: function () {
            //Need to manually destroy collection elements to trigger all appropriate events
            var resetCollection = function (collection) {
                if (collection.length) {
                    for (var i = collection.length - 1; i > -1; i--) {
                        collection.at(i).destroy();
                    }
                }
            };

            this.model.set('start_step', null);
            resetCollection(this.model.get('attributes'));
            resetCollection(this.model.get('steps'));
            resetCollection(this.model.get('transition_definitions'));
            resetCollection(this.model.get('transitions'));

            this.addStartingPoint();
        },

        addStartingPoint: function () {
            this.model.get('steps').add(this._createStartingPoint());
        },

        _createStartingPoint: function () {
            var startStepModel = new StepModel({
                'name': 'step:starting_point',
                'label': __('(Start)'),
                'order': -1,
                '_is_start': true
            });

            startStepModel
                .getAllowedTransitions(this.model)
                .reset(this.model.getStartTransitions());

            return startStepModel;
        },

        _getStartingPoint: function () {
            return this.model.getStepByName('step:starting_point');
        },

        saveConfiguration: function (e) {
            e.preventDefault();
            if (!this.workflowManagementView.valid()) {
                return;
            }

            var formData = Helper.getFormData(this.workflowManagementView.$el);
            formData.steps_display_ordered = formData.hasOwnProperty('steps_display_ordered');

            if (!this.model.get('name')) {
                this.model.set('name', Helper.getNameByString(formData.label, 'workflow_'));
            }
            this.model.set('label', formData.label);
            this.model.set('steps_display_ordered', formData.steps_display_ordered);
            this.model.set('entity', formData.related_entity);
            this.model.set('start_step', formData.start_step);

            if (!this.validateConfiguration()) {
                return;
            }

            mediator.execute('showLoading');
            this.model.save(null, {
                'success': _.bind(function () {
                    mediator.execute('hideLoading');

                    var redirectUrl = '',
                        modelName = this.model.get('name'),
                        saveAndClose = this.workflowManagementView.submitActor &&
                            this.workflowManagementView.submitActor.is('[data-action="save_and_stay"]');
                    if (saveAndClose) {
                        redirectUrl = routing.generate('oro_workflow_definition_view', { name: modelName });
                    } else {
                        redirectUrl = routing.generate('oro_workflow_definition_update', { name: modelName });
                    }

                    mediator.once('page:afterChange', function () {
                        messenger.notificationFlashMessage('success', __('Workflow saved.'));
                    });
                    mediator.execute('redirectTo', {url: redirectUrl});
                }, this),
                'error': function (model, response) {
                    mediator.execute('hideLoading');
                    var jsonResponse = response.responseJSON || {};

                    if (tools.debug && !_.isUndefined(console) && !_.isUndefined(jsonResponse.error)) {
                        console.error(jsonResponse.error);
                    }
                    messenger.notificationFlashMessage('error', __('Could not save workflow.'));
                }
            });
        },

        validateConfiguration: function () {
            // workflow label should be defined
            if (!this.model.get('label')) {
                messenger.notificationFlashMessage('error', __('Could not save workflow. Please set workflow name.'));
                return false;
            }

            // related entity should be defined
            if (!this.model.get('entity')) {
                messenger.notificationFlashMessage('error', __('Could not save workflow. Please set related entity.'));
                return false;
            }

            // at least one step and one transition must exist
            if (this.model.get('steps').length <= 1 || this.model.get('transitions').length === 0) {
                messenger.notificationFlashMessage(
                    'error',
                    __('Could not save workflow. Please add at least one step and one transition.')
                );
                return false;
            }

            // should be defined either start step or at least one start transition
            if (!this.model.get('start_step') && _.isEmpty(this._getStartingPoint().get('allowed_transitions'))) {
                messenger.notificationFlashMessage(
                    'error',
                    __('Could not save workflow. Please either set default step or add transitions to starting point.')
                );
                return false;
            }

            return true;
        },

        cloneTransition: function (transition, step) {
            var clonedTransition = this.model.cloneTransition(transition, true);
            this.openManageTransitionForm(clonedTransition, step);
        },

        addTransition: function (transition, stepFrom) {
            if (!this.model.get('transitions').get(transition.cid)) {
                if (_.isString(stepFrom)) {
                    stepFrom = this.model.getStepByName(stepFrom);
                }
                transition.set('is_start', stepFrom.get('_is_start'));

                this.model
                    .get('transitions')
                    .add(transition);

                stepFrom
                    .getAllowedTransitions(this.model)
                    .add(transition);
            }
        },

        addNewStep: function () {
            var step = new StepModel();
            this.openManageStepForm(step);
        },

        removeTransition: function (model) {
            this._removeHandler(model, __('Are you sure you want to delete this transition?'));
        }
    });

    return WorkflowEditorComponent;
});
