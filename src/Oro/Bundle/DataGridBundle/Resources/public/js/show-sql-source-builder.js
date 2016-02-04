define(function(require) {
    'use strict';

    var tools = require('oroui/js/tools');
    var ShowSqlSourcePlugin = require('orodatagrid/js/app/plugins/grid/show-sql-source-plugin');

    var showSqlSourceBuilder = {
        /**
         * Prepares and preloads all required files for inline editing plugin
         *
         * @param {jQuery.Deferred} deferred
         * @param {Object} options
         * @param {jQuery} [options.$el] container for the grid
         * @param {string} [options.gridName] grid name
         * @param {Object} [options.gridPromise] grid builder's promise
         * @param {Object} [options.data] data for grid's collection
         * @param {Object} [options.metadata] configuration for the grid
         */
        init: function(deferred, options) {
            if (tools.isMobile() || !options.metadata.options.show_sql_source) {
                deferred.resolve();
                return;
            }
            options.gridPromise.done(function(grid) {
                grid.pluginManager.create(ShowSqlSourcePlugin, options);
                grid.pluginManager.enable(ShowSqlSourcePlugin, options);
                deferred.resolve();
            });
        }
    };

    return showSqlSourceBuilder;
});
