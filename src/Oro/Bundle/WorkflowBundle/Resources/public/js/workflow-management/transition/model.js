/* global define */
define(['backbone'],
function(Backbone) {
    'use strict';

    /**
     * @export  oro/workflow-management/transition/model
     * @class   oro.workflowManagement.TransitionModel
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            name: '',
            label: '',
            display_type: 'dialog',
            step_to: null,
            is_start: false,
            form_options: {},
            message: null,
            is_unavailable_hidden: true,
            transition_definition: null
        }
    });
});
