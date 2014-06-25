/*jslint vars: true, nomen: true, browser: true*/
/*jshint browser: true*/
/*global define, require*/
define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var mediator = require('oroui/js/mediator');
    var PageableCollection = require('./pageable-collection');
    var Grid = require('./datagrid/grid');
    var GridViewsView = require('./datagrid/grid-views/view');
    var mapActionModuleName = require('./map-action-module-name');
    var mapCellModuleName = require('./map-cell-module-name');
    var gridContentManager = require('./content-manager');

    var gridSelector = '[data-type="datagrid"]:not([data-rendered])',
        gridGridViewsSelector = '.page-title > .navbar-extra .span9:last',

        helpers = {
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
             * @param {jQuery} $el
             * @param {String} gridName
             */
            init: function (deferred, $el, gridName) {
                var self = {
                    deferred: deferred,
                    $el: $el,
                    gridName: gridName,
                    modules: {}
                };

                self.metadata = _.extend({
                    columns: [],
                    options: {},
                    state: {},
                    rowActions: {},
                    massActions: {}
                }, self.$el.data('metadata'));

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
                    collection = new PageableCollection(this.$el.data('data'), collectionOptions);
                }

                mediator.trigger('datagrid_collection_set_after', collection, this.$el);

                // create grid
                options = gridBuilder.combineGridOptions.call(this);
                mediator.trigger('datagrid_create_before', options, collection);
                grid = new Grid(_.extend({collection: collection}, options));
                mediator.trigger('datagrid_create_after', grid);
                this.grid = grid;
                this.$el.append(grid.render().$el);
                this.$el.data('datagrid', grid);
                mediator.trigger('datagrid:rendered');

                if (options.routerEnabled !== false) {
                    // trace collection changes
                    gridContentManager.trace(collection);
                }

                // create grid view
                options = gridBuilder.combineGridViewsOptions.call(this);
                $(gridGridViewsSelector).append((new GridViewsView(_.extend({collection: collection}, options))).render().$el);

                this.deferred.resolve();
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
     * Process datagrid's metadata and creates datagrid
     *
     * @export orodatagrid/js/datagrid-builder
     * @name   orodatagrid.datagridBuilder
     *
     * @param {array} builders
     * @param {string} selector
     */
    return function (builders, selector) {
        var $el = $(selector).filter(gridSelector);

        builders.push(gridBuilder);

        $el.each(function (i, el) {
            var $el, gridName, fragment, promises;

            $el = $(el);
            gridName = (($el.data('metadata') || {}).options || {}).gridName;
            if (!gridName) {
                return;
            }

            var $placeHolder = $('<div/>');
            $el.before($placeHolder);
            fragment = document.createDocumentFragment();
            fragment.appendChild($el[0]);
            promises = [];

            _.each(builders, function (builder) {
                var deferred;
                if (!_.has(builder, 'init') || !$.isFunction(builder.init)) {
                    throw new TypeError('Builder does not have init method');
                }
                deferred = $.Deferred();
                setTimeout(function () {
                    builder.init(deferred, $el, gridName);
                }, 0);
                promises.push(deferred.promise());
            });

            $.when.apply($, promises).done(function () {
                $el.attr('data-rendered', true);
                $placeHolder.replaceWith($el);
            });
        });
    };
});
