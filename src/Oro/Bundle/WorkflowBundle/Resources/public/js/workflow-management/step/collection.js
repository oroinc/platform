/* global define */
define(['backbone', 'oro/workflow-management/step/model'],
function(Backbone, StepModel) {
    'use strict';

    /**
     * @export  oro/workflow-management/step/collection
     * @class   oro.workflowManagement.StepCollection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        model: StepModel,
        comparator: 'order'
    });
});
