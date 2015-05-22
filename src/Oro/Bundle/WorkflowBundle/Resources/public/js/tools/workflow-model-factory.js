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
         * @returns {WorkflowModel|*}
         */
        create: function (options) {
            this.model = this.createWorkflowModel(options);
            this.addStartingStep();
            return this.model;
        },

        /**
         * Adds a starting step to workflow model
         */
        addStartingStep: function () {
            this.model.get('steps').add(this._createStartingStep());
        },

        /**
         * Creates a starting step
         *
         * @returns {StepModel}
         * @private
         */
        _createStartingStep: function () {
            var startStepModel = new StepModel({
                name: 'step:starting_point',
                label: __('(Start)'),
                order: -1,
                _is_start: true
            });

            startStepModel
                .getAllowedTransitions(this.model)
                .reset(this.model.getStartTransitions());

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
        },

        /**
         * Creates workflow model
         *
         * @param {Object} options
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

            workflowModel.url = options._sourceElement.attr('action');

            return workflowModel;
        }
    };
});
