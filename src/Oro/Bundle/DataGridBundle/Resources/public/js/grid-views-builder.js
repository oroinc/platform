define(function(require, exports, module) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const loadModules = require('oroui/js/app/services/load-modules');
    const GridViewsView = require('orodatagrid/js/datagrid/grid-views/view');
    const GridViewsCollection = require('orodatagrid/js/datagrid/grid-views/collection');
    const gridGridViewsSelector = '.page-title > .navbar-extra .pull-left-extra > .pull-left';

    let config = require('module-config').default(module.id);
    config = _.extend({
        GridViewsView: GridViewsView
    }, config);

    const gridViewsBuilder = {
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
            if (
                !options.enableViews ||
                $.isEmptyObject(options.metadata.gridViews) ||
                !options.metadata.gridViews.permissions.VIEW ||
                !options.metadata.filters
            ) {
                deferred.resolve();
                return;
            }

            const self = {
                metadata: _.defaults(options.metadata, {
                    gridViews: {},
                    options: {}
                }),
                gridViewsOptions: _.defaults({}, options.gridViewsOptions),
                enableViews: options.enableViews,
                $gridEl: options.$el,
                showInNavbar: options.showViewsInNavbar,
                showInCustomElement: options.showViewsInCustomElement,
                GridViewsView: config.GridViewsView,
                buildViews: function(grid) {
                    const gridViews = gridViewsBuilder.build.call(this, grid.collection);

                    if (gridViews !== void 0) {
                        grid.$grid.attr({'aria-labelledby': gridViews.uniqueId});
                    }
                    deferred.resolve(gridViews);
                }
            };

            if (_.isString(self.GridViewsView)) {
                self.buildViews = _.wrap(self.buildViews, function(buildViews, grid) {
                    loadModules(this.GridViewsView)
                        .then(GridViewsView => {
                            this.GridViewsView = GridViewsView;
                            buildViews.call(this, grid);
                        });
                });
            }

            options.gridPromise.done(function(grid) {
                if (_.contains(options.builders, 'orofilter/js/datafilter-builder')) {
                    if (self.$gridEl.find('.filter-box').length) {
                        self.buildViews(grid);
                    } else {
                        const _buildViews = function(collection, $gridEl) {
                            if (!$gridEl.is('#' + this.$gridEl.attr('id'))) {
                                return;
                            }

                            this.buildViews(grid);
                            mediator.off('datagrid_filters:rendered', _buildViews);
                        };

                        mediator.on('datagrid_filters:rendered', _buildViews, self);
                    }
                } else {
                    self.buildViews(grid);
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
            let gridViews;
            let $gridViews;
            const GridViewsView = this.GridViewsView;
            const options = gridViewsBuilder.combineGridViewsOptions.call(this);
            const gridViewsOptions = _.extend({collection: collection}, options);

            if (this.showInNavbar) {
                $gridViews = $(gridGridViewsSelector);
                gridViewsOptions.title = $gridViews.text();

                gridViews = new GridViewsView(gridViewsOptions);
                $gridViews.html(gridViews.render().$el);
            } else if (this.showInCustomElement) {
                gridViews = new GridViewsView(gridViewsOptions);
                $gridViews = $(this.showInCustomElement);
                $gridViews.html(gridViews.render().$el);
            } else {
                gridViews = new GridViewsView(gridViewsOptions);
                this.$gridEl.prepend(gridViews.render().$el);
            }

            return gridViews;
        },

        /**
         * Process metadata and combines options for datagrid views
         *
         * @returns {Object}
         */
        combineGridViewsOptions: function() {
            const options = this.metadata.gridViews;

            gridViewsBuilder.normalizeGridViewModelsData(options.views, this.metadata.initialState);
            const collection = new GridViewsCollection(options.views, {gridName: options.gridName});

            options.viewsCollection = collection;
            options.appearances = this.metadata.options.appearances;
            options.gridViewsOptions = this.gridViewsOptions;
            options.uniqueId = _.uniqueId(`grid-views-${options.gridName}`);

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
