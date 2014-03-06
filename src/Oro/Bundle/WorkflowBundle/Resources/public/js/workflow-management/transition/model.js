/* global define */
define(['backbone'],
function(Backbone) {
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
            transition_definition: null
        },

        initialize: function() {
            if (this.get('form_options') === null) {
                this.set('form_options', {'attribute_fields': {}});
            }
        }
    });
});
