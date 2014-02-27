/* global define */
define(['backbone', 'oro/workflow-management/transition/model'],
function(Backbone, TransitionModel) {
    'use strict';

    /**
     * @export  oro/workflow-management/transition/collection
     * @class   oro.workflowManagement.TransitionCollection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        model: TransitionModel
    });
});
