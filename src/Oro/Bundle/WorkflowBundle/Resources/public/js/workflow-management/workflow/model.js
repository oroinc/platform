/* global define */
define(['backbone'],
function(Backbone) {
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
        }
    });
});
