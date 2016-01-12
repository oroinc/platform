define(['backbone', 'underscore'], function(Backbone, _) {
    'use strict';

    var MetadataModel;
    var helpers = {
        actionType: function(type) {
            return type + 'Action';
        }
    };

    /**
     * Datagrid metadata model
     *
     * @export  orodatagrid/js/datagrid/metadata-model
     * @class   orodatagrid.datagrid.MetadataModel
     * @extends Backbone.Model
     */
    MetadataModel = Backbone.Model.extend({
        defaults: {
            columns: [],
            options: {},
            state: {},
            initialState: {},
            rowActions: {},
            massActions: {}
        },

        /**
         * @returns {Object}
         */
        getMassActionsOptions: function(modules) {
            var massActions = {};

            _.each(this.get('massActions'), function(options, action) {
                massActions[action] = modules[helpers.actionType(options.frontend_type)].extend(options);
            });

            return massActions;
        }
    });

    return MetadataModel;
});
