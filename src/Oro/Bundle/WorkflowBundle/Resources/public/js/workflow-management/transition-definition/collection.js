/* global define */
define(['backbone', 'oroworkflow/js/workflow-management/transition-definition/model'],
function(Backbone, TransitionDefinitionModel) {
    'use strict';

    /**
     * @export  oroworkflow/js/workflow-management/transition-definition/collection
     * @class   oro.workflowManagement.TransitionDefinitionCollection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        model: TransitionDefinitionModel
    });
});
