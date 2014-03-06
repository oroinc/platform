/* global define */
define(['backbone', 'oroworkflow/js/workflow-management/transition/model'],
function(Backbone, TransitionModel) {
    'use strict';

    /**
     * @export  oroworkflow/js/workflow-management/transition/collection
     * @class   oro.workflowManagement.TransitionCollection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        model: TransitionModel
    });
});
