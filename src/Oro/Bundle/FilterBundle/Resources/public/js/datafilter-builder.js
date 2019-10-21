define(function(require, exports, module) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var tools = require('oroui/js/tools');
    var mapFilterModuleName = require('orofilter/js/map-filter-module-name');
    var FiltersManager = require('orofilter/js/collection-filters-manager');
    var FiltersTogglePlugin = require('orofilter/js/plugins/filters-toggle-plugin');
    var config = require('module-config').default(module.id);
    var cachedFilters = {};

    config = _.extend({
        FiltersManager: FiltersManager
    }, config);

    var methods = {
        /**
         * Reads data from container, collects required modules and runs filters builder
         */
        initBuilder: function() {
            var modules;
            var deferred = $.Deferred();

            _.defaults(this.metadata, {filters: {}});
            modules = methods.collectModules.call(this);
            tools.loadModules(modules, function(modules) {
                this.modules = modules;
                deferred.resolve();
            }, this);

            return deferred.promise();
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

            if (_.isString(config.FiltersManager)) {
                modules.FiltersManager = config.FiltersManager;
            }
            return modules;
        },

        build: function() {
            if (!this.collection || !this.modules) {
                return;
            }

            FiltersManager = this.modules.FiltersManager || FiltersManager;

            var options = _.extend(
                methods.combineOptions.call(this),
                _.pick(this, 'collection'),
                _.pick(this.metadata.options, 'defaultFiltersViewMode', 'filtersStateStorageKey',
                    'useFiltersStateAnimationOnInit')
            );

            var filterContainer;

            if (this.filterContainerSelector) {
                // Since potentially filter container can be moved outside grid container by another component
                // we try to find it everywhere before deciding to use grid container instead
                filterContainer = this.$el.find(this.filterContainerSelector)[0] ||
                    $(this.filterContainerSelector)[0];
            }

            if (!filterContainer) {
                filterContainer = this.$el[0];
            }

            if (!this.enableToggleFilters || _.result(this.metadata.options.toolbarOptions, 'hide') === true) {
                options.forcedViewMode = FiltersManager.MANAGE_VIEW_MODE;
            } else if (this.filtersStateElement) {
                options.filtersStateElement = this.filtersStateElement;
            } else {
                var $container = this.$el.closest('body, .ui-dialog').find(options.filtersStateElement).first();

                options.filtersStateElement = $container.length
                    ? $container : $('<div/>').prependTo(filterContainer);
            }

            var filtersList = new FiltersManager(options);
            filtersList.render();
            filtersList.$el.prependTo(filterContainer);

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

            _.extend(self, _.pick(options, 'filtersStateElement', 'filterContainerSelector', 'enableToggleFilters'));

            $.when(options.gridPromise, methods.initBuilder.call(self)).done(function(grid) {
                self.collection = grid.collection;
                self.grid = grid;
                methods.build.call(self);
            }).fail(function() {
                deferred.reject();
            });
        },

        processDatagridOptions: function(deferred, options) {
            if (!_.isArray(options.metadata.plugins)) {
                options.metadata.plugins = [];
            }

            if (_.result(config, 'enableToggleFilters') === false) {
                options.enableToggleFilters = false;
            }

            if (options.enableToggleFilters) {
                options.metadata.plugins.push(FiltersTogglePlugin);
            }

            if (_.isFunction(FiltersTogglePlugin.isApplicable) && FiltersTogglePlugin.isApplicable(options) === false) {
                options.enableToggleFilters = false;
            }

            deferred.resolve();
        }
    };
});
