/* global define */
define(['underscore', 'backbone', 'oro/workflow-management/transition/collection'],
function(_, Backbone, TransitionCollection) {
    'use strict';

    /**
     * @export  oro/workflow-management/step/model
     * @class   oro.workflowManagement.StepModel
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            name: '',
            label: '',
            isFinal: false,
            order: 0,
            allowedTransitions: null,
            _isStart: false
        },

        getAllowedTransitions: function(workflowModel) {
            var allowedTransitionsAttr = this.get('allowedTransitions');
            if (allowedTransitionsAttr instanceof Backbone.Collection) {
                return allowedTransitionsAttr;
            } else {
                var allowedTransitions = new TransitionCollection();
                if (_.isArray(allowedTransitionsAttr)) {
                    _.each(allowedTransitionsAttr, function (transitionName) {
                        allowedTransitions.add(workflowModel.getTransitionByName(transitionName));
                    });
                }

                return allowedTransitions;
            }
        }
    });
});
