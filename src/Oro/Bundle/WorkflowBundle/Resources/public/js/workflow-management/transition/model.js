/* global define */
define(['underscore', 'backbone'],
function(_, Backbone) {
    'use strict';

    /**
     * @export  oroworkflow/js/workflow-management/transition/model
     * @class   oro.workflowManagement.TransitionModel
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            name: null,
            label: null,
            display_type: 'dialog',
            step_to: null,
            is_start: false,
            form_options: null,
            message: null,
            is_unavailable_hidden: true,
            transition_definition: null,
            _is_clone: false
        },

        initialize: function() {
            this.workflow = null;

            if (_.isEmpty(this.get('form_options'))) {
                this.set('form_options', {'attribute_fields': {}});
            }

            if (_.isEmpty(this.get('form_options').attribute_fields)) {
                this.get('form_options').attribute_fields = {};
            }
        },

        setWorkflow: function(workflow) {
            this.workflow = workflow;
        },

        getTransitionDefinition: function() {
            if (this.workflow) {
                return this.workflow.getTransitionDefinitionByName(this.get('transition_definition'));
            }
            return null;
        },

        destroy: function(options) {
            var transitionDefinition = this.getTransitionDefinition();
            if (transitionDefinition) {
                transitionDefinition.destroy();
            }

            Backbone.Model.prototype.destroy.call(this, options);
        }
    });
});
