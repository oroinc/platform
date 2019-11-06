/** @exports WorkflowEditorComponent */
define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const AttributeCollection = require('oroworkflow/js/app/models/attribute-collection');
    const StepCollection = require('oroworkflow/js/app/models/step-collection');
    const StepModel = require('oroworkflow/js/app/models/step-model');
    const TransitionCollection = require('oroworkflow/js/app/models/transition-collection');
    const TransitionDefinitionCollection = require('oroworkflow/js/app/models/transition-definition-collection');
    const WorkflowModel = require('oroworkflow/js/app/models/workflow-model');

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
            const model = this._createWorkflowModel(options);
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
            const configuration = options.entity.configuration;
            const translateLinks = options.entity.translateLinks;

            configuration.steps = new StepCollection(
                this._getNodeConfiguration(configuration, 'steps', translateLinks)
            );

            configuration.transitions = new TransitionCollection(
                this._getNodeConfiguration(configuration, 'transitions', translateLinks)
            );

            configuration.attributes = new AttributeCollection(
                this._getNodeConfiguration(configuration, 'attributes', translateLinks)
            );

            configuration.transition_definitions = new TransitionDefinitionCollection(
                this._getNodeConfiguration(configuration, 'transition_definitions', translateLinks)
            );

            configuration.name = options.entity.name;
            configuration.label = options.entity.label;
            configuration.entity = options.entity.entity;
            configuration.entity_attribute = options.entity.entity_attribute;
            configuration.start_step = options.entity.startStep;
            configuration.steps_display_ordered = options.entity.stepsDisplayOrdered;
            configuration.priority = options.entity.priority;
            configuration.exclusive_active_groups = options.entity.exclusive_active_groups;
            configuration.exclusive_record_groups = options.entity.exclusive_record_groups;
            configuration.applications = options.entity.applications;

            const workflowModel = new WorkflowModel(configuration);
            workflowModel.setAvailableDestinations(options.availableDestinations || {});

            workflowModel.url = options._sourceElement.attr('action');
            if (translateLinks && 'label' in translateLinks) {
                workflowModel.translateLinkLabel = translateLinks.label;
            }

            return workflowModel;
        },

        /**
         * Adds a starting step to workflow model
         * @param {WorkflowModel} model
         */
        addStartingStep: function(model) {
            // if start step doesn't exist in database, create it
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
            const startStepModel = new StepModel({
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
         * Get and process configuration node;
         *
         * @param {Object} config
         * @param {string} node
         * @param {Object} translateLinks
         * @returns {Object}
         * @private
         */
        _getNodeConfiguration: function(config, node, translateLinks) {
            const updateConfig = [];
            _.each(config[node], function(item, name) {
                item.name = name;
                if (translateLinks && node in translateLinks && name in translateLinks[node]) {
                    item.translateLinks = translateLinks[node][name];
                }
                updateConfig.push(item);
            });

            return updateConfig;
        }
    };
});
