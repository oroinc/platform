define(function(require) {
    'use strict';

    var tools = require('oroui/js/tools');
    var ViewSqlQueryPlugin = require('ororeport/js/app/plugins/grid/view-sql-query-plugin');

    var ViewSqlQueryBuilder = {
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
            if (tools.isMobile() || !options.data.metadata.display_sql_query) {
                deferred.resolve();
                return;
            }
            options.gridPromise.done(function(grid) {
                grid.pluginManager.create(ViewSqlQueryPlugin, options);
                grid.pluginManager.enable(ViewSqlQueryPlugin, options);
                deferred.resolve();
            });
        }
    };

    return ViewSqlQueryBuilder;
});
