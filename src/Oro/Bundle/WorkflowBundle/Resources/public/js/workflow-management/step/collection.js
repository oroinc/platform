/* global define */
define(['backbone', 'oroworkflow/js/workflow-management/step/model'],
function(Backbone, StepModel) {
    'use strict';

    /**
     * @export  oroworkflow/js/workflow-management/step/collection
     * @class   oro.workflowManagement.StepCollection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        model: StepModel,
        comparator: 'order'
    });
});
