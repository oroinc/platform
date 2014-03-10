/* global define */
define(['underscore', 'backbone', 'oroworkflow/js/workflow-management/transition/collection'],
function(_, Backbone, TransitionCollection) {
    'use strict';

    /**
     * @export  oroworkflow/js/workflow-management/step/model
     * @class   oro.workflowManagement.StepModel
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            name: null,
            label: null,
            is_final: false,
            order: 0,
            allowed_transitions: null,
            _is_start: false
        },

        initialize: function() {
            this.allowedTransitions = null;
            if (this.get('allowed_transitions') === null) {
                this.set('allowed_transitions', []);
            }
        },

        resetAllowedTransitions: function() {
            this.allowedTransitions = null;
            this.set('allowed_transitions', []);
        },

        getAllowedTransitions: function(workflowModel) {
            // Initialize allowedTransitions as Backbone.Collection instance.
            // allowed_transitions transition attribute should contain only names
            if (this.allowedTransitions === null) {
                var allowedTransitionsAttr = this.get('allowed_transitions');

                this.allowedTransitions = new TransitionCollection();
                if (_.isArray(allowedTransitionsAttr)) {
                    _.each(
                        allowedTransitionsAttr,
                        function (transitionName) {
                            this.allowedTransitions.add(workflowModel.getTransitionByName(transitionName));
                        },
                        this
                    );
                }

                var onTransitionAdd = _.bind(function(transition) {
                    var transitionName = transition.get('name');
                    if (allowedTransitionsAttr.indexOf(transitionName) === -1) {
                        this.get('allowed_transitions').push(transitionName);
                    }
                }, this);

                this.listenTo(this.allowedTransitions, 'add', onTransitionAdd);

                this.listenTo(this.allowedTransitions, 'reset', _.bind(function(transitions) {
                    this.set('allowed_transitions', []);
                    _.each(transitions.models, onTransitionAdd);
                }, this));

                this.listenTo(this.allowedTransitions, 'remove', _.bind(function(transition) {
                    var transitionName = transition.get('name');
                    this.set('allowed_transitions', _.without(this.get('allowed_transitions'), transitionName));
                }, this));
            }

            return this.allowedTransitions;
        }
    });
});
