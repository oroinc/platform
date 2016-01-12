define(['backbone'], function(Backbone) {
    'use strict';

    var MetadataModel;

    /**
     * Datagrid metadata model
     *
     * @export  orodatagrid/js/datagrid/metadata-model
     * @class   orodatagrid.datagrid.MetadataModel
     * @extends Backbone.Model
     */
    MetadataModel = Backbone.Model.extend({
        defaults: {}
    });

    return MetadataModel;
});
