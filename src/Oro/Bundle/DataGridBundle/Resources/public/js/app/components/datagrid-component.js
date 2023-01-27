define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const loadModules = require('oroui/js/app/services/load-modules');
    const mediator = require('oroui/js/mediator');
    const Backbone = require('backbone');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const PageableCollection = require('orodatagrid/js/pageable-collection');
    const GridView = require('orodatagrid/js/datagrid/grid');
    const mapActionModuleName = require('orodatagrid/js/map-action-module-name');
    const mapCellModuleName = require('orodatagrid/js/map-cell-module-name');
    const PluginManager = require('oroui/js/app/plugins/plugin-manager');
    const MetadataModel = require('orodatagrid/js/datagrid/metadata-model');
    const DataGridThemeOptionsManager = require('orodatagrid/js/datagrid-theme-options-manager');

    const pluginModules = {
        FloatingHeaderPlugin: 'orodatagrid/js/app/plugins/grid/floating-header-plugin',
        FullscreenPlugin: 'orodatagrid/js/app/plugins/grid/fullscreen-plugin',
        DatagridSettingsPlugin: 'orodatagrid/js/app/plugins/grid/datagrid-settings-plugin',
        ToolbarMassActionPlugin: 'orodatagrid/js/app/plugins/grid/toolbar-mass-action-plugin',
        StickedScrollbarPlugin: 'orodatagrid/js/app/plugins/grid/sticked-scrollbar-plugin',
        AccessibilityPlugin: 'orodatagrid/js/app/plugins/grid/accessibility-plugin'
    };

    const helpers = {
        cellType: function(type) {
            return type + 'Cell';
        },
        actionType: function(type) {
            return type + 'Action';
        },
        customType: function(type) {
            return type + 'Custom';
        }
    };

    const DataGridComponent = BaseComponent.extend({
        currentAppearanceKey: 'grid',

        currentAppearanceId: void 0,

        changeAppearanceEnabled: false,

        /**
         * @inheritdoc
         */
        constructor: function DataGridComponent(options) {
            DataGridComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.pluginManager = new PluginManager(this);
            this.changeAppearanceEnabled = 'appearanceData' in options.metadata.state;
            if (!options.enableFilters ||
                ('filters' in options.metadata && !options.metadata.filters.length)
            ) {
                options.builders = _.reject(options.builders, function(module) {
                    return module === 'orofilter/js/datafilter-builder';
                });
            }

            if (options.metadata.rowLinkEnabled) {
                options.builders.push('orodatagrid/js/cell-links/builder');
            }

            options.builders.push('orodatagrid/js/inline-editing/builder');
            options.builders.push('orodatagrid/js/appearance/builder');

            const self = this;
            this._deferredInit();
            this.built = $.Deferred();

            options = options || {};
            this.fixStates(options);
            this.processOptions(options);

            const optionsProcessedPromises = [];
            const builderImpl = [];

            /**
             * #1. Let builders process datagrid options
             */
            _.each(options.builders, function(module) {
                const built = $.Deferred();
                optionsProcessedPromises.push(built.promise());
                loadModules(module, function(impl) {
                    builderImpl.push(impl);
                    if (!_.has(impl, 'processDatagridOptions')) {
                        built.resolve();
                        return;
                    }
                    impl.processDatagridOptions(built, options);
                });
            });

            $.when(...optionsProcessedPromises).always(() => {
                /**
                 * #2. Init datagrid
                 */
                this.initDataGrid(options);

                this.built.then(function() {
                    /**
                     * #3. Run builders
                     */
                    const buildersReadyPromises = [];

                    function throwNoInitMethodError() {
                        throw new TypeError('Builder does not have init method');
                    }
                    // run related builders
                    for (let i = 0; i < builderImpl.length; i++) {
                        const builder = builderImpl[i];
                        const built = $.Deferred();
                        buildersReadyPromises.push(built.promise());

                        if (!_.has(builder, 'init') || typeof builder.init !== 'function') {
                            built.resolve();
                            _.defer(throwNoInitMethodError);
                            continue;
                        }
                        builder.init(built, options);
                    }

                    $.when(...buildersReadyPromises).always(function(...components) {
                        /**
                         * #4. Done
                         */
                        if (self.changeAppearanceEnabled) {
                            self.selectAppearanceById(options.metadata.state.appearanceData.id);
                        }
                        self.subComponents = _.compact(components);
                        self._resolveDeferredInit();
                        self.$componentEl.find('.view-loading').remove();
                        self.$el.show();
                        self.grid.shown = true;
                        self.grid.trigger('shown');
                    });
                });
            });
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
            options.$el = $(options._sourceElement);
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
            this.$componentEl = options.$el;

            this.insertDataGrid(options);

            this.gridName = options.gridName;
            this.inputName = options.inputName;
            this.data = options.data;

            this.themeOptions = options.themeOptions || {};

            const customModules = _.extend(options.metadata.customModules || {}, this.themeOptions.customModules);

            this.metadata = _.defaults(options.metadata, {
                columns: [],
                options: {},
                state: {},
                initialState: {},
                rowActions: {},
                massActions: {},
                extraActions: {},
                customModules: customModules
            });

            this.metadataModel = new MetadataModel(this.metadata);

            this.modules = {};

            this.collectModules();

            // load all dependencies and build grid
            loadModules(this.modules, this.build, this);

            this.listenTo(this.metadataModel, 'change:massActions', (model, massActions) => {
                this.grid.massActions.reset(this.buildActionsOptions(massActions));
            });
            this.listenTo(this.metadataModel, 'change:extraActions', (model, extraActions) => {
                this.grid.extraActions.reset(this.buildActionsOptions(extraActions));
            });
        },

        /**
         * Insert Grid to DOM
         * @param {Object} options
         */
        insertDataGrid: function(options) {
            this.$el = $('<div data-layout="separate">');
            this.$componentEl.append(this.$el);
        },

        /**
         * Collects required modules
         */
        collectModules: function() {
            const modules = this.modules;
            const metadata = this.metadata;
            // cells
            _.each(metadata.columns, function(column) {
                const type = column.type;
                modules[helpers.cellType(type)] = mapCellModuleName(type);
            });
            // actions (row, mass and extra)
            [
                ...Object.values(metadata.rowActions),
                ...Object.values(metadata.massActions),
                ...Object.values(metadata.extraActions)
            ].forEach(({frontend_type: type}) => {
                modules[helpers.actionType(type)] = mapActionModuleName(type);
            });

            // Collect custom modules for datagrid or child components  if there are present.
            _.each(metadata.customModules, function(module, type) {
                if (_.isString(module)) {
                    modules[helpers.customType(type)] = module;
                }
            });

            // preload all action confirmation modules
            _.each(this.data.data, function(model) {
                _.each(model.action_configuration, function(config) {
                    const module = config.confirmation && config.confirmation.component;
                    if (module) {
                        // the key does not matter, the module just added to list to have it preloaded
                        modules[module] = module;
                    }
                });
            });

            // load pluginsModules
            if (!this.themeOptions.headerHide) {
                if (this.metadata.enableFloatingHeaderPlugin) {
                    modules.FloatingHeaderPlugin = pluginModules.FloatingHeaderPlugin;
                } else if (this.metadata.enableFullScreenLayout) {
                    modules.FullscreenPlugin = pluginModules.FullscreenPlugin;
                }
            }

            if (metadata.options.toolbarOptions.addDatagridSettingsManager) {
                modules.DatagridSettingsPlugin = pluginModules.DatagridSettingsPlugin;
            }

            if (this.themeOptions.showMassActionOnToolbar) {
                modules.ToolbarMassActionPlugin = pluginModules.ToolbarMassActionPlugin;
            }

            if (!this.themeOptions.disableStickedScrollbar) {
                if (this.metadata.responsiveGrids && this.metadata.responsiveGrids.enable) {
                    modules.StickedScrollbarPlugin = pluginModules.StickedScrollbarPlugin;
                } else if (tools.isMobile() || !this.metadata.enableFullScreenLayout) {
                    modules.StickedScrollbarPlugin = pluginModules.StickedScrollbarPlugin;
                }
            }

            if (this.themeOptions.enabledAccessibilityPlugin) {
                modules.AccessibilityPlugin = pluginModules.AccessibilityPlugin;
            }
        },

        /**
         * Build grid
         */
        build: function(modules) {
            let collectionModels;

            const Grid = modules.GridView || GridView;
            const Collection = modules.PageableCollection || PageableCollection;

            collectionModels = {};
            if (this.data && this.data.data) {
                collectionModels = this.data.data;
            }

            const collectionOptions = this.combineCollectionOptions(modules);
            if (this.data && this.data.options) {
                _.extend(collectionOptions, this.data.options);
            }

            const collection = new Collection(collectionModels, collectionOptions);

            // create grid
            const options = this.combineGridOptions();
            mediator.trigger('datagrid_create_before', options, collection);

            options.el = this.$el[0];

            options.themeOptionsConfigurator(Grid, options);
            const grid = new Grid(_.extend({collection: collection}, options));

            this.grid = grid;
            grid.render();
            if (this.changeAppearanceEnabled) {
                grid.on('changeAppearance', this.onChangeAppearance.bind(this));
                collection.on('updateState', () => {
                    if (this.currentAppearanceKey !== collection.state.appearanceType ||
                        this.currentAppearanceId !== collection.state.appearanceData.id) {
                        this.selectAppearanceById(collection.state.appearanceData.id);
                    }
                });
            }
            mediator.trigger('datagrid:rendered', grid);

            this.collection = collection;

            if (options.routerEnabled !== false) {
                this.traceChanges();
            }

            const deferredBuilt = this.built;
            if (grid.deferredRender) {
                grid.deferredRender.then(function() {
                    deferredBuilt.resolve(grid);
                });
            } else {
                deferredBuilt.resolve(grid);
            }
        },

        onChangeAppearance: function(key, options) {
            this.selectAppearance(key, options);
        },

        selectAppearanceById: function(id) {
            const appearanceOptions = _.find(this.metadata.options.appearances, function(item) {
                return item.id === id || (id === '' && item.id === void 0 /* non specified on default view */);
            });
            if (!appearanceOptions) {
                const error = new Error('Could not find appearance `' + id + '`');
                setTimeout(function() {
                    throw error;
                }, 0);
                return;
            }
            this.selectAppearance(appearanceOptions.type, appearanceOptions);
        },

        selectAppearance: function(key, options) {
            if (this.currentAppearanceKey === key &&
                this.currentAppearanceId === options.id) {
                return;
            }

            this.currentAppearanceKey = key;
            this.currentAppearanceId = options.id;

            if (this.lastAppearancePlugin) {
                this.pluginManager.remove(this.lastAppearancePlugin);
                delete this.lastAppearancePlugin;
            }
            this.grid.trigger('appearanceChanged', key, options);
            if (key === 'grid') {
                // grid doesn't need any modifications
                return;
            }
            const Plugin = options.plugin;
            if (!Plugin) {
                throw new Error('Could not find plugin for appearance key `' + key + '`');
            }
            this.lastAppearancePlugin = Plugin;
            this.pluginManager.create(Plugin, options || {});
            this.pluginManager.enable(Plugin);
        },

        /**
         * Process metadata and combines options for collection
         *
         * @returns {Object}
         */
        combineCollectionOptions: function(modules) {
            const options = _.extend({
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
                mode: this.metadata.mode || 'server',
                modules: modules
            }, this.metadata.options);
            return options;
        },

        /**
         * Process metadata and combines options for datagrid
         *
         * @returns {Object}
         */
        combineGridOptions: function() {
            const rowActions = {};
            const defaultOptions = {
                sortable: false
            };
            const modules = this.modules;
            const metadata = this.metadata;
            const plugins = this.metadata.plugins || [];

            // columns
            const columns = _.map(metadata.columns, function(cell) {
                const cellOptionKeys = ['name', 'label', 'renderable', 'editable', 'sortable', 'sortingType', 'align',
                    'order', 'manageable', 'required', 'shortenableLabel', 'cellClassName', 'notMarkAsBlank',
                    'long_value_threshold', 'editor'];
                const cellOptions = _.extend({}, defaultOptions, _.pick.apply(null, [cell].concat(cellOptionKeys)));
                const extendOptions = _.omit.apply(null, [cell].concat(cellOptionKeys.concat('type')));
                let cellType = modules[helpers.cellType(cell.type)];
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
            const massActions = this.buildActionsOptions(this.metadata.massActions);
            // extra actions
            const extraActions = this.buildActionsOptions(this.metadata.extraActions);

            Object.values(_.pick(modules, [
                'FloatingHeaderPlugin',
                'FullscreenPlugin',
                'DatagridSettingsPlugin',
                'ToolbarMassActionPlugin',
                'AccessibilityPlugin'
            ])).forEach(plugin => plugins.push(plugin));

            if (modules.StickedScrollbarPlugin) {
                if (this.metadata.responsiveGrids && this.metadata.responsiveGrids.enable) {
                    plugins.push({
                        constructor: modules.StickedScrollbarPlugin,
                        options: {
                            viewport: this.metadata.responsiveGrids.viewport || 'all'
                        }
                    });
                } else {
                    plugins.push(modules.StickedScrollbarPlugin);
                }
            }

            const appearances = metadata.options.appearances || [];
            switch (appearances.length) {
                case 0:
                    break;
                case 1:
                    break;
                default:
                    metadata.options.toolbarOptions.addAppearanceSwitcher = true;
                    metadata.options.toolbarOptions.availableAppearances = appearances.map(function(item) {
                        return {
                            key: item.type,
                            id: item.id || 'by_type',
                            label: item.label,
                            className: 'btn',
                            iconClassName: item.icon,
                            options: item
                        };
                    });
            }

            return {
                name: this.gridName,
                columns,
                rowActions,
                massActions: new Backbone.Collection(massActions),
                extraActions: new Backbone.Collection(extraActions),
                toolbarOptions: metadata.options.toolbarOptions || {},
                multipleSorting: metadata.options.multipleSorting || false,
                entityHint: metadata.options.entityHint,
                noDataMessages: metadata.options.noDataMessages || {},
                exportOptions: metadata.options.export || {},
                routerEnabled: _.isUndefined(metadata.options.routerEnabled) ? true : metadata.options.routerEnabled,
                multiSelectRowEnabled: metadata.options.multiSelectRowEnabled || massActions.length,
                rowClickAction: metadata.options.rowClickAction || false,
                metadata: this.metadata,
                metadataModel: this.metadataModel,
                plugins,
                themeOptionsConfigurator: DataGridThemeOptionsManager.createConfigurator(this.themeOptions)
            };
        },

        /**
         * @param {Object} actionsOptions
         * @returns {Array<{action:string, module: Function}>}
         */
        buildActionsOptions: function(actionsOptions) {
            const actions = [];

            for (const [action, options] of Object.entries(actionsOptions)) {
                const type = helpers.actionType(options.frontend_type);
                if (type in this.modules) {
                    actions.push({
                        action,
                        module: this.modules[type].extend(options)
                    });
                }
            }

            return actions;
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
        },

        /**
         * Enables tracing of collection changes to reflect datagrid state in URL.
         */
        traceChanges: function() {
            const self = this;

            this.updateStateInUrl();

            this.listenTo(this.collection, {
                updateState: this.updateStateInUrl,
                reset: this.updateStateInUrl
            });

            mediator.once('page:beforeChange', function() {
                self.stopListening(self.collection);
            });
        },

        /**
         * Reflects datagrid state in URL.
         */
        updateStateInUrl: function() {
            const key = this.collection.stateHashKey();
            const hash = this.collection.stateHashValue(true);

            mediator.execute('changeUrlParam', key, hash);
        }
    });

    return DataGridComponent;
});
