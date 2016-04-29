define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'routing',
    'oroui/js/tools',
    'oroui/js/mediator',
    './map-filter-module-name',
    './collection-filters-manager'
], function($, _, __, routing, tools, mediator, mapFilterModuleName, FiltersManager) {
    'use strict';

    var cachedFilters = {};

    var methods = {
        /**
         * Reads data from container, collects required modules and runs filters builder
         */
        initBuilder: function() {
            var modules;
            _.defaults(this.metadata, {filters: {}});
            modules = methods.collectModules.call(this);
            tools.loadModules(modules, function(modules) {
                this.modules = modules;
                methods.build.call(this);
            }, this);
        },

        /**
         * Collects required modules
         */
        collectModules: function() {
            var modules = {};
            _.each(this.metadata.filters || {}, function(filter) {
                var type = filter.type;
                modules[type] = mapFilterModuleName(type);
            });
            return modules;
        },

        build: function() {
            if (!this.collection || !this.modules) {
                return;
            }

            var filtersList;
            var options = methods.combineOptions.call(this);
            options.collection = this.collection;
            options.el = $('<div/>').prependTo(this.$el);
            filtersList = new FiltersManager(options);
            filtersList.render();
            mediator.trigger('datagrid_filters:rendered', this.collection, this.$el);
            this.metadata.state.filters = this.metadata.state.filters || [];
            if (this.collection.length === 0 && this.metadata.state.filters.length === 0) {
                filtersList.$el.hide();
            }

            this.grid.filterManager = filtersList;
            this.grid.trigger('filterManager:connected');

            this.deferred.resolve(filtersList);
        },

        /**
         * Process metadata and combines options for filters
         *
         * @returns {Object}
         */
        combineOptions: function() {
            var filters = {};
            var modules = this.modules;
            var collection = this.collection;
            _.each(this.metadata.filters, function(options) {
                if (_.has(options, 'name') && _.has(options, 'type')) {
                    // @TODO pass collection only for specific filters
                    if (options.type === 'selectrow') {
                        options.collection = collection;
                    }
                    if (options.lazy) {
                        options.loader = methods.createFilterLoader.call(this, options);
                    }
                    var Filter = modules[options.type].extend(options);
                    filters[options.name] = new Filter();
                }
            }, this);
            methods.loadFilters.call(this, this.metadata.options.gridName);

            return {
                filters: filters
            };
        },

        loadFilters: function(gridName) {
            var filterNames = _.map(this.filterLoaders, _.property('name'));
            if (!filterNames.length) {
                return;
            }

            _.chain(this.filterLoaders)
                .filter(_.property('useCache'))
                .each(function(loader) {
                    loader.success.call(this, cachedFilters[loader.cacheId]);
                });

            var params = {
                gridName: gridName,
                filterNames: _.map(this.filterLoaders, _.property('name'))
            };
            params[this.metadata.options.gridName] = this.metadata.gridParams;

            var url = routing.generate('oro_datagrid_filter_metadata', params);

            var self = this;
            $.get(url)
                .done(function(data) {
                    _.each(self.filterLoaders, function(loader) {
                        cachedFilters[data[loader.name].cacheId] = data[loader.name];
                        if (!loader.useCache) {
                            loader.success.call(this, data[loader.name]);
                        }
                    });
                })
                .fail(function() {
                    mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
                });
        },

        createFilterLoader: function(filterOptions) {
            return _.bind(function(success) {
                this.filterLoaders.push({
                    name: filterOptions.name,
                    cacheId: filterOptions.cacheId,
                    success: success,
                    useCache: filterOptions.cacheId && cachedFilters[filterOptions.cacheId]
                });
            }, this);
        }
    };

    return {
        /**
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
            var self;
            self = {
                filterLoaders: [],
                deferred: deferred,
                $el: options.$el,
                gridName: options.gridName,
                metadata: options.metadata,
                collection: null,
                modules: null
            };

            methods.initBuilder.call(self);

            options.gridPromise.done(function(grid) {
                self.collection = grid.collection;
                self.grid = grid;
                methods.build.call(self);
            }).fail(function() {
                deferred.reject();
            });
        }
    };
});
