define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const Backbone = require('backbone');
    const Backgrid = require('backgrid');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const GridHeader = require('./header');
    const GridBody = require('./body');
    const GridFooter = require('./footer');
    const GridColumns = require('./columns');
    const Toolbar = require('./toolbar');
    const SelectState = require('./select-state-model');
    const ActionColumn = require('./column/action-column');
    const SelectRowCell = require('oro/datagrid/cell/select-row-cell');
    const SelectAllHeaderCell = require('./header-cell/select-all-header-cell');
    const RefreshCollectionAction = require('oro/datagrid/action/refresh-collection-action');
    const ResetCollectionAction = require('oro/datagrid/action/reset-collection-action');
    const SelectDataAppearanceAction = require('oro/datagrid/action/select-data-appearance-action');
    const ExportAction = require('oro/datagrid/action/export-action');
    const PluginManager = require('oroui/js/app/plugins/plugin-manager');
    const scrollHelper = require('oroui/js/tools/scroll-helper');
    const PageableCollection = require('../pageable-collection');
    const GridRowsCounter = require('./grid-rows-counter').default;
    const util = require('./util');
    const tools = require('oroui/js/tools');

    /**
     * Basic grid class.
     *
     * Triggers events:
     *  - "rowClicked" when row of grid body is clicked
     *
     * @export  orodatagrid/js/datagrid/grid
     * @class   orodatagrid.datagrid.Grid
     * @extends Backgrid.Grid
     */
    const Grid = Backgrid.Grid.extend({
        /** @property {String} */
        name: 'datagrid',

        /** @property {String} */
        tagName: 'div',

        attributes: {
            'data-layout': 'separate'
        },

        /** @property {int} */
        requestsCount: 0,

        /** @property {String} */
        className: 'oro-datagrid',

        /** @property */
        noDataTemplate: require('tpl-loader!orodatagrid/templates/datagrid/no-data.html'),

        noSearchResultsTemplate: require('tpl-loader!orodatagrid/templates/datagrid/no-search-results.html'),

        /** @property {Object} */
        noDataTranslations: {
            entityHint: 'oro.datagrid.entityHint',
            noColumns: 'oro.datagrid.no.columns',
            noEntities: 'oro.datagrid.no.entities',
            noResults: 'oro.datagrid.no.results',
            noResultsTitle: 'oro.datagrid.no.results_title'
        },

        /** @property {Object} */
        selectors: {
            grid: '.grid-main-container',
            toolbar: '[data-grid-toolbar]',
            toolbars: {
                top: '[data-grid-toolbar=top]',
                bottom: '[data-grid-toolbar=bottom]'
            },
            noDataBlock: '.no-data',
            filterBox: '.filter-box',
            loadingMaskContainer: '.other-scroll-container',
            floatTheadContainer: '.floatThead-container'
        },

        /** @property {orodatagrid.datagrid.Header} */
        header: GridHeader,

        /** @property {orodatagrid.datagrid.Body} */
        body: GridBody,

        /** @property {orodatagrid.datagrid.Footer} */
        footer: GridFooter,

        /** @property {orodatagrid.datagrid.Toolbar} */
        toolbar: Toolbar,

        /** @property {Object} */
        toolbars: {},

        /** @property {orodatagrid.datagrid.MetadataModel} */
        metadataModel: null,

        /** @property {LoadingMaskView|null} */
        loadingMask: null,

        /** @property {orodatagrid.datagrid.column.ActionColumn} */
        actionsColumn: ActionColumn,

        selectRowCell: SelectRowCell,

        selectAllHeaderCell: SelectAllHeaderCell,

        /** @property true when no one column configured to be shown in th grid */
        noColumnsFlag: false,

        selectState: null,

        /**
         * Generates default properties
         *
         * @returns {Object}
         * @protected
         */
        _defaults() {
            return {
                rowClickActionClass: 'row-click-action',
                rowClassName: '',
                toolbarOptions: {
                    addResetAction: true,
                    addRefreshAction: true,
                    addDatagridSettingsManager: true,
                    addSorting: false,
                    datagridSettings: {
                        addSorting: true
                    },
                    placement: {
                        top: true,
                        bottom: false
                    }
                },
                actionOptions: {
                    refreshAction: {
                        launcherOptions: {
                            label: __('oro_datagrid.action.refresh'),
                            ariaLabel: __('oro_datagrid.action.refresh.aria_label'),
                            className: 'btn refresh-action',
                            iconClassName: 'fa-repeat',
                            launcherMode: 'icon-only'
                        }
                    },
                    resetAction: {
                        launcherOptions: {
                            label: __('oro_datagrid.action.reset'),
                            ariaLabel: __('oro_datagrid.action.reset.aria_label'),
                            className: 'btn reset-action',
                            iconClassName: 'fa-refresh',
                            launcherMode: 'icon-only'
                        }
                    }
                },
                rowClickAction: undefined,
                multipleSorting: true,
                rowActions: [],
                massActions: new Backbone.Collection(),
                extraActions: new Backbone.Collection(),
                enableFullScreenLayout: false,
                scopeDelimiter: ':'
            };
        },

        /**
         * Column indexing starts from this valus in case when 'order' is not specified in column config.
         * This start index required to display new columns at end of already sorted columns set
         */
        DEFAULT_COLUMN_START_INDEX: 1000,

        /** @property */
        template: require('tpl-loader!orodatagrid/templates/datagrid/grid.html'),

        themeOptions: {
            optionPrefix: 'grid'
        },

        /**
         * @inheritdoc
         */
        constructor: function Grid(options) {
            this.defaults = this._defaults();
            Grid.__super__.constructor.call(this, options);
        },

        /**
         * Initialize grid
         *
         * @param {Object} options
         * @param {Backbone.Collection} options.collection
         * @param {(Backbone.Collection|Array)} options.columns
         * @param {String} [options.rowClickActionClass] CSS class for row with click action
         * @param {String} [options.rowClassName] CSS class for row
         * @param {Object} [options.toolbarOptions] Options for toolbar
         * @param {Object} [options.exportOptions] Options for export
         * @param {Object} [options.extraActions] Options for extra actions
         * @param {Array<oro.datagrid.action.AbstractAction>} [options.rowActions] Array of row actions prototypes
         * @param {Backbone.Collection<oro.datagrid.action.AbstractAction>} [options.massActions] Collection of mass actions prototypes
         * @param {Boolean} [options.multiSelectRowEnabled] Option for enabling multi select row
         * @param {oro.datagrid.action.AbstractAction} [options.rowClickAction] Prototype for
         *  action that handles row click
         * @throws {TypeError} If mandatory options are undefined
         */
        initialize: function(options) {
            const opts = options || {};
            this.pluginManager = new PluginManager(this);
            if (options.plugins) {
                for (let i = 0; i < options.plugins.length; i++) {
                    const plugin = options.plugins[i];
                    if (_.isFunction(plugin)) {
                        this.pluginManager.enable(plugin);
                    } else {
                        this.pluginManager.create(plugin.constructor, plugin.options);
                        this.pluginManager.enable(plugin.constructor);
                    }
                }
            }

            this.trigger('beforeParseOptions', options);
            if (this.className) {
                this.$el.addClass(_.result(this, 'className'));
            }

            this._validateOptions(opts);

            this._initProperties(opts);

            // use columns collection as event bus since there is no alternatives
            if (this.themeOptionsConfigurator) {
                this.listenTo(this.columns, 'configureInitializeOptions', this.themeOptionsConfigurator);
            }

            this.filteredColumns = util.createFilteredColumnCollection(this.columns);

            options.filteredColumns = this.filteredColumns;

            this.trigger('beforeBackgridInitialize');
            this.backgridInitialize(options);
            this.trigger('afterBackgridInitialize');

            // Listen and proxy events
            this._listenToCollectionEvents();
            this._listenToContentEvents();
            this._listenToCommands();
        },

        /**
         * @param {Object} opts
         * @private
         */
        _validateOptions: function(opts) {
            if (!opts.collection) {
                throw new TypeError('"collection" is required');
            }
            if (!opts.columns) {
                throw new TypeError('"columns" is required');
            }
            if (!opts.metadataModel) {
                throw new TypeError('"metadataModel" is required');
            }
        },

        /**
         * Init properties values based on options and defaults
         *
         * @param {Object} opts
         * @private
         */
        _initProperties: function(opts) {
            this.collection = opts.collection;

            if (opts.columns.length === 0) {
                this.noColumnsFlag = true;
            }

            _.extend(this, tools.deepClone(this.defaults), opts);
            this._initToolbars(opts);
            this._initActions(opts);
            this.exportOptions = {};
            _.extend(this.exportOptions, opts.exportOptions);

            this.collection.multipleSorting = this.multipleSorting;

            this._initRowActions();

            if (this.rowClickAction) {
                // This option property is used in orodatagrid.datagrid.Body
                opts.rowClassName = this.rowClickActionClass + ' ' + this.rowClassName;
            }

            this._initColumns(opts);
        },

        /**
         * Create this function instead of original Grid.__super__.initialize to customize options for subviews
         * @param options
         */
        backgridInitialize: function(options) {
            const gridRowsCounter = this.gridRowsCounter = new GridRowsCounter(this);
            const filteredOptions = Object.assign({gridRowsCounter}, _.omit(
                options,
                ['el', 'id', 'attributes', 'className', 'tagName', 'events', 'themeOptions']
            ));

            this.header = options.header || this.header;
            const headerOptions = Object.assign({}, filteredOptions);
            this.columns.trigger('configureInitializeOptions', this.header, headerOptions);
            if (headerOptions.themeOptions.hide) {
                this.header = null;
            }

            this.body = options.body || this.body;
            const bodyOptions = Object.assign({}, filteredOptions);
            this.columns.trigger('configureInitializeOptions', this.body, bodyOptions);

            this.footer = options.footer || this.footer;
            const footerOptions = Object.assign({}, filteredOptions);
            this.columns.trigger('configureInitializeOptions', this.footer, footerOptions);
            if (footerOptions.themeOptions.hide) {
                this.footer = null;
            }

            // must construct body first so it listens to backgrid:sort first
            if (this.body) {
                this.body = new this.body(bodyOptions);
                this.subview('body', this.body);
            }

            if (this.header) {
                this.header = new this.header(headerOptions);
                this.subview('header', this.header);
                if ('selectState' in this.header.row.subviews[0]) {
                    this.selectState = this.header.row.subviews[0].selectState;
                }
            }
            if (this.selectState === null) {
                this.selectState = new SelectState();
            }

            if (this.footer) {
                this.footer = new this.footer(footerOptions);
                this.subview('footer', this.footer);
            }

            this.listenTo(this.columns, 'reset', function() {
                if (this.header) {
                    this.header = new (this.header.remove().constructor)(headerOptions);
                }
                if (this.body) {
                    this.body = new (this.body.remove().constructor)(bodyOptions);
                }
                if (this.footer) {
                    this.footer = new (this.footer.remove().constructor)(footerOptions);
                }
                this.render();
            });

            this.listenTo(this.collection, {
                'remove': this.onCollectionModelRemove,
                'updateState': this.onCollectionUpdateState,
                'backgrid:selected': this.onSelectRow,
                'backgrid:selectAll': this.selectAll,
                'backgrid:selectAllVisible': this.selectAllVisible,
                'backgrid:selectNone': this.selectNone,
                'backgrid:isSelected': this.isSelected,
                'backgrid:getSelected': this.getSelected
            });
        },

        onCollectionUpdateState: function(collection, state) {
            if (this.stateIsResettable(collection.previousState, state)) {
                this.selectNone();
            }
        },

        onCollectionModelRemove: function(model) {
            this.selectState.removeRow(model);
        },

        onSelectRow: function(model, status) {
            if (status === this.selectState.get('inset')) {
                this.selectState.addRow(model);
            } else {
                this.selectState.removeRow(model);
            }
        },

        /**
         * Performs selection of all possible models:
         *  - reset to initial state
         *  - change type of set type as not-inset
         *  - marks all models in collection as selected
         *  start to collect models which have to be excluded
         */
        selectAll: function() {
            this.collection.each(function(model) {
                model.trigger('backgrid:select', model, true);
            });
            this.selectState.reset({inset: false});
        },

        /**
         * Reset selection of all possible models:
         *  - reset to initial state
         *  - change type of set type as inset
         *  - marks all models in collection as not selected
         *  start to collect models which have to be included
         */
        selectNone: function() {
            this.collection.each(function(model) {
                model.trigger('backgrid:select', model, false);
            });
            this.selectState.reset();
        },

        /**
         * Performs selection of all visible models:
         *  - if necessary reset to initial state
         *  - marks all models in collection as selected
         */
        selectAllVisible: function() {
            this.selectState.reset();
            this.collection.each(function(model) {
                model.trigger('backgrid:select', model, true);
            });
        },

        /**
         * Checks if model is selected
         *  - updates passed obj {selected: true} or {selected: false}
         *
         * @param {Backbone.Model} model
         * @param {Object} obj
         */
        isSelected: function(model, obj) {
            if ($.isPlainObject(obj)) {
                obj.selected = this.selectState.hasRow(model) === this.selectState.get('inset');
            }
        },

        /**
         * Collects selected models
         *  - updates passed obj
         *  {
         *      inset: true,// or false
         *      selected: [
         *          // array of models' ids
         *      ]
         *  }
         *
         * @param {Object} obj
         */
        getSelected: function(obj) {
            if ($.isEmptyObject(obj)) {
                obj.selected = this.selectState.get('rows');
                obj.inset = this.selectState.get('inset');
            }
        },

        /**
         * @param {*} previousState
         * @param {*} state
         * @returns {boolean} TRUE if values are not equal, otherwise - FALSE
         */
        stateIsResettable: function(previousState, state) {
            const fields = ['filters', 'gridView', 'pageSize'];

            return !tools.isEqualsLoosely(
                _.pick(previousState, fields),
                _.pick(state, fields)
            );
        },

        /**
         * @param {Object} opts
         * @private
         */
        _initToolbars: function(opts) {
            this.toolbars = {};
            this.toolbarOptions = {};
            _.extend(this.toolbarOptions, this.defaults.toolbarOptions, opts.toolbarOptions);
        },

        /**
         * @param {Object} opts
         * @private
         */
        _initActions: function(opts) {
            const themeActionsOpts = opts.themeOptions.actionOptions;
            if (_.isObject(themeActionsOpts)) {
                this.actionOptions = $.extend(true, {}, this.actionOptions, themeActionsOpts);
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.pluginManager.dispose();

            this.filteredColumns.dispose();
            delete this.filteredColumns;

            this.columns.dispose();
            delete this.columns;

            delete this.refreshAction;
            delete this.resetAction;
            delete this.exportAction;
            delete this.gridRowsCounter;

            const subviews = ['header', 'body', 'footer', 'loadingMask'];
            _.each(subviews, function(viewName) {
                if (this[viewName]) {
                    this[viewName].dispose();
                    delete this[viewName];
                }
            }, this);

            this.callToolbar('dispose');
            delete this.toolbars;
            mediator.off('import-export:handleExport', this.onHandleExport, this);

            Grid.__super__.dispose.call(this);
        },

        /**
         * @inheritdoc
         */
        delegateEvents: function(events) {
            Grid.__super__.delegateEvents.call(this, events);

            let $parents = this.$('.grid-container').parents();
            if ($parents.length) {
                $parents = $parents.add(document);
                $parents.on('scroll' + this.eventNamespace(), this.trigger.bind(this, 'scroll'));
                this._$boundScrollHandlerParents = $parents;

                this.listenTo(this.collection, 'backgrid:sort', _.debounce(this.sort, 50), this);
            }

            mediator.on('import-export:handleExport', this.onHandleExport, this);
            return this;
        },

        /**
         * Pass whole query string (with selected filters, sorters, etc) to the export url
         * if "filteredResultsGrid" option was passed to the configuration.
         *
         * @param exportRouteOptions
         */
        onHandleExport: function(exportRouteOptions) {
            if (exportRouteOptions.hasOwnProperty('filteredResultsGrid')) {
                const queryParams = tools.unpackFromQueryString(window.location.search);
                const gridName = exportRouteOptions['filteredResultsGrid'];

                if (queryParams.hasOwnProperty('grid') && queryParams['grid'].hasOwnProperty(gridName)) {
                    exportRouteOptions.filteredResultsGridParams = queryParams['grid'][gridName];
                }
            }
        },

        /**
         * @inheritdoc
         */
        undelegateEvents: function() {
            Grid.__super__.undelegateEvents.call(this);

            if (this._$boundScrollHandlerParents) {
                this._$boundScrollHandlerParents.off(this.eventNamespace());
                delete this._$boundScrollHandlerParents;
            }

            this.stopListening(this.collection, 'backgrid:sort');

            return this;
        },

        /**
         * Initializes columns collection required to draw grid
         *
         * @param {Object} options
         * @private
         */
        _initColumns: function(options) {
            if (Object.keys(this.rowActions).length > 0) {
                options.columns.push(this._createActionsColumn());
            }

            if (options.multiSelectRowEnabled) {
                options.columns.unshift(this._createSelectRowColumn());
            }

            for (let i = 0; i < options.columns.length; i++) {
                const column = options.columns[i];
                if (column.order === void 0 && !(column instanceof Backgrid.Column)) {
                    column.order = i + this.DEFAULT_COLUMN_START_INDEX;
                }
                column.metadata = _.findWhere(options.metadata.columns, {name: column.name});
            }

            this.columns = options.columns = new GridColumns(options.columns);
            this.columns.sort();
            this.trigger('columns:ready');
        },

        /**
         * Init this.rowActions and this.rowClickAction
         *
         * @private
         */
        _initRowActions: function() {
            if (!this.rowClickAction) {
                this.rowClickAction = _.find(this.rowActions, function(action) {
                    return Boolean(action.prototype.rowAction);
                });
            }
        },

        /**
         * Creates actions column
         *
         * @return {Backgrid.Column}
         * @private
         */
        _createActionsColumn: function() {
            const column = new this.actionsColumn({
                datagrid: this,
                actions: this.rowActions,
                massActions: this.massActions,
                manageable: false,
                order: Infinity,
                // Skip to add specific attributes if this cell has an empty value.
                notMarkAsBlank: true
            });
            return column;
        },

        /**
         * Creates mass actions column
         *
         * @return {Backgrid.Column}
         * @private
         */
        _createSelectRowColumn: function() {
            const column = new Backgrid.Column({
                name: 'massAction',
                label: __('Selected Rows'),
                renderable: true,
                sortable: false,
                editable: false,
                manageable: false,
                cell: this.selectRowCell,
                headerCell: this.selectAllHeaderCell,
                order: -Infinity,
                // Skip to add specific attributes if this cell has an empty value.
                notMarkAsBlank: true
            });
            return column;
        },

        /**
         * Gets selection state
         *
         * @returns {{selectedIds: *, inset: boolean}}
         */
        getSelectionState: function() {
            const state = {
                selectedIds: this.selectState.get('rows'),
                inset: this.selectState.get('inset')
            };
            return state;
        },

        /**
         * Resets selection state
         *
         * @param {Object|null} restoreState
         */
        resetSelectionState: function(restoreState) {
            this.collection.trigger('backgrid:selectNone');

            if (restoreState && restoreState.selectedIds) {
                this.collection.each(function(model) {
                    if (restoreState.selectedIds.indexOf(model) !== -1) {
                        model.trigger('backgrid:selected', model, true);
                    }
                });
            }
        },

        /**
         * Creates instance of toolbar
         *
         * @return {orodatagrid.datagrid.Toolbar}
         * @private
         */
        _createToolbar: function(options) {
            const ComponentConstructor = this.collection.options.modules.datagridSettingsComponentCustom || null;
            const sortActions = this.sortActions;
            const toolbarOptions = {
                collection: this.collection,
                actions: this._getToolbarActions(),
                extraActions: this._getToolbarExtraActions(),
                columns: this.columns,
                componentConstructor: ComponentConstructor,
                addToolbarAction: function(action) {
                    toolbarOptions.actions.push(action);
                    sortActions(toolbarOptions.actions);
                }
            };
            _.defaults(toolbarOptions, options);

            this.columns.trigger('configureInitializeOptions', this.toolbar, toolbarOptions);
            this.trigger('beforeToolbarInit', toolbarOptions);
            const toolbar = new this.toolbar(toolbarOptions);
            this.trigger('afterToolbarInit', toolbar);
            return toolbar;
        },

        /**
         * Sorts actions array
         * @param actions
         */
        sortActions: function(actions) {
            actions.sort(function(a, b) {
                return (a.order || 500) - (b.order || 500);
            });
        },

        /**
         * Get actions of toolbar
         *
         * @return {Array}
         * @private
         */
        _getToolbarActions: function() {
            const actions = [];
            if (this.toolbarOptions.addRefreshAction) {
                actions.push(this.getRefreshAction());
            }
            if (this.toolbarOptions.addResetAction) {
                actions.push(this.getResetAction());
            }
            if (this.toolbarOptions.addAppearanceSwitcher) {
                const action = new SelectDataAppearanceAction({
                    datagrid: this,
                    launcherOptions: {
                        label: __('oro_datagrid.action.appearance'),
                        items: this.toolbarOptions.availableAppearances,
                        attributes: {
                            'data-placement': 'bottom-end'
                        },
                        className: 'btn btn-icon data-appearance-selector'
                    },
                    order: 700
                });
                this.on('appearanceChanged', function(key, options) {
                    const item = _.findWhere(action.launcherInstance.items,
                        {key: options.type, id: options.id || 'by_type'});
                    if (!item) {
                        throw new Error('Could not find corresponding launcher item');
                    }
                    action.launcherInstance.selectedItem = item;
                    action.launcherInstance.render();
                });
                actions.push(action);
            }
            this.sortActions(actions);
            return actions;
        },

        changeAppearance: function(key, options) {
            this.switchAppearanceClass(key);
            this.trigger('changeAppearance', key, options);
        },

        switchAppearanceClass: function(appearanceType) {
            const appearanceClass = _.find(this.el.classList, function(cls) {
                return /-appearance$/.test(cls);
            });
            if (appearanceClass) {
                this.$el.removeClass(appearanceClass);
            }
            if (appearanceType) {
                this.$el.addClass(appearanceType + '-appearance');
            }
        },

        /**
         * Get actions of toolbar
         *
         * @return {Array}
         * @private
         */
        _getToolbarExtraActions: function() {
            const actions = this.extraActions.map(actionModel => {
                const Action = actionModel.get('module');
                return new Action({
                    datagrid: this
                });
            });

            if (!_.isEmpty(this.exportOptions)) {
                actions.push(this.getExportAction());
            }
            return actions;
        },

        /**
         * Get action that refreshes grid's collection
         *
         * @return {oro.datagrid.action.RefreshCollectionAction}
         */
        getRefreshAction: function() {
            if (!this.refreshAction) {
                this.refreshAction = new RefreshCollectionAction({
                    datagrid: this,
                    launcherOptions: this.actionOptions.refreshAction.launcherOptions,
                    order: 100
                });

                this.listenTo(mediator, 'datagrid:doRefresh:' + this.name, _.debounce(function(ignoreVisibility) {
                    if (ignoreVisibility || this.$el.is(':visible')) {
                        this.refreshAction.execute();
                    } else {
                        this._hasDeferRefresh = true;
                    }
                }, 100, true));

                this.listenTo(mediator, 'content:shown', function() {
                    if (this._hasDeferRefresh && this.$el.is(':visible')) {
                        delete this._hasDeferRefresh;
                        this.refreshAction.execute();
                    }
                }.bind(this));

                this.listenTo(this.refreshAction, 'preExecute', function(action, options) {
                    this.$el.trigger('preExecute:refresh:' + this.name, [action, options]);
                });
            }

            return this.refreshAction;
        },

        /**
         * Get action that resets grid's collection
         *
         * @return {oro.datagrid.action.ResetCollectionAction}
         */
        getResetAction: function() {
            if (!this.resetAction) {
                this.resetAction = new ResetCollectionAction({
                    datagrid: this,
                    launcherOptions: this.actionOptions.resetAction.launcherOptions,
                    order: 200
                });

                this.listenTo(mediator, 'datagrid:doReset:' + this.name, _.debounce(function() {
                    if (this.$el.is(':visible')) {
                        this.resetAction.execute();
                    }
                }, 100, true));

                this.listenTo(this.resetAction, 'preExecute', function(action, options) {
                    this.$el.trigger('preExecute:reset:' + this.name, [action, options]);
                });
            }

            return this.resetAction;
        },

        /**
         * Get action that exports grid's data
         *
         * @return {oro.datagrid.action.ExportAction}
         */
        getExportAction: function() {
            if (!this.exportAction) {
                const links = [];
                _.each(this.exportOptions, function(val, key) {
                    links.push({
                        key: key,
                        label: val.label,
                        show_max_export_records_dialog: val.show_max_export_records_dialog,
                        max_export_records: val.max_export_records,
                        attributes: {
                            'class': 'no-hash',
                            'download': null
                        }
                    });
                });
                this.exportAction = new ExportAction({
                    datagrid: this,
                    launcherOptions: {
                        label: __('oro.datagrid.extension.export.label'),
                        title: __('oro.datagrid.extension.export.tooltip'),
                        className: 'btn',
                        iconClassName: 'fa-upload',
                        links: links
                    }
                });

                this.listenTo(this.exportAction, 'preExecute', function(action, options) {
                    this.$el.trigger('preExecute:export:' + this.name, [action, options]);
                });
            }

            return this.exportAction;
        },

        /**
         * Listen to events of collection
         *
         * @private
         */
        _listenToCollectionEvents: function() {
            this.listenTo(this.collection, 'request', function(model, xhr, options = {}) {
                this._beforeRequest(options);
                const always = xhr.always;
                xhr.always = (...args) => {
                    always.apply(xhr, args);
                    if (!this.disposed) {
                        this._afterRequest(xhr, options);
                    }
                };
            });

            this.listenTo(this.collection, 'remove', this._onRemove);
            this.listenTo(this.collection, 'add reset', this.setGridAriaAttrs);

            this.listenTo(this.collection, 'change', function(model) {
                this.$el.trigger('datagrid:change:' + this.name, model);
            });
        },

        /**
         * Listen to events of body, proxies events "rowClicked", handle run of rowClickAction if required
         *
         * @private
         */
        _listenToContentEvents: function() {
            this.listenTo(this.body, 'rowClicked', function(row, options) {
                this.trigger('rowClicked', this, row);
                this.runRowClickAction(row.model, options);
            });
            this.listenTo(this.columns, 'change:renderable', function() {
                this.trigger('content:update');
            });
            if (this.header) {
                this.listenTo(this.header.row, 'columns:reorder', function() {
                    // triggers content:update event in separate process
                    // to give time body's rows to finish reordering
                    _.defer(this.trigger.bind(this, 'content:update'));
                });
            }
        },

        /**
         * Create row click action
         *
         * @param {Backbone.Model} data
         * @param {Object} options
         * @private
         */
        runRowClickAction: function(model, options) {
            if (!this.rowClickAction) {
                return;
            }

            const action = new this.rowClickAction({
                datagrid: this,
                model: model
            });
            if (typeof action.dispose === 'function') {
                this.subviews.push(action);
            }
            const config = model.get('action_configuration');
            if (!config || config[action.name] !== false) {
                action.run(options);
            }
        },

        /**
         * Listen to commands on mediator
         */
        _listenToCommands: function() {
            this.listenTo(mediator, 'datagrid:setParam:' + this.name, function(param, value) {
                this.setAdditionalParameter(param, value);
            });

            this.listenTo(mediator, 'datagrid:removeParam:' + this.name, function(param) {
                this.removeAdditionalParameter(param);
            });

            this.listenTo(mediator, 'datagrid:restoreState:' + this.name,
                function(columnName, dataField, included, excluded) {
                    this.collection.each(function(model) {
                        if (_.indexOf(included, model.get(dataField)) !== -1) {
                            model.set(columnName, true);
                        }
                        if (_.indexOf(excluded, model.get(dataField)) !== -1) {
                            model.set(columnName, false);
                        }
                    });
                });

            this.listenTo(mediator, 'datagrid:restoreChangeset:' + this.name, function(dataField, changeset) {
                this.collection.each(function(model) {
                    if (changeset[model.get(dataField)]) {
                        _.each(changeset[model.get(dataField)], function(value, columnName) {
                            model.set(columnName, value);
                        });
                    }
                });
            });

            this.listenTo(mediator, `datagrid:changeColumnParam:${this.name}`, this.changeColumnParam);

            this.listenTo(mediator, 'datagrid:doRefresh:' + this.name, function() {
                if (!this.refreshAction) {
                    this._onDatagridRefresh();
                }
            });

            this.listenTo(mediator, 'datagrid:highlightNew:' + this.name, (...ids) => {
                ids = ids.map(id => id.toString());
                this.collection.each(model => {
                    if (ids.includes(model.id)) {
                        model.set('isNew', true);
                    }
                });
            });

            this.listenTo(mediator, 'datagrid:doInitialRefresh:' + this.name, () => {
                this.setAdditionalParameter('refresh', true);
                this.collection.getFirstPage();
                this.removeAdditionalParameter('refresh');
            });
        },

        /**
         * Changes column`s option  if such option exist
         *
         * @param columnName
         * @param option
         * @param value
         */
        changeColumnParam: function(columnName, option, value) {
            this.columns.each(column => {
                if (column.get('name') === columnName) {
                    column.set(option, value);
                }
            });
        },

        /**
         * Renders the grid, no data block and loading mask
         *
         * @return {*}
         */
        render: function() {
            this.$el.html(this.template({
                tableTagName: this.themeOptions.tagName || 'table',
                tableClassName: this.themeOptions.tableClassName || ''
            }));
            this.$grid = this.$(this.selectors.grid);
            this.renderToolbar();
            this.renderGrid();
            this.renderNoDataBlock();
            this.renderLoadingMask();

            this.delegateEvents();
            this.listenTo(this.collection, 'reset remove sync', this.renderNoDataBlock);

            this._deferredRender();
            this.initLayout({
                datagrid: this
            }).always(() => {
                this.rendered = true;
                /**
                 * Backbone event. Fired when the grid has been successfully rendered.
                 * @event rendered
                 */
                this.trigger('rendered');

                /**
                 * Backbone event. Fired when data for grid has been successfully rendered.
                 * @event grid_render:complete
                 */
                mediator.trigger('grid_render:complete', this.$el);
                this._resolveDeferredRender();
            });

            this.rendered = true;

            this.switchAppearanceClass(_.result(this.metadata.state, 'appearanceType'));
            return this;
        },

        /**
         * Renders the grid's header, then footer, then finally the body.
         */
        renderGrid: function() {
            if (this.header) {
                this.$grid.append(this.header.render().$el);
            }
            if (this.body) {
                this.$grid.append(this.body.render().$el);
            }
            if (this.footer) {
                this.$grid.append(this.footer.render().$el);
            }

            this.$grid.attr('role', 'grid');
            this.setGridAriaAttrs();

            mediator.trigger('grid_load:complete', this.collection, this.$grid);
        },

        /**
         * Set aria attributes for grid
         */
        setGridAriaAttrs() {
            this.$grid.attr({
                'aria-rowcount': this.gridRowsCounter.getGridRowsCount(),
                'aria-colcount': this.columns.filter(model => model.renderable).length
            });
        },

        /**
         * Renders grid toolbar.
         */
        renderToolbar: function() {
            const self = this;
            _.each(this.toolbarOptions.placement, function(enabled, position) {
                if (enabled) {
                    self.$(self.selectors.toolbars[position]).append(self.getToolbar(position).render().$el);
                }
            });
        },

        /**
         * Lazy init for toolbar
         *
         * @param {String} placement
         */
        getToolbar: function(placement) {
            if (this.toolbars[placement]) {
                return this.toolbars[placement];
            }

            const toolbarOptions = _.extend(this.toolbarOptions, {el: this.$(this.selectors.toolbars[placement])});
            this.toolbars[placement] = this._createToolbar(toolbarOptions);

            return this.toolbars[placement];
        },

        /**
         * Call method for all toolbars
         *
         * @param {String} method
         */
        callToolbar: function(method) {
            _.invoke(this.toolbars, method);
        },

        /**
         * Renders loading mask.
         */
        renderLoadingMask: function() {
            if (this.loadingMask) {
                this.loadingMask.dispose();
            }
            this.loadingMask = new LoadingMaskView({
                container: this.$(this.selectors.loadingMaskContainer)
            });
            this.subview('loadingMask', this.loadingMask);
        },

        /**
         * Define no data block.
         */
        _defineNoDataBlock: function() {
            let messageHTML;
            const placeholders = {
                entityHint: (this.entityHint || __(this.noDataTranslations.entityHint)).toLowerCase()
            };

            if (this.noColumnsFlag || _.isEmpty(this.collection.state.filters)) {
                if (_.has(this.noDataMessages, 'emptyGrid')) {
                    messageHTML = this.getEmptyGridCustomMessage(this.noDataMessages.emptyGrid);
                } else {
                    messageHTML = this.getEmptyGridMessage(placeholders);
                }
            } else {
                if (_.has(this.noDataMessages, 'emptyFilteredGrid')) {
                    messageHTML = this.getEmptySearchResultCustomMessage(this.noDataMessages.emptyFilteredGrid);
                } else {
                    messageHTML = this.getEmptySearchResultMessage(placeholders);
                }
            }

            this.$(this.selectors.noDataBlock).html(messageHTML);
        },

        /**
         * Not found entities message when grid result is empty
         *
         * @param {Object} placeholders
         * @returns {String}
         */
        getEmptyGridMessage: function(placeholders) {
            const translation = this.noColumnsFlag
                ? this.noDataTranslations.noColumns : this.noDataTranslations.noEntities;

            return this.noDataTemplate({
                text: __(translation, placeholders)
            });
        },

        /**
         * Custom not found entities message when grid result is empty
         *
         * @param {String} message
         * @returns {String}
         */
        getEmptyGridCustomMessage: function(message) {
            return this.noDataTemplate({
                text: message
            });
        },

        /**
         * Not found entities message when grid result is empty after applying the filters
         *
         * @param {Object} placeholders
         * @returns {String}
         */
        getEmptySearchResultMessage: function(placeholders) {
            return this.noSearchResultsTemplate({
                title: __(this.noDataTranslations.noResultsTitle),
                text: __(this.noDataTranslations.noResults, placeholders)
            });
        },

        /**
         * Custom not found entities message when grid result is empty after applying the filters
         *
         * @param {String} message
         * @returns {String}
         */
        getEmptySearchResultCustomMessage: function(message) {
            return this.noSearchResultsTemplate({
                title: __(this.noDataTranslations.noResultsTitle),
                text: message
            });
        },

        /**
         * Triggers when collection "request" event fired
         *
         * @private
         */
        _beforeRequest: function(options = {}) {
            const {toggleLoading = true} = options;
            this.requestsCount += 1;

            if (toggleLoading) {
                this.showLoading();
            }
            this.lockToolBar();
        },

        /**
         * Triggers when collection request is done
         *
         * @private
         */
        _afterRequest: function(jqXHR, options = {}) {
            const json = jqXHR.responseJSON || {};
            const {toggleLoading = true} = options;

            if (json.metadata) {
                this._processLoadedMetadata(json.metadata);
            }

            this.requestsCount -= 1;
            if (this.requestsCount === 0) {
                if (toggleLoading) {
                    this.hideLoading();
                }
                this.unlockToolBar();
                /**
                 * Backbone event. Fired when data for grid has been successfully rendered.
                 * @event grid_load:complete
                 */
                mediator.trigger('grid_load:complete', this.collection, this.$el);
                this.initLayout();
                this.trigger('content:update');
            }
        },

        /**
         * @param {Object} metadata
         * @private
         */
        _processLoadedMetadata: function(metadata) {
            _.extend(this.metadata, metadata);
            this.metadataModel.set(metadata);

            mediator.trigger('datagrid:metadata-loaded', this);
        },

        /**
         * Show loading mask
         */
        showLoading: function() {
            this.loadingMask.show();
            this.trigger('loading-mask:show');
        },

        /**
         * Disable toolbar
         */
        lockToolBar: function() {
            this.callToolbar('disable');
            this.trigger('disable');
        },

        /**
         * Hide loading mask
         */
        hideLoading: function() {
            this.trigger('loading-mask:hide');
            this.loadingMask.hide();
        },

        /**
         * Enable toolbar
         */
        unlockToolBar: function() {
            this.callToolbar('enable');
            this.trigger('enable');
        },

        /**
         * Update no data block status
         *
         * @private
         */
        renderNoDataBlock: function() {
            this._defineNoDataBlock();
            this.$el.toggleClass('no-data-visible', this.collection.models.length <= 0 || this.noColumnsFlag);
        },

        /**
         * Triggers when collection "remove" event fired
         *
         * @private
         */
        _onRemove: function(model, collection, options = {}) {
            mediator.trigger('datagrid:beforeRemoveRow:' + this.name, model);

            if (collection) {
                const fetchKeys = ['mode', 'parse', 'reset', 'wait', 'uniqueOnly',
                    'add', 'remove', 'merge', 'toggleLoading'];
                let fetchOptions = {
                    reset: true,
                    alreadySynced: true // prevents recursion update
                };
                let params = _.pick(collection.options, fetchKeys);

                if (collection.options.parseResponseOptions) {
                    params = _.extend( params, _.pick(collection.options.parseResponseOptions(), fetchKeys));
                }

                fetchOptions = _.extend(fetchOptions, params, _.pick(options, fetchKeys));

                if (!options.alreadySynced) {
                    this.collection.fetch(fetchOptions);
                }
            }

            this.setGridAriaAttrs();
            mediator.trigger('datagrid:afterRemoveRow:' + this.name);
        },

        _onDatagridRefresh: function() {
            this.setAdditionalParameter('refresh', true);
            this.collection.fetch({reset: true});
            this.removeAdditionalParameter('refresh');
        },

        /**
         * Set additional parameter to send on server
         *
         * @param {String} name
         * @param value
         */
        setAdditionalParameter: function(name, value) {
            const state = this.collection.state;
            if (!_.has(state, 'parameters')) {
                state.parameters = {};
            }

            state.parameters[name] = value;
        },

        /**
         * Remove additional parameter
         *
         * @param {String} name
         */
        removeAdditionalParameter: function(name) {
            const state = this.collection.state;
            if (_.has(state, 'parameters')) {
                delete state.parameters[name];
            }
        },

        /**
         * Ensure that cell is visible. Works like cell.el.scrollIntoView, but in more appropriate way
         *
         * @param cell
         */
        ensureCellIsVisible: function(cell) {
            const e = $.Event('ensureCellIsVisible');
            this.trigger('ensureCellIsVisible', e, cell);
            if (e.isDefaultPrevented()) {
                return;
            }
            scrollHelper.scrollIntoView(cell.el);
        },

        /**
         * Finds cell by corresponding model and column
         *
         * @param model
         * @param column
         * @return {Backgrid.Cell}
         */
        findCell: function(model, column) {
            const rows = this.body.rows;
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                if (row.model === model) {
                    const cells = row.subviews;
                    for (let j = 0; j < cells.length; j++) {
                        const cell = cells[j];
                        if (cell.column === column) {
                            return cell;
                        }
                    }
                }
            }
            return null;
        },

        /**
         * Finds cell by model and column indexes
         *
         * @param {number} modelI
         * @param {number} columnI
         * @return {Backgrid.Cell}
         */
        findCellByIndex: function(modelI, columnI) {
            try {
                return _.findWhere(this.body.subviews[modelI].subviews, {
                    column: this.columns.at(columnI)
                });
            } catch (e) {
                return null;
            }
        },

        /**
         * Finds header cell by column index
         *
         * @param {number} columnI
         * @return {Backgrid.Cell}
         */
        findHeaderCellByIndex: function(columnI) {
            if (!this.header) {
                return null;
            }
            try {
                return _.findWhere(this.header.row.subviews, {
                    column: this.columns.at(columnI)
                });
            } catch (e) {
                return null;
            }
        },

        /**
         * Create this function instead of original Body.__super__.refresh to customize options for subviews
         */
        backgridRefresh: function() {
            this.render();
            this.collection.trigger('backgrid:refresh', this);
            return this;
        },

        makeComparator: function(attr, order, func) {
            return function(left, right) {
                // extract the values from the models
                let t;
                let l = func(left, attr);
                let r = func(right, attr);
                // if descending order, swap left and right
                if (order === 1) {
                    t = l;
                    l = r;
                    r = t;
                }
                // compare as usual
                if (l === r) {
                    return 0;
                } else if (l < r) {
                    return -1;
                }
                return 1;
            };
        },

        /**
         * @param {string} column
         * @param {null|"ascending"|"descending"} direction
         */
        sort: function(column, direction) {
            if (!_.contains(['ascending', 'descending', null], direction)) {
                throw new RangeError('direction must be one of "ascending", "descending" or `null`');
            }
            if (_.isString(column) && column.length) {
                column = this.columns.findWhere({name: column});
            }

            let columnName = null;
            let columnSortValue = null;
            if (_.isObject(column)) {
                columnName = column.get('name');
                columnSortValue = column.sortValue();
            } else {
                column = null;
            }

            const collection = this.collection;

            let order;

            if (direction === 'ascending') {
                order = '-1';
            } else if (direction === 'descending') {
                order = '1';
            } else {
                order = null;
            }

            let extractorDelegate;
            if (order) {
                extractorDelegate = columnSortValue;
            } else {
                extractorDelegate = function(model) {
                    return model.cid.replace('c', '') * 1;
                };
            }
            const comparator = this.makeComparator(columnName, order, extractorDelegate);

            if (collection instanceof PageableCollection) {
                collection.setSorting(columnName, order, {sortValue: columnSortValue});

                if (collection.fullCollection) {
                    if (collection.fullCollection.comparator === null ||
                        collection.fullCollection.comparator === undefined) {
                        collection.fullCollection.comparator = comparator;
                    }
                    collection.fullCollection.sort();
                    collection.trigger('backgrid:sorted', column, direction, collection);
                } else {
                    collection.fetch({reset: true, success: function() {
                        collection.trigger('backgrid:sorted', column, direction, collection);
                    }});
                }
            } else {
                collection.comparator = comparator;
                collection.sort();
                collection.trigger('backgrid:sorted', column, direction, collection);
            }

            if (column) {
                column.set('direction', direction);
            }

            return this;
        },

        /**
         * @return {String|null}
         */
        getGridScope: function() {
            const nameParts = this.name.split(this.scopeDelimiter);
            if (nameParts.length > 2) {
                throw new Error(
                    'Grid name is invalid, it should not contain more than one occurrence of "' +
                    this.scopeDelimiter + '"'
                );
            }

            return nameParts.length === 2 ? nameParts[1] : null;
        }
    });

    return Grid;
});
