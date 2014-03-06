/* global define */
define(['backbone', 'oro/workflow-management/attribute/model'],
function(Backbone, AttributeModel) {
    'use strict';

    /**
     * @export  oro/workflow-management/attribute/collection
     * @class   oro.workflowManagement.AttributeCollection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        model: AttributeModel
    });
});
