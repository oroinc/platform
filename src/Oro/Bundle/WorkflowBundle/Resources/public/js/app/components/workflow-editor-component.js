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
            var workflowModel, workflowManagement;

            workflowModel = this.createWorkflowModel(options);

            workflowManagement = new WorkflowManagement({
                el: options._sourceElement,
                stepsEl: '.workflow-definition-steps-list-container',
                model: workflowModel
            });

            workflowManagement.render();
        },

        /**
         * Helper function. Callback for _.map;
         *
         * @param config {{}}
         * @param name {string}
         * @returns {{}}
         * @private
         */
        _mergeName: function (config, name) {
            config.name = name;
            return config;
        },

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
        }
    });

    return WorkflowEditorComponent;
});
