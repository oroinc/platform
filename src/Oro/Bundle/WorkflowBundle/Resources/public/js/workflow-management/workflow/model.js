/* global define */
define(['underscore', 'backbone',
    'oroworkflow/js/workflow-management/step/collection',
    'oroworkflow/js/workflow-management/transition/collection',
    'oroworkflow/js/workflow-management/attribute/collection',
    'oroworkflow/js/workflow-management/attribute/model'],
function(_, Backbone, StepCollection, TransitionCollection, AttributeCollection, AttributeModel) {
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

        initialize: function() {
            if (this.get('steps') === null) {
                this.set('steps', new StepCollection());
            }
            if (this.get('transitions') === null) {
                this.set('transitions', new TransitionCollection());
            }
            if (this.get('attributes') === null) {
                this.set('attributes', new AttributeCollection());
            }
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
