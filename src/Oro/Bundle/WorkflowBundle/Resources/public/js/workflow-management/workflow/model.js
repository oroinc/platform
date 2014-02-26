/* global define */
define(['underscore', 'backbone', 'oro/workflow-management/step/collection', 'oro/workflow-management/transition/collection'],
function(_, Backbone, StepCollection, TransitionCollection) {
    'use strict';

    /**
     * @export  oro/workflow-management/workflow/model
     * @class   oro.workflowManagement.WorkflowModel
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            name: '',
            label: '',
            relatedEntity: '',
            startStep: null,
            stepsDisplayOrdered: false,
            steps: null,
            transitions: null,
            transitionDefinitions: null
        },

        initialize: function() {
            if (this.get('steps') === null) {
                this.set('steps', new StepCollection());
            }
            if (this.get('transitions') === null) {
                this.set('transitions', new TransitionCollection());
            }
        },

        getStepByName: function(name) {
            return this._getByName('steps', name);
        },

        getTransitionByName: function(name) {
            return this._getByName('transitions', name);
        },

        getTransitionDefinitionByName: function(name) {
            return this._getByName('transitionDefinitions', name);
        },

        getStartTransitions: function() {
            return this.get('transitions').where({'isStart': true});
        },

        _getByName: function(item, name) {
            return _.first(this.get(item).where({'name': name}));
        }
    });
});
