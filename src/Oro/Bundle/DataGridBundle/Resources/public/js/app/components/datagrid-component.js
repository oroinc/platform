/*jslint vars: true, nomen: true, browser: true*/
/*jshint browser: true*/
/*global define, require*/
define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var mediator = require('oroui/js/mediator');
    var PageableCollection = require('orodatagrid/js/pageable-collection');
    var Grid = require('orodatagrid/js/datagrid/grid');
    var mapActionModuleName = require('orodatagrid/js/map-action-module-name');
    var mapCellModuleName = require('orodatagrid/js/map-cell-module-name');
    var gridContentManager = require('orodatagrid/js/content-manager');

    var helpers = {
            cellType: function (type) {
                return type + 'Cell';
            },
            actionType: function (type) {
                return type + 'Action';
            }
        },

        gridBuilder = {
            /**
             * Reads data from grid container, collects required modules and runs grid builder
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
                    deferred: deferred,
                    $el: options.$el,
                    gridName: options.gridName,
                    data: options.data,
                    metadata: _.defaults(options.metadata, {
                        columns: [],
                        options: {},
                        state: {},
                        rowActions: {},
                        massActions: {}
                    }),
                    modules: {}
                };

                gridBuilder.collectModules.call(self);

                // load all dependencies and build grid
                tools.loadModules(self.modules, gridBuilder.build, self);
            },

            /**
             * Collects required modules
             */
            collectModules: function () {
                var modules = this.modules,
                    metadata = this.metadata;
                // cells
                _.each(metadata.columns, function (column) {
                    var type = column.type;
                    modules[helpers.cellType(type)] = mapCellModuleName(type);
                });
                // row actions
                _.each(_.values(metadata.rowActions), function (action) {
                    var type = action.frontend_type;
                    modules[helpers.actionType(type)] = mapActionModuleName(type);
                });
                // mass actions
                _.each(_.values(metadata.massActions), function (action) {
                    var type = action.frontend_type;
                    modules[helpers.actionType(type)] = mapActionModuleName(type);
                });
            },

            /**
             * Build grid
             */
            build: function () {
                var options, collectionOptions, collection, collectionName, grid;

                collectionName = this.metadata.options.gridName;
                collection = gridContentManager.get(collectionName);
                if (!collection) {
                    // otherwise, create collection from metadata
                    collectionOptions = gridBuilder.combineCollectionOptions.call(this);
                    collection = new PageableCollection(this.data, collectionOptions);
                }

                // create grid
                options = gridBuilder.combineGridOptions.call(this);
                mediator.trigger('datagrid_create_before', options, collection);
                grid = new Grid(_.extend({collection: collection}, options));
                this.grid = grid;
                this.$el.append(grid.render().$el);
                mediator.trigger('datagrid:rendered');

                if (options.routerEnabled !== false) {
                    // trace collection changes
                    gridContentManager.trace(collection);
                }

                this.deferred.resolve(grid);
            },

            /**
             * Process metadata and combines options for collection
             *
             * @returns {Object}
             */
            combineCollectionOptions: function () {
                return _.extend({
                    inputName: this.metadata.options.gridName,
                    parse: true,
                    url: '\/user\/json',
                    state: _.extend({
                        filters: {},
                        sorters: {}
                    }, this.metadata.state),
                    initialState: this.metadata.initialState || {}
                }, this.metadata.options);
            },

            /**
             * Process metadata and combines options for datagrid
             *
             * @returns {Object}
             */
            combineGridOptions: function () {
                var columns,
                    rowActions = {},
                    massActions = {},
                    defaultOptions = {
                        sortable: false
                    },
                    modules = this.modules,
                    metadata = this.metadata;

                // columns
                columns = _.map(metadata.columns, function (cell) {
                    var cellOptionKeys = ['name', 'label', 'renderable', 'editable', 'sortable', 'align'],
                        cellOptions = _.extend({}, defaultOptions, _.pick.apply(null, [cell].concat(cellOptionKeys))),
                        extendOptions = _.omit.apply(null, [cell].concat(cellOptionKeys.concat('type'))),
                        cellType = modules[helpers.cellType(cell.type)];
                    if (!_.isEmpty(extendOptions)) {
                        cellType = cellType.extend(extendOptions);
                    }
                    cellOptions.cell = cellType;
                    return cellOptions;
                });

                // row actions
                _.each(metadata.rowActions, function (options, action) {
                    rowActions[action] = modules[helpers.actionType(options.frontend_type)].extend(options);
                });

                // mass actions
                _.each(metadata.massActions, function (options, action) {
                    massActions[action] = modules[helpers.actionType(options.frontend_type)].extend(options);
                });

                return {
                    name: metadata.options.gridName,
                    columns: columns,
                    rowActions: rowActions,
                    massActions: massActions,
                    toolbarOptions: metadata.options.toolbarOptions || {},
                    multipleSorting: metadata.options.multipleSorting || false,
                    entityHint: metadata.options.entityHint,
                    exportOptions: metadata.options.export || {},
                    routerEnabled: _.isUndefined(metadata.options.routerEnabled) ? true : metadata.options.routerEnabled
                };
            }
        };

    /**
     * Runs passed builder
     *
     * @param {jQuery.Deferred} deferred
     * @param {Object} options
     * @param {Object} builder
     */
    function runBuilder(deferred, options, builder) {
        if (!_.has(builder, 'init') || !$.isFunction(builder.init)) {
            deferred.reject();
            throw new TypeError('Builder does not have init method');
        }
        _.defer(_.bind(builder.init, builder), deferred, options);
    }

    /**
     * Process datagrid's metadata and creates datagrid
     *
     * @export orodatagrid/js/datagrid-builder
     * @name   orodatagrid.datagridBuilder
     *
     * @param {array} builders
     * @param {string} selector
     */
    return function (options) {
        var deferred, promises;

        options.$el = $(document.createDocumentFragment());
        options.gridName = options.metadata.options.gridName;

        // run grid builders
        deferred = $.Deferred();
        options.gridPromise = deferred.promise();
        promises = [options.gridPromise];
        runBuilder(deferred, options, gridBuilder);

        options.builders = options.builders || [];
        options.builders.push('orodatagrid/js/grid-views-builder');

        // run other builders
        _.each(options.builders, function (module) {
            var deferred = $.Deferred();
            promises.push(deferred.promise());
            require([module], _.partial(runBuilder, deferred, options));
        });

        $.when.apply($, promises).always(function () {
            $(options.el).html(options.$el.children());
        });

        return promises;
    };
});
