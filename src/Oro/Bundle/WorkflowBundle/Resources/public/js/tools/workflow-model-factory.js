/** @exports WorkflowEditorComponent */
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var AttributeCollection = require('oroworkflow/js/app/models/attribute-collection');
    var StepCollection = require('oroworkflow/js/app/models/step-collection');
    var StepModel = require('oroworkflow/js/app/models/step-model');
    var TransitionCollection = require('oroworkflow/js/app/models/transition-collection');
    var TransitionDefinitionCollection = require('oroworkflow/js/app/models/transition-definition-collection');
    var WorkflowModel = require('oroworkflow/js/app/models/workflow-model');

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
        createWorkflowModel: function(options) {
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
        _createWorkflowModel: function(options) {
            var configuration = options.entity.configuration;
            var translateLinks = options.entity.translateLinks;
            var stepsConfig = _.map(configuration.steps, this._mergeName);
            if (translateLinks) {
                _.each(stepsConfig, function (step) {
                    step.translateLinks = translateLinks.steps[step.name];
                });
            }
            configuration.steps = new StepCollection(stepsConfig);

            var transitionsConfig = _.map(configuration.transitions, this._mergeName);
            if (translateLinks) {
                _.each(transitionsConfig, function (transition) {
                    transition.translateLinks = translateLinks.transitions[transition.name];
                });
            }
            configuration.transitions = new TransitionCollection(transitionsConfig);

            configuration.transition_definitions = new TransitionDefinitionCollection(
                _.map(configuration.transition_definitions, this._mergeName));

            var attributesConfig = _.map(configuration.attributes, this._mergeName);
            if (translateLinks) {
                _.each(attributesConfig, function (attribute) {
                    attribute.translateLinks = translateLinks.attributes[attribute.name];
                });
            }
            configuration.attributes = new AttributeCollection(attributesConfig);

            configuration.name = options.entity.name;
            configuration.label = options.entity.label;
            configuration.entity = options.entity.entity;
            configuration.entity_attribute = options.entity.entity_attribute;
            configuration.start_step = options.entity.startStep;
            configuration.steps_display_ordered = options.entity.stepsDisplayOrdered;

            var workflowModel = new WorkflowModel(configuration);
            workflowModel.setSystemEntities(options.system_entities);

            workflowModel.url = options._sourceElement.attr('action');

            return workflowModel;
        },

        /**
         * Adds a starting step to workflow model
         * @param {WorkflowModel} model
         */
        addStartingStep: function(model) {
            //if start step doesn't exist in database, create it
            if (model.getStartStep().length === 0) {
                model.get('steps').add(this._createStartingStep(model));
            }
        },

        /**
         * Creates a starting step
         *
         * @param {WorkflowModel} model
         * @returns {StepModel}
         * @private
         */
        _createStartingStep: function(model) {
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
        _mergeName: function(config, name) {
            config.name = name;
            return config;
        }
    };
});
