/* global define */
define(['underscore', 'jquery', 'backbone', 'oroworkflow/js/workflow-management/helper',
    'oroworkflow/js/workflow-management/step/collection',
    'oroworkflow/js/workflow-management/transition/collection',
    'oroworkflow/js/workflow-management/transition-definition/collection',
    'oroworkflow/js/workflow-management/attribute/collection',
    'oroworkflow/js/workflow-management/step/model',
    'oroworkflow/js/workflow-management/transition/model',
    'oroworkflow/js/workflow-management/transition-definition/model',
    'oroworkflow/js/workflow-management/attribute/model'

],
function(_, $, Backbone, Helper,
     StepCollection,
     TransitionCollection,
     TransitionDefinitionCollection,
     AttributeCollection,
     StepModel,
     TransitionModel,
     TransitionDefinitionModel,
     AttributeModel
) {
    'use strict';

    /**
     * @export  oroworkflow/js/workflow-management/workflow/model
     * @class   oro.workflowManagement.WorkflowModel
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            name: '',
            label: '',
            entity: '',
            entity_attribute: 'entity',
            start_step: null,
            steps_display_ordered: false,
            steps: null,
            transitions: null,
            transition_definitions: null,
            attributes: null
        },

        propertyPathToFieldIdMapping: {},
        pathMappingInitialized: false,

        initialize: function() {
            if (this.get('steps') === null) {
                this.set('steps', new StepCollection());
            }
            if (this.get('transitions') === null) {
                this.set('transitions', new TransitionCollection());
            }
            if (this.get('transition_definitions') === null) {
                this.set('transition_definitions', new TransitionDefinitionCollection());
            }
            if (this.get('attributes') === null) {
                this.set('attributes', new AttributeCollection());
            }
        },

        cloneTransitionDefinition: function(definition) {
            if (_.isString(definition)) {
                definition = this.getTransitionDefinitionByName(definition);
            }
            var cloned = this._getClonedItem(definition);

            var clonedModel = new TransitionDefinitionModel(cloned);
            this.get('transition_definitions').add(clonedModel);

            return clonedModel;
        },

        cloneTransition: function(transition) {
            if (_.isString(transition)) {
                transition = this.getTransitionByName(transition);
            }

            var transitionDefinition = this.cloneTransitionDefinition(transition.get('transition_definition'));

            var cloned = this._getClonedItem(transition);
            cloned.transition_definition = transitionDefinition.get('name');
            cloned.frontend_options = Helper.deepClone(cloned.frontend_options);
            cloned.form_options = Helper.deepClone(cloned.form_options);
            cloned.label = 'Copy of ' + cloned.label;

            var clonedModel = new TransitionModel(cloned);
            this.get('transitions').add(clonedModel);

            return clonedModel;
        },

        cloneStep: function(step) {
            if (_.isString(step)) {
                step = this.getStepByName(step);
            }

            var cloned = this._getClonedItem(step);
            var clonedAllowedTransitions = [];
            _.each(step.getAllowedTransitions(this).models, function(transition) {
                var clonedTransition = this.cloneTransition(transition);
                clonedAllowedTransitions.push(clonedTransition.get('name'));
            }, this);
            cloned.allowed_transitions = clonedAllowedTransitions;
            cloned.label = 'Copy of ' + cloned.label;

            var clonedModel = new StepModel(cloned);
            this.get('steps').add(clonedModel);

            return clonedModel;
        },

        _getClonedItem: function(item) {
            var cloned = _.clone(item.toJSON());
            cloned.name += '_clone_' + Helper.getRandomId();

            return cloned;
        },

        setPropertyPathToFieldIdMapping: function(mapping) {
            this.propertyPathToFieldIdMapping = mapping;
            this.pathMappingInitialized = true;
            this.trigger('pathMappingInit', mapping);
        },

        getFieldIdByPropertyPath: function(propertyPath) {
            return this.pathMappingInitialized && this.propertyPathToFieldIdMapping.hasOwnProperty(propertyPath)
                ? this.propertyPathToFieldIdMapping[propertyPath]
                : '';
        },

        getSystemEntities: function() {
            return this.systemEntities;
        },

        setSystemEntities: function(entities) {
            this.systemEntities = entities;
        },

        getOrAddAttributeByPropertyPath: function(propertyPath) {
            var attribute = _.first(this.get('attributes').where({'property_path': propertyPath}));
            if (!attribute) {
                attribute = new AttributeModel({
                    'name': propertyPath.replace(/\./g, '_'),
                    'property_path': propertyPath
                });
                this.get('attributes').add(attribute);
            }

            return attribute;
        },

        getAttributeByPropertyPath: function(propertyPath) {
            return _.first(this.get('attributes').where({'property_path': propertyPath}));
        },

        getAttributeByName: function(name) {
            return this._getByName('attributes', name);
        },

        getStepByName: function(name) {
            return this._getByName('steps', name);
        },

        getTransitionByName: function(name) {
            return this._getByName('transitions', name);
        },

        getTransitionDefinitionByName: function(name) {
            return this._getByName('transition_definitions', name);
        },

        getStartTransitions: function() {
            return this.get('transitions').where({'is_start': true});
        },

        _getByName: function(item, name) {
            return _.first(this.get(item).where({'name': name}));
        }
    });
});
