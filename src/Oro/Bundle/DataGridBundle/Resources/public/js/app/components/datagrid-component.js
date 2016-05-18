define(function(require) {
    'use strict';

    var DataGridComponent;
    var helpers;
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var mediator = require('oroui/js/mediator');
    var Backbone = require('backbone');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var PageableCollection = require('orodatagrid/js/pageable-collection');
    var Grid = require('orodatagrid/js/datagrid/grid');
    var mapActionModuleName = require('orodatagrid/js/map-action-module-name');
    var mapCellModuleName = require('orodatagrid/js/map-cell-module-name');
    var gridContentManager = require('orodatagrid/js/content-manager');
    var FloatingHeaderPlugin = require('orodatagrid/js/app/plugins/grid/floating-header-plugin');
    var FullscreenPlugin = require('orodatagrid/js/app/plugins/grid/fullscreen-plugin');
    var ColumnManagerPlugin = require('orodatagrid/js/app/plugins/grid/column-manager-plugin');
    var ToolbarMassActionPlugin = require('orodatagrid/js/app/plugins/grid/toolbar-mass-action-plugin');
    var MetadataModel = require('orodatagrid/js/datagrid/metadata-model');
    var DataGridThemeOptionsManager = require('orodatagrid/js/datagrid-theme-options-manager');

    helpers = {
        cellType: function(type) {
            return type + 'Cell';
        },
        actionType: function(type) {
            return type + 'Action';
        }
    };

    DataGridComponent = BaseComponent.extend({
        initialize: function(options) {
            if (!options.enableFilters) {
                options.builders = _.reject(options.builders, function(module) {
                    return module === 'orofilter/js/datafilter-builder';
                });
            }
            options.builders.push('orodatagrid/js/inline-editing/builder');

            var self = this;
            this._deferredInit();
            this.built = $.Deferred();

            options = options || {};
            this.fixStates(options);
            this.processOptions(options);

            var optionsProcessedPromises = [];
            var builderImpl = [];

            /**
             * #1. Let builders process datagrid options
             */
            _.each(options.builders, function(module) {
                var built = $.Deferred();
                optionsProcessedPromises.push(built.promise());
                require([module], function(impl) {
                    builderImpl.push(impl);
                    if (!_.has(impl, 'processDatagridOptions')) {
                        built.resolve();
                        return;
                    }
                    impl.processDatagridOptions(built, options);
                });
            });

            $.when.apply($, optionsProcessedPromises).always(_.bind(function() {
                /**
                 * #2. Init datagrid
                 */
                this.initDataGrid(options);

                this.built.then(function() {
                    /**
                     * #3. Run builders
                     */
                    var buildersReadyPromises = [];

                    function throwNoInitMethodError() {
                        throw new TypeError('Builder does not have init method');
                    }
                    // run related builders
                    for (var i = 0; i < builderImpl.length; i++) {
                        var builder = builderImpl[i];
                        var built = $.Deferred();
                        buildersReadyPromises.push(built.promise());

                        if (!_.has(builder, 'init') || !$.isFunction(builder.init)) {
                            built.resolve();
                            _.defer(throwNoInitMethodError);
                            continue;
                        }
                        builder.init(built, options);
                    }

                    $.when.apply($, buildersReadyPromises).always(function() {
                        /**
                         * #4. Done
                         */
                        self.subComponents = _.compact(arguments);
                        self._resolveDeferredInit();
                        self.$componentEl.find('.view-loading').remove();
                        self.$el.show();
                        self.grid.shown = true;
                        self.grid.trigger('shown');
                    });
                });
            }, this));
        },

        /**
         * Extends passed options
         *
         * @param options
         */
        processOptions: function(options) {
            if (typeof options.inputName === 'undefined') {
                throw new Error('Option inputName has to be specified');
            }

            options.metadata.options.toolbarOptions =
                $.extend(true, options.metadata.options.toolbarOptions, options.toolbarOptions);
            options.$el = $(options.el);
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
        initDataGrid: function(options) {
            this.$el = $('<div>');
            this.$componentEl = options.$el;
            this.$componentEl.append(this.$el);
            this.gridName = options.gridName;
            this.inputName = options.inputName;
            this.data = options.data;
            this.metadata = _.defaults(options.metadata, {
                columns: [],
                options: {},
                state: {},
                initialState: {},
                rowActions: {},
                massActions: {}
            });
            this.themeOptions = options.themeOptions || {};
            this.metadataModel = new MetadataModel(this.metadata);
            this.modules = {};

            this.collectModules();

            // load all dependencies and build grid
            tools.loadModules(this.modules, this.build, this);

            this.listenTo(this.metadataModel, 'change:massActions', function(model, massActions) {
                this.grid.massActions.reset(this.buildMassActionsOptions(massActions));
            }, this);
        },

        /**
         * Collects required modules
         */
        collectModules: function() {
            var modules = this.modules;
            var metadata = this.metadata;
            // cells
            _.each(metadata.columns, function(column) {
                var type = column.type;
                modules[helpers.cellType(type)] = mapCellModuleName(type);
            });
            // row actions
            _.each(_.values(metadata.rowActions), function(action) {
                var type = action.frontend_type;
                modules[helpers.actionType(type)] = mapActionModuleName(type);
            });
            // mass actions
            _.each(_.values(metadata.massActions), function(action) {
                var type = action.frontend_type;
                modules[helpers.actionType(type)] = mapActionModuleName(type);
            });
        },

        /**
         * Build grid
         */
        build: function() {
            var collectionModels;
            var collectionOptions;
            var grid;

            var collectionName = this.gridName;
            var collection = gridContentManager.get(collectionName);

            collectionModels = {};
            if (this.data && this.data.data) {
                collectionModels = this.data.data;
            }

            collectionOptions = this.combineCollectionOptions();
            if (this.data && this.data.options) {
                _.extend(collectionOptions, this.data.options);
            }

            if (!collection) {
                // otherwise, create collection from metadata
                collection = new PageableCollection(collectionModels, collectionOptions);
            }

            // create grid
            var options = this.combineGridOptions();
            mediator.trigger('datagrid_create_before', options, collection);

            this.$el.hide();
            options.el = this.$el[0];
            options.themeOptionsConfigurator(Grid, options);
            grid = new Grid(_.extend({collection: collection}, options));
            this.grid = grid;
            grid.render();
            mediator.trigger('datagrid:rendered', grid);

            if (options.routerEnabled !== false) {
                // trace collection changes
                gridContentManager.trace(collection);
            }

            var deferredBuilt = this.built;
            if (grid.deferredRender) {
                grid.deferredRender.then(function() {
                    deferredBuilt.resolve(grid);
                });
            } else {
                deferredBuilt.resolve(grid);
            }
        },

        /**
         * Process metadata and combines options for collection
         *
         * @returns {Object}
         */
        combineCollectionOptions: function() {
            var options = _.extend({
                /*
                 * gridName contains extended information "inputName + scopeName"
                 * (allows to differentiate grid instances)
                 */
                inputName: this.gridName,
                parse: true,
                url: '\/user\/json',
                state: _.extend({
                    filters: {},
                    sorters: {},
                    columns: {}
                }, this.metadata.state),
                initialState: this.metadata.initialState,
                mode: this.metadata.mode || 'server'
            }, this.metadata.options);
            return options;
        },

        /**
         * Process metadata and combines options for datagrid
         *
         * @returns {Object}
         */
        combineGridOptions: function() {
            var columns;
            var rowActions = {};
            var defaultOptions = {
                sortable: false
            };
            var modules = this.modules;
            var metadata = this.metadata;
            var plugins = this.metadata.plugins || [];

            // columns
            columns = _.map(metadata.columns, function(cell) {
                var cellOptionKeys = ['name', 'label', 'renderable', 'editable', 'sortable', 'sortingType', 'align',
                    'order', 'manageable', 'required'];
                var cellOptions = _.extend({}, defaultOptions, _.pick.apply(null, [cell].concat(cellOptionKeys)));
                var extendOptions = _.omit.apply(null, [cell].concat(cellOptionKeys.concat('type')));
                var cellType = modules[helpers.cellType(cell.type)];
                if (!_.isEmpty(extendOptions)) {
                    cellType = cellType.extend(extendOptions);
                }
                cellOptions.cell = cellType;
                return cellOptions;
            });

            // row actions
            _.each(metadata.rowActions, function(options, action) {
                rowActions[action] = modules[helpers.actionType(options.frontend_type)].extend(options);
            });

            // mass actions
            var massActions = this.buildMassActionsOptions(this.metadata.massActions);

            if (!this.themeOptions.headerHide) {
                if (tools.isMobile()) {
                    plugins.push(FloatingHeaderPlugin);
                } else if (this.metadata.enableFullScreenLayout) {
                    plugins.push(FullscreenPlugin);
                }
            }

            if (metadata.options.toolbarOptions.addColumnManager) {
                plugins.push(ColumnManagerPlugin);
            }

            if (this.themeOptions.showMassActionOnToolbar) {
                plugins.push(ToolbarMassActionPlugin);
            }

            return {
                name: this.gridName,
                columns: columns,
                rowActions: rowActions,
                massActions: new Backbone.Collection(massActions),
                toolbarOptions: metadata.options.toolbarOptions || {},
                multipleSorting: metadata.options.multipleSorting || false,
                entityHint: metadata.options.entityHint,
                exportOptions: metadata.options.export || {},
                routerEnabled: _.isUndefined(metadata.options.routerEnabled) ? true : metadata.options.routerEnabled,
                multiSelectRowEnabled: metadata.options.multiSelectRowEnabled || massActions.length,
                metadata: this.metadata,
                metadataModel: this.metadataModel,
                plugins: plugins,
                themeOptionsConfigurator: DataGridThemeOptionsManager.createConfigurator(this.themeOptions)
            };
        },

        /**
         * @param {Object} actions
         * @returns {Array}
         */
        buildMassActionsOptions: function(actions) {
            var modules = this.modules;
            var massActions = [];

            _.each(actions, function(options, action) {
                massActions.push({
                    action: action,
                    module: modules[helpers.actionType(options.frontend_type)].extend(options)
                });
            });

            return massActions;
        },

        fixStates: function(options) {
            if (options.metadata) {
                this.fixState(options.metadata.state);
                this.fixState(options.metadata.initialState);
            }
        },

        fixState: function(state) {
            if (_.isArray(state.filters) && _.isEmpty(state.filters)) {
                state.filters = {};
            }

            if (_.isArray(state.sorters) && _.isEmpty(state.sorters)) {
                state.sorters = {};
            }
        },

        dispose: function() {
            // disposes registered sub-components
            if (this.subComponents) {
                _.each(this.subComponents, function(component) {
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
