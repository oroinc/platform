define(function(require) {
    'use strict';

    var Grid;
    var $ = require('jquery');
    var _ = require('underscore');
    var Backbone = require('backbone');
    var Backgrid = require('backgrid');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var GridHeader = require('./header');
    var GridBody = require('./body');
    var GridFooter = require('./footer');
    var GridColumns = require('./columns');
    var Toolbar = require('./toolbar');
    var SelectState = require('./select-state-model');
    var ActionColumn = require('./column/action-column');
    var SelectRowCell = require('oro/datagrid/cell/select-row-cell');
    var SelectAllHeaderCell = require('./header-cell/select-all-header-cell');
    var RefreshCollectionAction = require('oro/datagrid/action/refresh-collection-action');
    var ResetCollectionAction = require('oro/datagrid/action/reset-collection-action');
    var ExportAction = require('oro/datagrid/action/export-action');
    var PluginManager = require('oroui/js/app/plugins/plugin-manager');
    var scrollHelper = require('oroui/js/tools/scroll-helper');
    var util = require('./util');

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
    Grid = Backgrid.Grid.extend({
        /** @property {String} */
        name: 'datagrid',

        /** @property {String} */
        tagName: 'div',

        /** @property {int} */
        requestsCount: 0,

        /** @property {String} */
        className: 'oro-datagrid',

        /** @property */
        noDataTemplate: _.template('<span><%= hint %><span>'),

        /** @property {Object} */
        selectors: {
            grid:        '.grid',
            toolbar:     '[data-grid-toolbar]',
            toolbars: {
                top: '[data-grid-toolbar=top]',
                bottom: '[data-grid-toolbar=bottom]'
            },
            noDataBlock: '.no-data',
            filterBox:   '.filter-box',
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

        /** @property true when no one column configured to be shown in th grid */
        noColumnsFlag: false,

        selectState: null,

        /**
         * @property {Object} Default properties values
         */
        defaults: {
            rowClickActionClass:    'row-click-action',
            rowClassName:           '',
            toolbarOptions:         {
                addResetAction: true,
                addRefreshAction: true,
                addColumnManager: true,
                addSorting: false,
                columnManager: {},
                placement: {
                    top: true,
                    bottom: false
                }
            },
            rowClickAction:         undefined,
            multipleSorting:        true,
            rowActions:             [],
            massActions:            new Backbone.Collection(),
            enableFullScreenLayout: false
        },

        /**
         * Column indexing starts from this valus in case when 'order' is not specified in column config.
         * This start index required to display new columns at end of already sorted columns set
         */
        DEFAULT_COLUMN_START_INDEX: 1000,

        /** @property */
        template: require('tpl!orodatagrid/templates/datagrid/grid.html'),

        themeOptions: {
            optionPrefix: 'grid',
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
         * @param {Array<oro.datagrid.action.AbstractAction>} [options.rowActions] Array of row actions prototypes
         * @param {Backbone.Collection<oro.datagrid.action.AbstractAction>} [options.massActions] Collection of mass actions prototypes
         * @param {Boolean} [options.multiSelectRowEnabled] Option for enabling multi select row
         * @param {oro.datagrid.action.AbstractAction} [options.rowClickAction] Prototype for
         *  action that handles row click
         * @throws {TypeError} If mandatory options are undefined
         */
        initialize: function(options) {
            var opts = options || {};
            this.pluginManager = new PluginManager(this);
            if (options.plugins) {
                for (var i = 0; i < options.plugins.length; i++) {
                    var plugin = options.plugins[i];
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
         * @param {Object} options
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
            this.subviews = [];
            this.collection = opts.collection;

            if (opts.columns.length === 0) {
                this.noColumnsFlag = true;
            }

            _.extend(this, this.defaults, opts);
            this._initToolbars(opts);
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
            var filteredOptions = _.omit(
                options,
                ['el', 'id', 'attributes', 'className', 'tagName', 'events', 'themeOptions']
            );

            this.header = options.header || this.header;
            var headerOptions = _.extend({}, filteredOptions);
            this.columns.trigger('configureInitializeOptions', this.header, headerOptions);
            if (headerOptions.themeOptions.hide) {
                this.header = null;
            }

            this.body = options.body || this.body;

            var bodyOptions = _.extend({}, filteredOptions);
            this.columns.trigger('configureInitializeOptions', this.body, bodyOptions);
            this.footer = options.footer || this.footer;
            var footerOptions = _.extend({}, filteredOptions);
            this.columns.trigger('configureInitializeOptions', this.footer, footerOptions);
            if (footerOptions.themeOptions.hide) {
                this.footer = null;
            }

            // must construct body first so it listens to backgrid:sort first
            this.body = new this.body(bodyOptions);
            this.subview('body', this.body);

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
                this.body = new (this.body.remove().constructor)(bodyOptions);
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

        onCollectionUpdateState: function() {
            this.selectState.reset();
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
            this.selectState.reset({'inset': false});
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
         * @param {Object} opts
         * @private
         */
        _initToolbars: function(opts) {
            this.toolbars = {};
            this.toolbarOptions = {};
            _.extend(this.toolbarOptions, this.defaults.toolbarOptions, opts.toolbarOptions);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            var subviews;
            if (this.disposed) {
                return;
            }

            this.pluginManager.dispose();

            _.each(this.columns.models, function(column) {
                column.dispose();
            });

            this.filteredColumns.dispose();
            delete this.filteredColumns;

            this.columns.dispose();
            delete this.columns;

            delete this.refreshAction;
            delete this.resetAction;
            delete this.exportAction;

            subviews = ['header', 'body', 'footer', 'loadingMask'];
            _.each(subviews, function(viewName) {
                if (this[viewName]) {
                    this[viewName].dispose();
                    delete this[viewName];
                }
            }, this);

            this.callToolbar('dispose');
            delete this.toolbars;

            Grid.__super__.dispose.call(this);
        },

        /**
         * @inheritDoc
         */
        delegateEvents: function() {
            Grid.__super__.delegateEvents.apply(this, arguments);

            var $parents = this.$('.grid-container').parents();
            if ($parents.length) {
                $parents = $parents.add(document);
                $parents.on('scroll' + this.eventNamespace(), _.bind(this.trigger, this, 'scroll'));
                this._$boundScrollHandlerParents = $parents;
            }

            return this;
        },

        /**
         * @inheritDoc
         */
        undelegateEvents: function() {
            Grid.__super__.undelegateEvents.apply(this, arguments);

            if (this._$boundScrollHandlerParents) {
                this._$boundScrollHandlerParents.off(this.eventNamespace());
                delete this._$boundScrollHandlerParents;
            }

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

            for (var i = 0; i < options.columns.length; i++) {
                var column = options.columns[i];
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
            var column;
            column = new this.actionsColumn({
                datagrid: this,
                actions:  this.rowActions,
                massActions: this.massActions,
                manageable: false,
                order: Infinity
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
            var column;
            column = new Backgrid.Column({
                name:       'massAction',
                label:      __('Selected Rows'),
                renderable: true,
                sortable:   false,
                editable:   false,
                manageable: false,
                cell:       SelectRowCell,
                headerCell: SelectAllHeaderCell,
                order: -Infinity
            });
            return column;
        },

        /**
         * Gets selection state
         *
         * @returns {{selectedIds: *, inset: boolean}}
         */
        getSelectionState: function() {
            var state = {
                selectedIds: this.selectState.get('rows'),
                inset: this.selectState.get('inset')
            };
            return state;
        },

        /**
         * Resets selection state
         */
        resetSelectionState: function() {
            this.collection.trigger('backgrid:selectNone');
        },

        /**
         * Creates instance of toolbar
         *
         * @return {orodatagrid.datagrid.Toolbar}
         * @private
         */
        _createToolbar: function(options) {
            var toolbar;
            var toolbarOptions = {
                collection:   this.collection,
                actions:      this._getToolbarActions(),
                extraActions: this._getToolbarExtraActions(),
                columns:      this.columns
            };
            _.defaults(toolbarOptions, options);

            this.trigger('beforeToolbarInit', toolbarOptions);
            toolbar = new this.toolbar(toolbarOptions);
            this.trigger('afterToolbarInit', toolbar);
            return toolbar;
        },

        /**
         * Get actions of toolbar
         *
         * @return {Array}
         * @private
         */
        _getToolbarActions: function() {
            var actions = [];
            if (this.toolbarOptions.addRefreshAction) {
                actions.push(this.getRefreshAction());
            }
            if (this.toolbarOptions.addResetAction) {
                actions.push(this.getResetAction());
            }
            return actions;
        },

        /**
         * Get actions of toolbar
         *
         * @return {Array}
         * @private
         */
        _getToolbarExtraActions: function() {
            var actions = [];
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
                    launcherOptions: {
                        label: __('oro_datagrid.action.refresh'),
                        className: 'btn',
                        iconClassName: 'icon-repeat'
                    }
                });
                this.listenTo(mediator, 'datagrid:doRefresh:' + this.name, _.debounce(function() {
                    if (this.$el.is(':visible')) {
                        this.refreshAction.execute();
                    }
                }, 100));

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
                    launcherOptions: {
                        label: __('oro_datagrid.action.reset'),
                        className: 'btn',
                        iconClassName: 'icon-refresh'
                    }
                });

                this.listenTo(mediator, 'datagrid:doReset:' + this.name, _.debounce(function() {
                    if (this.$el.is(':visible')) {
                        this.resetAction.execute();
                    }
                }, 100));

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
                var links = [];
                _.each(this.exportOptions, function(val, key) {
                    links.push({
                        key: key,
                        label: val.label,
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
                        iconClassName: 'icon-upload-alt',
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
            this.listenTo(this.collection, 'request', function(model, xhr) {
                this._beforeRequest();
                var self = this;
                var always = xhr.always;
                xhr.always = function() {
                    always.apply(this, arguments);
                    if (!self.disposed) {
                        self._afterRequest(this);
                    }
                };
            });

            this.listenTo(this.collection, 'remove', this._onRemove);

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
                this._runRowClickAction(row, options);
            });
            this.listenTo(this.columns, 'change:renderable', function() {
                this.trigger('content:update');
            });
            if (this.header) {
                this.listenTo(this.header.row, 'columns:reorder', function() {
                    // triggers content:update event in separate process
                    // to give time body's rows to finish reordering
                    _.defer(_.bind(this.trigger, this, 'content:update'));
                });
            }
        },

        /**
         * Create row click action
         *
         * @param {orodatagrid.datagrid.Row} row
         * @param {Object} options
         * @private
         */
        _runRowClickAction: function(row, options) {
            var config;
            if (!this.rowClickAction) {
                return;
            }

            var action = new this.rowClickAction({
                datagrid: this,
                model: row.model
            });
            if (typeof action.dispose === 'function') {
                this.subviews.push(action);
            }
            config = row.model.get('action_configuration');
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
        },

        /**
         * Renders the grid, no data block and loading mask
         *
         * @return {*}
         */
        render: function() {
            this.$el.html(this.template({
                tableTagName: this.themeOptions.tagName || 'table'
            }));
            this.$grid = this.$(this.selectors.grid);

            this.renderToolbar();
            this.renderGrid();
            this.renderNoDataBlock();
            this.renderLoadingMask();

            this.delegateEvents();
            this.listenTo(this.collection, 'reset', this.renderNoDataBlock);

            this._deferredRender();
            this.initLayout().always(_.bind(function() {
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
            }, this));

            this.rendered = true;

            return this;
        },

        /**
         * Renders the grid's header, then footer, then finally the body.
         */
        renderGrid: function() {
            if (this.header) {
                this.$grid.append(this.header.render().$el);
            }
            if (this.footer) {
                this.$grid.append(this.footer.render().$el);
            }
            this.$grid.append(this.body.render().$el);

            mediator.trigger('grid_load:complete', this.collection, this.$grid);
        },

        /**
         * Renders grid toolbar.
         */
        renderToolbar: function() {
            var self = this;
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

            var toolbarOptions = _.extend(this.toolbarOptions, {el: this.$(this.selectors.toolbars[placement])});
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
            var placeholders = {
                entityHint: (this.entityHint || __('oro.datagrid.entityHint')).toLowerCase()
            };
            var message = _.isEmpty(this.collection.state.filters) ?
                'oro.datagrid.no.entities' : 'oro.datagrid.no.results';
            message = this.noColumnsFlag ? 'oro.datagrid.no.columns' : message;

            this.$(this.selectors.noDataBlock).html($(this.noDataTemplate({
                hint: __(message, placeholders).replace('\n', '<br />')
            })));
        },

        /**
         * Triggers when collection "request" event fired
         *
         * @private
         */
        _beforeRequest: function() {
            this.requestsCount += 1;
            this.showLoading();
        },

        /**
         * Triggers when collection request is done
         *
         * @private
         */
        _afterRequest: function(jqXHR) {
            var json = jqXHR.responseJSON || {};
            if (json.metadata) {
                this._processLoadedMetadata(json.metadata);
            }

            this.requestsCount -= 1;
            if (this.requestsCount === 0) {
                this.hideLoading();
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
        },

        /**
         * Show loading mask and disable toolbar
         */
        showLoading: function() {
            this.loadingMask.show();
            this.callToolbar('disable');
            this.trigger('disable');
        },

        /**
         * Hide loading mask and enable toolbar
         */
        hideLoading: function() {
            this.loadingMask.hide();
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
            this.$el.toggleClass('no-data-visible', this.collection.models.length <= 0  || this.noColumnsFlag);
        },

        /**
         * Triggers when collection "remove" event fired
         *
         * @private
         */
        _onRemove: function(model) {
            mediator.trigger('datagrid:beforeRemoveRow:' + this.name, model);

            this.collection.fetch({reset: true});

            mediator.trigger('datagrid:afterRemoveRow:' + this.name);
        },

        /**
         * Set additional parameter to send on server
         *
         * @param {String} name
         * @param value
         */
        setAdditionalParameter: function(name, value) {
            var state = this.collection.state;
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
            var state = this.collection.state;
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
            var e = $.Event('ensureCellIsVisible');
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
            var rows = this.body.rows;
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                if (row.model === model) {
                    var cells = row.subviews;
                    for (var j = 0; j < cells.length; j++) {
                        var cell = cells[j];
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
                return _.findWhere(this.body.rows[modelI].subviews, {
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
        }
    });

    return Grid;
});
