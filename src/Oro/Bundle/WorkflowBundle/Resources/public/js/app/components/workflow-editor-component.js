/* global define */
/** @exports WorkflowEditorComponent */
define(function (require) {
    'use strict';

    var WorkflowEditorComponent,
        WorkflowViewerComponent = require('./workflow-viewer-component'),
        WorkflowHistoryComponent = require('./workflow-history-component'),
        FlowchartEditorWorkflowView = require('../views/flowchart/editor/workflow-view'),
        mediator = require('oroui/js/mediator'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        messenger = require('oroui/js/messenger'),
        tools = require('oroui/js/tools'),
        routing = require('routing'),
        helper = require('oroworkflow/js/tools/workflow-helper'),
        WorkflowManagementView = require('../views/workflow-management-view'),
        TransitionModel = require('../models/transition-model'),
        TransitionEditFormView = require('../views/transition/transition-edit-view'),
        StepEditView = require('../views/step/step-edit-view'),
        StepModel = require('../models/step-model'),
        workflowModelFactory = require('../../tools/workflow-model-factory'),
        DeleteConfirmation = require('oroui/js/delete-confirmation');

    /**
     * Builds workflow editor UI.
     *
     * @class WorkflowEditorComponent
     * @augments WorkflowViewerComponent
     */
    WorkflowEditorComponent = WorkflowViewerComponent.extend(/** @lends WorkflowEditorComponent.prototype */{

        /**
         * @inheritDoc
         */
        initViews: function ($el, flowchartOptions) {
            this.workflowManagementView = new WorkflowManagementView({
                el: $el,
                stepsEl: '.workflow-definition-steps-list-container',
                model: this.model
            });
            this.historyManager = new WorkflowHistoryComponent({
                workflow: this.model,
                _sourceElement: $el.find('.workflow-history-container')
            });
            this.workflowManagementView.render();
            this.FlowchartWorkflowView = FlowchartEditorWorkflowView;
            WorkflowEditorComponent.__super__.initViews.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
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
         * Opens a "Add transition" dialog
         *
         * @param {StepModel} stepFrom
         * @param {StepModel=} stepTo
         */
        addNewStepTransition: function (stepFrom, stepTo) {
            var transition = new TransitionModel();
            this.openManageTransitionForm(transition, stepFrom, stepTo);
        },

        /**
         * Opens "Edit transition" dialog
         *
         * @param {TransitionModel} transition
         * @param {StepModel=} stepFrom
         * @param {StepModel=} stepTo
         */
        openManageTransitionForm: function (transition, stepFrom, stepTo) {
            if (this.model.get('steps').length === 1) {
                this._showModalMessage(__('At least one step should be added to add transition.'), __('Warning'));
                return;
            }
            if (!this.workflowManagementView.isEntitySelected()) {
                this._showModalMessage(__('Related entity must be selected to add transition.'), __('Warning'));
                return;
            }

            var transitionEditView = new TransitionEditFormView({
                'model': transition,
                'workflow': this.model,
                'step_from': stepFrom,
                'step_to': stepTo,
                'entity_select_el': this.workflowManagementView.getEntitySelect(),
                'workflowContainer': this.workflowManagementView.$el
            });
            transitionEditView.on('transitionAdd', this.addTransition, this);
            transitionEditView.render();
        },

        /**
         * Opens "Edit step" dialog
         *
         * @param {StepModel} step
         */
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

        /**
         * Utility function, adds step into workflow if it is unique
         *
         * @param {StepModel} step
         */
        addStep: function (step) {
            if (!this.model.get('steps').get(step.cid)) {
                this.model.get('steps').add(step);
            }
        },

        /**
         * Opens "Clone step" dialog
         *
         * @param {StepModel} step - step to clone
         */
        cloneStep: function (step) {
            var clonedStep = this.model.cloneStep(step, true);
            this.openManageStepForm(clonedStep);
        },

        /**
         * Opens "Remove step" dialog
         *
         * @param {StepModel} step - step to clone
         */
        removeStep: function (step) {
            this._removeHandler(step, __('Are you sure you want to delete this step?'));
        },

        /**
         * Shows message in popup
         *
         * @param {string} message
         * @param {string} title
         * @param {string} okText
         * @private
         */
        _showModalMessage: function (message, title, okText) {
            var confirm = new DeleteConfirmation({
                title: title || '',
                content: message,
                okText: okText || __('OK'),
                allowCancel: false
            });
            confirm.open();
        },

        /**
         * Handles model removement
         *
         * @param {Model} model
         * @param {string} message - Message to show in confirmation dialog
         * @private
         */
        _removeHandler: function (model, message) {
            var confirm = new DeleteConfirmation({
                content: message
            });
            confirm.on('ok', function () {
                model.destroy();
            });
            confirm.open();
        },

        /**
         * Clean ups workflow model
         */
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

            workflowModelFactory.addStartingStep(this.model);
        },

        /**
         * Returns a starting step
         *
         * @returns {StepModel}
         * @private
         */
        _getStartingStep: function () {
            return this.model.getStepByName('step:starting_point');
        },

        /**
         * Event callback for form submit. Saves workflow model to server and navigates out if required
         *
         * @param e
         */
        saveConfiguration: function (e) {
            e.preventDefault();
            if (!this.workflowManagementView.valid()) {
                return;
            }

            var formData = helper.getFormData(this.workflowManagementView.$el);
            formData.steps_display_ordered = formData.hasOwnProperty('steps_display_ordered');

            if (!this.model.get('name')) {
                this.model.set('name', helper.getNameByString(formData.label, 'workflow_'));
            }
            this.model.set('label', formData.label);
            this.model.set('steps_display_ordered', formData.steps_display_ordered);
            this.model.set('entity', formData.related_entity);
            this.model.set('start_step', formData.start_step);

            if (!this.validateWorkflow()) {
                return;
            }

            mediator.execute('showLoading');
            this.model.save(null, {
                'success': _.bind(function () {
                    mediator.execute('hideLoading');

                    var redirectUrl = '',
                        modelName = this.model.get('name'),
                        saveAndClose = this.workflowManagementView.submitActor &&
                            !$(this.workflowManagementView.submitActor).is('[data-action="save_and_stay"]');
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

        /**
         * Validates workflow model.
         *
         * @returns {boolean} validation result
         */
        validateWorkflow: function () {
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
            if (!this.model.get('start_step') && _.isEmpty(this._getStartingStep().get('allowed_transitions'))) {
                messenger.notificationFlashMessage(
                    'error',
                    __('Could not save workflow. Please either set default step or add transitions to starting point.')
                );
                return false;
            }

            return true;
        },

        /**
         * Opens "clone transition" dialog
         *
         * @param {TransitionModel} transition
         * @param {StepModel} stepFrom
         */
        cloneTransition: function (transition, stepFrom) {
            var clonedTransition = this.model.cloneTransition(transition, true);
            this.openManageTransitionForm(clonedTransition, stepFrom);
        },

        /**
         * Opens "add transition" dialog
         *
         * @param {TransitionModel} transition
         * @param {StepModel} stepFrom
         */
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
                stepFrom.trigger('change');
            }
        },

        /**
         * Opens "Add step" dialog
         */
        addNewStep: function () {
            var step = new StepModel();
            this.openManageStepForm(step);
        },

        /**
         * Opens "Remove step" dialog
         *
         * @param {TransitionModel} transition
         */
        removeTransition: function (transition) {
            this._removeHandler(transition, __('Are you sure you want to delete this transition?'));
        }
    });

    return WorkflowEditorComponent;
});
