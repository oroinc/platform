/* global define */
define(['backbone'],
function(Backbone) {
    'use strict';

    /**
     * @export  oroworkflow/js/workflow-management/attribute/model
     * @class   oro.workflowManagement.AttributeModel
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            name: null,
            label: null,
            translated_label: null,
            type: null,
            property_path: null,
            options: {}
        }
    });
});
