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
            displayType: 'dialog',
            stepTo: null,
            isStart: false,
            formOptions: null,
            message: null,
            isUnavailableHidden: true,
            transitionDefinition: null,
            preConditions: null,
            conditions: null,
            postActions: null
        }
    });
});
