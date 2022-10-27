define(['backbone'], function(Backbone) {
    'use strict';

    /**
     * Datagrid metadata model
     *
     * @export  orodatagrid/js/datagrid/metadata-model
     * @class   orodatagrid.datagrid.MetadataModel
     * @extends Backbone.Model
     */
    const MetadataModel = Backbone.Model.extend({
        defaults: {
            columns: [],
            options: {},
            state: {},
            initialState: {},
            rowActions: {},
            massActions: {}
        },

        /**
         * @inheritdoc
         */
        constructor: function MetadataModel(attrs, options) {
            MetadataModel.__super__.constructor.call(this, attrs, options);
        }
    });

    return MetadataModel;
});
