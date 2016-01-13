define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var GridViewsView = require('orodatagrid/js/datagrid/grid-views/view');
    var GridViewsCollection = require('orodatagrid/js/datagrid/grid-views/collection');
    var gridContentManager = require('orodatagrid/js/content-manager');
    var gridGridViewsSelector = '.page-title > .navbar-extra .pull-left-extra > .pull-left';
    var gridViewsBuilder = {
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
        init: function(deferred, options) {
            var self = {
                metadata: _.defaults(options.metadata, {
                    gridViews: {},
                    options: {}
                }),
                enableViews: options.enableViews,
                $gridEl: options.$el,
                showInNavbar: options.showViewsInNavbar,
                buildViews: function(grid) {
                    var gridViews = gridViewsBuilder.build.call(this, grid.collection);
                    deferred.resolve(gridViews);
                }
            };

            options.gridPromise.done(function(grid) {
                if (_.contains(options.builders, 'orofilter/js/datafilter-builder')) {
                    if (self.$gridEl.find('.filter-box').length) {
                        self.buildViews.call(self, grid);
                    } else {
                        var _buildViews = function(collection, $gridEl) {
                            if (!$gridEl.is('#' + this.$gridEl.attr('id'))) {
                                return;
                            }

                            this.buildViews(grid);
                            mediator.off('datagrid_filters:rendered', _buildViews);
                        };

                        mediator.on('datagrid_filters:rendered', _buildViews, self);
                    }
                } else {
                    self.buildViews.call(self, grid);
                }
            }).fail(function() {
                deferred.reject();
            });
        },

        /**
         * Creates grid view
         *
         * @param {orodatagrid.PageableCollection} collection
         * @returns {orodatagrid.datagrid.GridViewsView}
         */
        build: function(collection) {
            var gridViews;
            var options = gridViewsBuilder.combineGridViewsOptions.call(this);
            if (!$.isEmptyObject(options) && this.metadata.filters && this.enableViews && options.permissions.VIEW) {
                var gridViewsOptions = _.extend({collection: collection}, options);

                if (this.showInNavbar) {
                    var $gridViews = $(gridGridViewsSelector);
                    gridViewsOptions.title = $gridViews.text();

                    gridViews = new GridViewsView(gridViewsOptions);
                    $gridViews.html(gridViews.render().$el);
                } else {
                    gridViews = new GridViewsView(gridViewsOptions);
                    this.$gridEl.prepend(gridViews.render().$el);
                }
            }
            return gridViews;
        },

        /**
         * Process metadata and combines options for datagrid views
         *
         * @returns {Object}
         */
        combineGridViewsOptions: function() {
            var options = this.metadata.gridViews;
            // check is grid views collection is stored in content manager
            var collection = gridContentManager.getViewsCollection(options.gridName);

            if (!collection) {
                collection = new GridViewsCollection(options.views, {gridName: options.gridName});
            }

            if (this.metadata.options.routerEnabled !== false) {
                // trace grid views collection changes
                gridContentManager.traceViewsCollection(collection);
            }

            options.viewsCollection = collection;
            return _.omit(options, ['choices', 'views']);
        }
    };

    return gridViewsBuilder;
});
