/*jslint vars: true, nomen: true, browser: true*/
/*jshint browser: true*/
/*global define, require*/
define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oro/tools');
    var mediator = require('oro/mediator');
    var PageableCollection = require('./pageable-collection');
    var Grid = require('./datagrid/grid');
    var GridRouter = require('./datagrid/router');
    var GridViewsView = require('./datagrid/grid-views/view');
    var mapActionModuleName = require('./map-action-module-name');
    var mapCellModuleName = require('./map-cell-module-name');

    var gridSelector = '[data-type="datagrid"]:not([data-rendered])',
        gridGridViewsSelector = '.page-title > .navbar-extra .span9:last',
        collectionOptions = {},

        helpers = {
            cellType: function (type) {
                return type + 'Cell';
            },
            actionType: function (type) {
                return type + 'Action';
            }
        },

        methods = {
            /**
             * Reads data from grid container, collects required modules and runs grid builder
             *
             * @param {Function} initBuilders
             */
            initBuilder: function (initBuilders) {
                var self = this;

                self.metadata = _.extend({
                    columns: [],
                    options: {},
                    state: {},
                    rowActions: {},
                    massActions: {}
                }, self.$el.data('metadata'));

                self.modules = {};

                methods.collectModules.call(self);

                // load all dependencies and build grid
                tools.loadModules(self.modules, function () {
                    methods.buildGrid.call(self);
                    initBuilders();
                    methods.afterBuild.call(self);
                });
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
            buildGrid: function () {
                var options, collection, grid, obj;

                // collection can be stored in the page cache
                mediator.trigger('datagrid_collection_set_before', obj = {});
                if (obj.collection) {
                    collection = obj.collection;
                } else {
                    // otherwise, create collection from metadata
                    options = methods.combineCollectionOptions.call(this);
                    collection = new PageableCollection(this.$el.data('data'), options);
                    collectionOptions = _.extend({}, options);
                }

                // create grid
                options = methods.combineGridOptions.call(this);
                mediator.trigger('datagrid_create_before', options, collection);
                grid = new Grid(_.extend({collection: collection}, options));
                mediator.trigger('datagrid_create_after', grid);
                this.grid = grid;
                this.$el.append(grid.render().$el);
                this.$el.data('datagrid', grid);

                if (options.routerEnabled !== false) {
                    // register router
                    new GridRouter({collection: collection});
                }

                // create grid view
                options = methods.combineGridViewsOptions.call(this);
                $(gridGridViewsSelector).append((new GridViewsView(_.extend({collection: collection}, options))).render().$el);
            },

            /**
             * After build
             */
            afterBuild: function () {
                mediator.trigger('datagrid_collection_set_after', this.grid.collection, this.$el);
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
                    }, this.metadata.state)
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
                    var cellOptionKeys = ['name', 'label', 'renderable', 'editable', 'sortable'],
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
            },

            /**
             * Process metadata and combines options for datagrid views
             *
             * @returns {Object}
             */
            combineGridViewsOptions: function () {
                return this.metadata.gridViews || {};
            }
        };


    /**
     * Process datagirid's metadata and creates datagrid
     *
     * @export orodatagrid/js/datagrid-builder
     * @name   orodatagrid.datagridBuilder
     */
    return function (builders) {
        $(gridSelector).each(function (i, el) {
            var $el = $(el);
            var gridName = (($el.data('metadata') || {}).options || {}).gridName;
            if (!gridName) {
                return;
            }
            $el.attr('data-rendered', true);
            methods.initBuilder.call({ $el: $el }, function () {
                _.each(builders, function (builder) {
                    if (!_.has(builder, 'init') || !$.isFunction(builder.init)) {
                        throw new TypeError('Builder does not have init method');
                    }
                    builder.init($el, gridName);
                });
            });
        }).end();
    };
});
