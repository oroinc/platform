define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var GridViewsView = require('orodatagrid/js/datagrid/grid-views/view');
    var GridViewsCollection = require('orodatagrid/js/datagrid/grid-views/collection');
    var gridContentManager = require('orodatagrid/js/content-manager');
    var gridGridViewsSelector = '.page-title > .navbar-extra .pull-left-extra > .pull-left';

    var config = require('module').config();
    config = _.extend({
        GridViewsView: GridViewsView
    }, config);

    var gridViewsBuilder = {
        /** @property */
        GridViewsView: config.GridViewsView,

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
                gridViewsOptions: _.defaults({}, options.gridViewsOptions),
                enableViews: options.enableViews,
                $gridEl: options.$el,
                showInNavbar: options.showViewsInNavbar,
                showInCustomElement: options.showViewsInCustomElement,
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
            var self = this;
            var GridViewsView;
            var gridViews;
            var $gridViews;
            var opts = gridViewsBuilder.combineGridViewsOptions.call(this);
            var deferred = $.Deferred();

            if (_.isString(gridViewsBuilder.GridViewsView)) {
                tools.loadModules(gridViewsBuilder.GridViewsView, function(View) {
                    GridViewsView = View;
                    deferred.resolve();
                });
            } else {
                GridViewsView = gridViewsBuilder.GridViewsView;
                deferred.resolve();
            }

            deferred.done(function() {
                if (!$.isEmptyObject(opts) && self.metadata.filters && self.enableViews && opts.permissions.VIEW) {
                    var gridViewsOptions = _.extend({collection: collection}, opts);

                    if (self.showInNavbar) {
                        $gridViews = $(gridGridViewsSelector);
                        gridViewsOptions.title = $gridViews.text();

                        gridViews = new GridViewsView(gridViewsOptions);
                        $gridViews.html(gridViews.render().$el);
                    } else if (self.showInCustomElement) {
                        gridViews = new GridViewsView(gridViewsOptions);
                        $gridViews = $(self.showInCustomElement);
                        $gridViews.html(gridViews.render().$el);
                    } else {
                        gridViews = new GridViewsView(gridViewsOptions);
                        self.$gridEl.prepend(gridViews.render().$el);
                    }
                }
                return gridViews;
            });
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
                gridViewsBuilder.normalizeGridViewModelsData(options.views, this.metadata.initialState);
                collection = new GridViewsCollection(options.views, {gridName: options.gridName});
            }

            if (this.metadata.options.routerEnabled !== false) {
                // trace grid views collection changes
                gridContentManager.traceViewsCollection(collection);
            }

            options.viewsCollection = collection;
            options.appearances = this.metadata.options.appearances;
            options.gridViewsOptions = this.gridViewsOptions;

            return _.omit(options, ['views']);
        },

        normalizeGridViewModelsData: function(items, initialState) {
            _.each(items, function(item) {
                _.each(['columns', 'sorter'], function(attr) {
                    if (_.isEmpty(item[attr])) {
                        $.extend(true, item, _.pick(initialState, attr));
                    }
                });
            });
        }
    };

    return gridViewsBuilder;
});
