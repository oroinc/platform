/* global define */
define(['backbone'],
function(Backbone) {
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
            isUnavailableHidden: true,
            order: null,
            allowedTransitions: null
        }
    });
});
