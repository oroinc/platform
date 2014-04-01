/* global define */
define(['backbone', 'oroworkflow/js/workflow-management/attribute/model'],
function(Backbone, AttributeModel) {
    'use strict';

    /**
     * @export  oroworkflow/js/workflow-management/attribute/collection
     * @class   oro.workflowManagement.AttributeCollection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        model: AttributeModel
    });
});
