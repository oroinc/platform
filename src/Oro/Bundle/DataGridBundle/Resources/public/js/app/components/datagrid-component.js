/*jslint vars: true, nomen: true, browser: true*/
/*jshint browser: true*/
/*global define, require, window*/
define(function (require) {
    'use strict';

    var DataGridComponent, helpers, document,
        $ = require('jquery'),
        _ = require('underscore'),
        tools = require('oroui/js/tools'),
        mediator = require('oroui/js/mediator'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        PageableCollection = require('orodatagrid/js/pageable-collection'),
        Grid = require('orodatagrid/js/datagrid/grid'),
        mapActionModuleName = require('orodatagrid/js/map-action-module-name'),
        mapCellModuleName = require('orodatagrid/js/map-cell-module-name'),
        gridContentManager = require('orodatagrid/js/content-manager');

    document = window.document;

    helpers = {
        cellType: function (type) {
            return type + 'Cell';
        },
        actionType: function (type) {
            return type + 'Action';
        }
    };

    /**
     * Runs passed builder
     *
     * @param {jQuery.Deferred} built
     * @param {Object} options
     * @param {Object} builder
     */
    function runBuilder(built, options, builder) {
        if (!_.has(builder, 'init') || !$.isFunction(builder.init)) {
            built.resolve();
            throw new TypeError('Builder does not have init method');
        }
        _.defer(_.bind(builder.init, builder), built, options);
    }

    DataGridComponent = BaseComponent.extend({
        initialize: function (options) {
            var promises, self;

            self = this;
            this._deferredInit();
            this.built = $.Deferred();

            options = options || {};
            this.processOptions(options);
            this.initDataGrid(options);

            promises = [this.built.promise()];

            // run related builders
            _.each(options.builders, function (module) {
                var built = $.Deferred();
                promises.push(built.promise());
                require([module], _.partial(runBuilder, built, options));
            });

            $.when.apply($, promises).always(function () {
                $(options.el).html(options.$el.children());
                self.subComponents = _.compact(arguments);
                self.grid.reflow();
                self._resolveDeferredInit();
            });
        },

        /**
         * Extends passed options
         *
         * @param options
         */
        processOptions: function (options) {
            options.$el = $(document.createDocumentFragment());
            options.gridName = options.gridName || options.metadata.options.gridName;
            options.builders = options.builders || [];
            options.builders.push('orodatagrid/js/grid-views-builder');
            options.gridPromise = this.built.promise();
        },

        /**
         * Collects required modules and runs grid builder
         *
         * @param {Object} options
         */
        initDataGrid: function (options) {
            this.$el = options.$el;
            this.gridName = options.gridName;
            this.data = options.data;
            this.metadata = _.defaults(options.metadata, {
                columns: [],
                options: {},
                state: {},
                rowActions: {},
                massActions: {},
            });
            this.modules = {};

            this.collectModules();

            // load all dependencies and build grid
            tools.loadModules(this.modules, this.build, this);
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

            collectionName = this.gridName;
            collection = gridContentManager.get(collectionName);
            if (!collection) {
                // otherwise, create collection from metadata
                collectionOptions = this.combineCollectionOptions();
                collection = new PageableCollection(this.data, collectionOptions);
            }

            // create grid
            options = this.combineGridOptions();
            mediator.trigger('datagrid_create_before', options, collection);
            grid = new Grid(_.extend({collection: collection}, options));
            this.grid = grid;
            this.$el.append(grid.render().$el);
            mediator.trigger('datagrid:rendered');

            if (options.routerEnabled !== false) {
                // trace collection changes
                gridContentManager.trace(collection);
            }

            this.built.resolve(grid);
        },

        /**
         * Process metadata and combines options for collection
         *
         * @returns {Object}
         */
        combineCollectionOptions: function () {
            return _.extend({
                inputName: this.gridName,
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
                name: this.gridName,
                columns: columns,
                rowActions: rowActions,
                massActions: massActions,
                toolbarOptions: metadata.options.toolbarOptions || {},
                multipleSorting: metadata.options.multipleSorting || false,
                entityHint: metadata.options.entityHint,
                exportOptions: metadata.options.export || {},
                routerEnabled: _.isUndefined(metadata.options.routerEnabled) ? true : metadata.options.routerEnabled,
                multiSelectRowEnabled: metadata.options.multiSelectRowEnabled || !_.isEmpty(massActions),
                metadata: this.metadata,
                enableFullScreenLayout: this.metadata.enableFullScreenLayout
            };
        },
        dispose: function () {
            // disposes registered sub-components
            if (this.subComponents) {
                _.each(this.subComponents, function (component) {
                    if (component && typeof component.dispose === 'function') {
                        component.dispose();
                    }
                });
                delete this.subComponents;
            }
            DataGridComponent.__super__.dispose.call(this);
        }
    });

    return DataGridComponent;
});
