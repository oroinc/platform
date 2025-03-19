/** @exports WorkflowEditorComponent */
define(function(require) {
    'use strict';

    const WorkflowViewerComponent = require('./workflow-viewer-component');
    const HistoryNavigationComponent = require('oroui/js/app/components/history-navigation-component');
    const FlowchartEditorWorkflowView = require('../views/flowchart/editor/workflow-view');
    const mediator = require('oroui/js/mediator');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const messenger = require('oroui/js/messenger');
    const routing = require('routing');
    const helper = require('oroworkflow/js/tools/workflow-helper');
    const WorkflowManagementView = require('../views/workflow-management-view');
    const TransitionModel = require('../models/transition-model');
    const TransitionEditFormView = require('../views/transition/transition-edit-view');
    const StepEditView = require('../views/step/step-edit-view');
    const StepModel = require('../models/step-model');
    const workflowModelFactory = require('../../tools/workflow-model-factory');
    const EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');
    const DeleteConfirmation = require('oroui/js/delete-confirmation');

    /**
     * Builds workflow editor UI.
     *
     * @class WorkflowEditorComponent
     * @augments WorkflowViewerComponent
     */
    const WorkflowEditorComponent = WorkflowViewerComponent.extend(/** @lends WorkflowEditorComponent.prototype */{
        FlowchartWorkflowView: FlowchartEditorWorkflowView,

        /**
         * @inheritdoc
         */
        listen: {
            'requestAddTransition model': 'addNewStepTransition',
            'requestAddStep model': 'addNewStep',
            'requestRefreshChart model': 'refreshChart',
            'requestEditStep model': 'openManageStepForm',
            'requestCloneStep model': 'cloneStep',
            'requestRemoveStep model': 'removeStep',
            'requestRemoveTransition model': 'removeTransition',
            'requestCloneTransition model': 'cloneTransition',
            'requestEditTransition model': 'openManageTransitionForm',
            'change:entity model': 'onEntityChange',
            'saveWorkflow model': 'saveConfiguration'
        },

        /**
         * @inheritdoc
         */
        constructor: function WorkflowEditorComponent(options) {
            WorkflowEditorComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            const providerOptions = {
                filterPreset: 'workflow'
            };

            const entity = _.result(options, 'entity');
            if (entity) {
                providerOptions.rootEntity = _.result(entity, 'entity');
            }

            const providerPromise = EntityStructureDataProvider.createDataProvider(providerOptions, this)
                .then(function(provider) {
                    this.entityFieldsProvider = provider;
                    this.initManagementView(options._sourceElement);
                }.bind(this));

            this._initPromises.push(providerPromise);

            WorkflowEditorComponent.__super__.initialize.call(this, options);

            this.historyManager = new HistoryNavigationComponent({
                observedModel: this.model,
                _sourceElement: options._sourceElement.find('.workflow-history-container')
            });
        },

        /**
         * @inheritdoc
         */
        initManagementView: function($el) {
            this.workflowManagementView = new WorkflowManagementView({
                autoRender: true,
                el: $el,
                stepsEl: '.workflow-definition-steps-list-container',
                model: this.model,
                entityFieldsProvider: this.entityFieldsProvider
            });
        },

        /**
         * Automatically organizes steps on flowchart. Fits flowchart to screen.
         */
        refreshChart: function() {
            if (this.flowchartContainerView) {
                this.flowchartContainerView.refresh();
            }
        },

        /**
         * Opens a "Add transition" dialog
         *
         * @param {StepModel} stepFrom
         * @param {StepModel=} stepTo
         */
        addNewStepTransition: function(stepFrom, stepTo) {
            const transition = new TransitionModel();
            this.openManageTransitionForm(transition, stepFrom, stepTo);
        },

        /**
         * Opens "Edit transition" dialog
         *
         * @param {TransitionModel} transition
         * @param {StepModel=} stepFrom
         * @param {StepModel=} stepTo
         */
        openManageTransitionForm: function(transition, stepFrom, stepTo) {
            if (this.model.get('steps').length === 1) {
                this._showModalMessage(__('At least one step should be added to add transition.'), __('Warning'));
                return;
            }
            if (!this.workflowManagementView.isEntitySelected()) {
                this._showModalMessage(__('Related entity must be selected to add transition.'), __('Warning'));
                return;
            }

            const transitionEditView = new TransitionEditFormView({
                autoRender: true,
                model: transition,
                workflow: this.model,
                entityFieldsProvider: this.entityFieldsProvider,
                step_from: stepFrom,
                step_to: stepTo,
                entity: this.workflowManagementView.getEntitySelectValue(),
                workflowContainer: this.workflowManagementView.$el
            });
            transitionEditView.on('transitionAdd', this.addTransition, this);
        },

        /**
         * Opens "Edit step" dialog
         *
         * @param {StepModel} step
         */
        openManageStepForm: function(step) {
            if (!this.workflowManagementView.isEntitySelected()) {
                this._showModalMessage(__('Related entity must be selected to add step.'), __('Warning'));
                return;
            }

            const stepEditView = new StepEditView({
                model: step,
                workflow: this.model,
                workflowContainer: this.workflowManagementView.$el
            });
            stepEditView.on('stepAdd', this.addStep, this);
            stepEditView.render();
        },

        /**
         * Utility function, adds step into workflow if it is unique
         *
         * @param {StepModel} step
         */
        addStep: function(step) {
            if (!this.model.get('steps').get(step.cid)) {
                this.model.get('steps').add(step);
            }
        },

        /**
         * Opens "Clone step" dialog
         *
         * @param {StepModel} step - step to clone
         */
        cloneStep: function(step) {
            const clonedStep = this.model.cloneStep(step, true);
            this.openManageStepForm(clonedStep);
        },

        /**
         * Opens "Remove step" dialog
         *
         * @param {StepModel} step - step to clone
         */
        removeStep: function(step) {
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
        _showModalMessage: function(message, title, okText) {
            const confirm = new DeleteConfirmation({
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
        _removeHandler: function(model, message) {
            const confirm = new DeleteConfirmation({
                content: message
            });
            confirm.on('ok', function() {
                model.destroy();
            });
            confirm.open();
        },

        onEntityChange: function(model, value) {
            this.entityFieldsProvider.setRootEntityClassName(value);
            this.resetWorkflow();
        },

        /**
         * Clean ups workflow model
         */
        resetWorkflow: function() {
            // Need to manually destroy collection elements to trigger all appropriate events
            const resetCollection = function(collection) {
                if (collection.length) {
                    for (let i = collection.length - 1; i > -1; i--) {
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
        _getStartingStep: function() {
            return this.model.getStepByName('step:starting_point');
        },

        /**
         * Event callback for form submit. Saves workflow model to server and navigates out if required
         *
         * @param e
         */
        saveConfiguration: function(e) {
            e.preventDefault();
            if (!this.workflowManagementView.valid()) {
                return;
            }

            const formData = helper.getFormData(this.workflowManagementView.$el);
            formData.steps_display_ordered = formData.hasOwnProperty('steps_display_ordered');

            if (!this.model.get('name')) {
                this.model.set('name', helper.getNameByString(formData.label, 'workflow_'));
            }
            this.model.set('label', formData.label);
            this.model.set('steps_display_ordered', formData.steps_display_ordered);
            this.model.set('entity', formData.related_entity);
            this.model.set('start_step', formData.start_step);
            this.model.set('exclusive_active_groups', formData.exclusive_active_groups);
            this.model.set('exclusive_record_groups', formData.exclusive_record_groups);

            if (!this.validateWorkflow()) {
                return;
            }

            mediator.execute('showLoading');
            this.model.save(null, {
                success: () => {
                    mediator.execute('hideLoading');

                    let redirectUrl = '';
                    const modelName = this.model.get('name');
                    const saveAndClose = this.workflowManagementView.submitActor &&
                            !$(this.workflowManagementView.submitActor).is('[data-action="save_and_stay"]');
                    if (saveAndClose) {
                        redirectUrl = routing.generate('oro_workflow_definition_view', {name: modelName});
                    } else {
                        redirectUrl = routing.generate('oro_workflow_definition_update', {name: modelName});
                    }

                    mediator.once('page:afterChange', function() {
                        messenger.notificationFlashMessage('success', __('Workflow saved.'));
                    });
                    mediator.execute('redirectTo', {url: redirectUrl}, {redirect: true});
                },
                errorHandlerMessage: (event, response) => {
                    let message = __('Could not save workflow.');
                    if (response.responseJSON && response.responseJSON.error) {
                        message = response.responseJSON.error;
                    }

                    return message;
                },
                error: (model, response) => {
                    mediator.execute('hideLoading');
                }
            });
        },

        /**
         * Validates workflow model.
         *
         * @returns {boolean} validation result
         */
        validateWorkflow: function() {
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
        cloneTransition: function(transition, stepFrom) {
            const clonedTransition = this.model.cloneTransition(transition, true);
            this.openManageTransitionForm(clonedTransition, stepFrom);
        },

        /**
         * Opens "add transition" dialog
         *
         * @param {TransitionModel} transition
         * @param {StepModel} stepFrom
         */
        addTransition: function(transition, stepFrom) {
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
                stepFrom.trigger('change', stepFrom, {});
            }
        },

        /**
         * Opens "Add step" dialog
         */
        addNewStep: function() {
            const step = new StepModel({
                order: this.model.get('steps').getOrderForNew()
            });
            this.openManageStepForm(step);
        },

        /**
         * Opens "Remove step" dialog
         *
         * @param {TransitionModel} transition
         */
        removeTransition: function(transition) {
            this._removeHandler(transition, __('Are you sure you want to delete this transition?'));
        }
    });

    return WorkflowEditorComponent;
});
