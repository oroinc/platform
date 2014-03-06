/* global define */
define(['underscore', 'backbone',
    'oroworkflow/js/workflow-management/step/collection',
    'oroworkflow/js/workflow-management/transition/collection'],
function(_, Backbone, StepCollection, TransitionCollection) {
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
            start_step: null,
            steps_display_ordered: false,
            steps: null,
            transitions: null,
            transition_definitions: null
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
