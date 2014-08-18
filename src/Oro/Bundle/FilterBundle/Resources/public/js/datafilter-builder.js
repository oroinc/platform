/*jshint browser:true*/
/*jslint nomen: true*/
/*global define, require*/
define([
    'jquery',
    'underscore',
    'oroui/js/tools',
    'oroui/js/mediator',
    './map-filter-module-name',
    './collection-filters-manager'
], function ($, _, tools,  mediator, mapFilterModuleName, FiltersManager) {
    'use strict';

    var methods = {
        /**
         * Reads data from container, collects required modules and runs filters builder
         */
        initBuilder: function () {
            var modules;
            _.defaults(this.metadata, {filters: {}});
            modules = methods.collectModules.call(this);
            tools.loadModules(modules, function (modules) {
                this.modules = modules;
                methods.build.call(this);
            }, this);
        },

        /**
         * Collects required modules
         */
        collectModules: function () {
            var modules = {};
            _.each(this.metadata.filters || {}, function (filter) {
                var type = filter.type;
                modules[type] = mapFilterModuleName(type);
            });
            return modules;
        },

        build: function () {
            var options, filtersList;
            if (!this.collection || !this.modules) {
                return;
            }

            options = methods.combineOptions.call(this);
            options.collection = this.collection;
            filtersList = new FiltersManager(options);
            this.$el.prepend(filtersList.render().$el);
            mediator.trigger('datagrid_filters:rendered', this.collection);
            this.metadata.state.filters = this.metadata.state.filters || [];
            if (this.collection.length === 0 && this.metadata.state.filters.length === 0) {
                filtersList.$el.hide();
            }

            this.deferred.resolve(filtersList);
        },

        /**
         * Process metadata and combines options for filters
         *
         * @returns {Object}
         */
        combineOptions: function () {
            var filters = {},
                modules = this.modules,
                collection = this.collection;
            _.each(this.metadata.filters, function (options) {
                if (_.has(options, 'name') && _.has(options, 'type')) {
                    // @TODO pass collection only for specific filters
                    if (options.type === 'selectrow') {
                        options.collection = collection;
                    }
                    var Filter = modules[options.type].extend(options);
                    filters[options.name] = new Filter();
                }
            });
            return {filters: filters};
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
        init: function (deferred, options) {
            var self;
            self = {
                deferred: deferred,
                $el: options.$el,
                gridName: options.gridName,
                metadata: options.metadata,
                collection: null,
                modules: null
            };

            methods.initBuilder.call(self);

            options.gridPromise.done(function (grid) {
                self.collection = grid.collection;
                methods.build.call(self);
            }).fail(function () {
                deferred.reject();
            });
        }
    };
});
