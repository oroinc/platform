/*global define, console*/
/** @exports WorkflowEditorComponent */
define(function (require) {
    'use strict';

    var _ = require('underscore'),
        WorkflowModel = require('oroworkflow/js/workflow-management/workflow/model'),
        StepCollection = require('oroworkflow/js/workflow-management/step/collection'),
        TransitionCollection = require('oroworkflow/js/workflow-management/transition/collection'),
        TransitionDefinitionCollection = require('oroworkflow/js/workflow-management/transition-definition/collection'),
        StepModel = require('oroworkflow/js/workflow-management/step/model'),
        __ = require('orotranslation/js/translator'),
        AttributeCollection = require('oroworkflow/js/workflow-management/attribute/collection');

    /**
     * Builds workflow model.
     */
    return {
        /**
         * Creates workflow model
         *
         * @param {Object} options
         * @returns {WorkflowModel}
         */
        createWorkflowModel: function (options) {
            var model = this._createWorkflowModel(options);
            this.addStartingStep(model);
            return model;
        },

        /**
         * Creates workflow model
         *
         * @param {Object} options
         * @returns {WorkflowModel}
         * @private
         */
        _createWorkflowModel: function (options) {
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

            workflowModel.url = options._sourceElement.attr('action');

            return workflowModel;
        },

        /**
         * Adds a starting step to workflow model
         * @param {WorkflowModel} model
         */
        addStartingStep: function (model) {
            model.get('steps').add(this._createStartingStep(model));
        },

        /**
         * Creates a starting step
         *
         * @param {WorkflowModel} model
         * @returns {StepModel}
         * @private
         */
        _createStartingStep: function (model) {
            var startStepModel = new StepModel({
                name: 'step:starting_point',
                label: __('(Start)'),
                order: -1,
                _is_start: true
            });

            startStepModel
                .getAllowedTransitions(model)
                .reset(model.getStartTransitions());

            return startStepModel;
        },

        /**
         * Helper function. Callback for _.map;
         *
         * @param {Object} config
         * @param {string} name
         * @returns {Object}
         * @private
         */
        _mergeName: function (config, name) {
            config.name = name;
            return config;
        }
    };
});
