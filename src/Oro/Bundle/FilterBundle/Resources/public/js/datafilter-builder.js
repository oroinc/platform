/*jshint browser:true*/
/*jslint nomen: true*/
/*global define, require*/
define(['jquery', 'underscore', 'oroui/js/tools', 'oroui/js/mediator',
        './map-filter-module-name', './collection-filters-manager'
    ], function ($, _, tools,  mediator, mapFilterModuleName, FiltersManager) {
    'use strict';

    var methods = {
        /**
         * Reads data from container, collects required modules and runs filters builder
         */
        initBuilder: function () {
            var modules;
            this.metadata = _.extend({filters: {}}, this.$el.data('metadata'));
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
            if (!this.collection || !this.modules) {
                return;
            }

            var options = methods.combineOptions.call(this);
            options.collection = this.collection;
            var filtersList = new FiltersManager(options);
            this.$el.prepend(filtersList.render().$el);
            mediator.trigger('datagrid_filters:rendered', this.collection);
            this.metadata.state.filters = this.metadata.state.filters || [];
            if (this.collection.length === 0 && this.metadata.state.filters.length === 0) {
                filtersList.$el.hide();
            }

            this.deferred.resolve();
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
         * @param {jQuery} $el
         * @param {String} gridName
         */
        init: function (deferred, $el, gridName) {
            var self, onCollectionSet;
            self = {
                deferred: deferred,
                $el: $el,
                gridName: gridName,
                collection: null,
                modules: null
            };

            onCollectionSet = function (collection, $el) {
                if ($el === self.$el) {
                    self.collection = collection;
                    methods.build.call(self);
                }
            };
            mediator.once('datagrid_collection_set_after', onCollectionSet);
            mediator.once('hash_navigation_request:start', function () {
                mediator.off('datagrid_collection_set_after', onCollectionSet);
            });

            methods.initBuilder.call(self);
        }
    };
});
