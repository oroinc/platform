/*global define*/
/** @exports HiddenInitializationComponent */
define(function (require) {
    'use strict';

    var WorkflowEditorComponent,
        mediator = require('oroui/js/mediator'),
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        WorkflowManagement = require('oroworkflow/js/workflow-management'),
        WorkflowModel = require('oroworkflow/js/workflow-management/workflow/model'),
        StepModel = require('oroworkflow/js/workflow-management/step/model'),
        TransitionModel = require('oroworkflow/js/workflow-management/transition/model'),
        TransitionDefinitionModel = require('oroworkflow/js/workflow-management/transition-definition/model'),
        AttributeModel = require('oroworkflow/js/workflow-management/attribute/model'),
        StepCollection = require('oroworkflow/js/workflow-management/step/collection'),
        TransitionCollection = require('oroworkflow/js/workflow-management/transition/collection'),
        TransitionDefinitionCollection = require('oroworkflow/js/workflow-management/transition-definition/collection'),
        AttributeCollection = require('oroworkflow/js/workflow-management/attribute/collection');

    /**
     * Builds workflow editor UI.
     *
     * @class WorkflowEditorComponent
     * @augments BaseComponent
     */
    WorkflowEditorComponent = BaseComponent.extend(/** @lends WorkflowEditorComponent.prototype */{
        /**
         * @constructor
         * @inheritDoc
         */
        initialize: function (options) {
            this.element = options._sourceElement;
            if (!this.element) {
                return;
            }

            var steps = new StepCollection(),
                transitions = new TransitionCollection(),
                transitionDefinitions = new TransitionDefinitionCollection(),
                attributes = new AttributeCollection();

            function mergeName(config, name) {
                config.name = name;
                return config;
            }

            if (options.entity.configuration.steps) {
                steps.add(_.map(options.entity.configuration.steps, mergeName));
            }

            if (options.entity.configuration.transition_definitions) {
                transitionDefinitions.add(_.map(options.entity.configuration.transition_definitions, mergeName));
            }

            if (options.entity.configuration.transitions) {
                transitions.add(_.map(options.entity.configuration.transitions, mergeName));
            }

            if (options.entity.configuration.attributes) {
                attributes.add(_.map(options.entity.configuration.attributes, mergeName));
            }

            var configuration = options.entity.configuration;
            configuration.name = options.entity.name;
            configuration.label = options.entity.label;
            configuration.entity = options.entity.entity;
            configuration.entity_attribute = options.entity.entity_attribute;
            configuration.start_step = options.entity.startStep;
            configuration.steps_display_ordered = options.entity.stepsDisplayOrdered;
            configuration.steps = steps;
            configuration.transition_definitions = transitionDefinitions;
            configuration.transitions = transitions;
            configuration.attributes = attributes;

            var workflowModel = new WorkflowModel(configuration);
            workflowModel.setSystemEntities(options.system_entities);

            var workflowManagement = new WorkflowManagement({
                el: this.element,
                stepsEl: '.workflow-definition-steps-list-container',
                model: workflowModel
            });

            workflowManagement.render();
        }
    });

    return WorkflowEditorComponent;
});
