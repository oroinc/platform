/*jslint nomen:true */
/*global define, require*/
define(function (require) {
    'use strict';

    var gridViewsBuilder, gridGridViewsSelector,
        $ = require('jquery'),
        _ = require('underscore'),
        GridViewsView = require('orodatagrid/js/datagrid/grid-views/view');

    gridGridViewsSelector = '.page-title > .navbar-extra .span9:last';

    gridViewsBuilder = {
        /**
         * Runs grid views builder
         * Builder interface implementation
         *
         * @param {jQuery.Deferred} deferred
         * @param {Object} options
         * @param {jQuery} [options.$el] container for the grid
         * @param {string} [options.gridName] grid name
         * @param {Object} [options.gridPromise] grid builder's promise
         * @param {Object} [options.data] data for grid's collection
         * @param {Object} [options.metadata] configuration for the grid
         */
        init: function (deferred, options) {
            var self = {
                metadata: _.defaults(options.metadata, {
                    gridViews: {}
                })
            };

            options.gridPromise.done(function (grid) {
                var gridViews = gridViewsBuilder.build.call(self, grid.collection);
                deferred.resolve(gridViews);
            }).fail(function () {
                deferred.reject();
            });
        },

        /**
         * Creates grid view
         *
         * @param {orodatagrid.PageableCollection} collection
         * @returns {orodatagrid.datagrid.GridViewsView}
         */
        build: function (collection) {
            var options, gridViews;
            options = gridViewsBuilder.combineGridViewsOptions.call(this);
            gridViews = new GridViewsView(_.extend({collection: collection}, options));
            $(gridGridViewsSelector).append(gridViews.render().$el);

            return gridViews;
        },

        /**
         * Process metadata and combines options for datagrid views
         *
         * @returns {Object}
         */
        combineGridViewsOptions: function () {
            return this.metadata.gridViews;
        }
    };


    return gridViewsBuilder;
});
