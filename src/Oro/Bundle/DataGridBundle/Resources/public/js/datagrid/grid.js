/*jslint nomen: true, vars: true*/
/*global define*/
define(function (require) {
    'use strict';

    var $ = require('jquery'),
        _ = require('underscore'),
        Backgrid = require('backgrid'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        LoadingMask = require('oroui/js/loading-mask'),
        GridHeader = require('./header'),
        GridBody = require('./body'),
        GridFooter = require('./footer'),
        Toolbar = require('./toolbar'),
        ActionColumn = require('./column/action-column'),
        SelectRowCell = require('./cell/select-row-cell'),
        SelectAllHeaderCell = require('./header-cell/select-all-header-cell'),
        RefreshCollectionAction = require('./action/refresh-collection-action'),
        ResetCollectionAction = require('./action/reset-collection-action'),
        ExportAction = require('./action/export-action');

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
    return Backgrid.Grid.extend({
        /** @property {String} */
        name: 'datagrid',

        /** @property {String} */
        tagName: 'div',

        /** @property {int} */
        requestsCount: 0,

        /** @property {String} */
        className: 'clearfix',

        /** @property */
        template: _.template(
            '<div class="toolbar"></div>' +
                '<div class="container-fluid">' +
                '<div class="grid-container">' +
                '<table class="grid table-hover table table-bordered table-condensed"></table>' +
                '<div class="no-data"></div>' +
                '<div class="loading-mask"></div>' +
                '</div>' +
                '</div>'
        ),

        /** @property */
        noDataTemplate: _.template('<span><%= hint %><span>'),

        /** @property {Object} */
        selectors: {
            grid:        '.grid',
            toolbar:     '.toolbar',
            noDataBlock: '.no-data',
            loadingMask: '.loading-mask',
            filterBox:   '.filter-box'
        },

        /** @property {orodatagrid.datagrid.Header} */
        header: GridHeader,

        /** @property {orodatagrid.datagrid.Body} */
        body: GridBody,

        /** @property {orodatagrid.datagrid.Footer} */
        footer: GridFooter,

        /** @property {orodatagrid.datagrid.Toolbar} */
        toolbar: Toolbar,

        /** @property {oro.LoadingMask} */
        loadingMask: LoadingMask,

        /** @property {orodatagrid.datagrid.column.ActionColumn} */
        actionsColumn: ActionColumn,

        /** @property true when no one column configured to be shown in th grid */
        noColumnsFlag: false,

        /**
         * @property {Object} Default properties values
         */
        defaults: {
            rowClickActionClass: 'row-click-action',
            rowClassName:        '',
            toolbarOptions:      {addResetAction: true, addRefreshAction: true},
            rowClickAction:      undefined,
            multipleSorting:     true,
            rowActions:          [],
            massActions:         []
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
         * @param {Array<orodatagrid.datagrid.action.AbstractAction>} [options.rowActions] Array of row actions prototypes
         * @param {Array<orodatagrid.datagrid.action.AbstractAction>} [options.massActions] Array of mass actions prototypes
         * @param {orodatagrid.datagrid.action.AbstractAction} [options.rowClickAction] Prototype for action that handles row click
         * @throws {TypeError} If mandatory options are undefined
         */
        initialize: function (options) {
            var opts = options || {};

            // Check required options
            if (!opts.collection) {
                throw new TypeError("'collection' is required");
            }
            this.collection = opts.collection;

            if (!opts.columns) {
                throw new TypeError("'columns' is required");
            }

            if (opts.columns.length === 0) {
                this.noColumnsFlag = true;
            }

            // Init properties values based on options and defaults
            _.extend(this, this.defaults, opts);
            this.toolbarOptions = {};
            _.extend(this.toolbarOptions, this.defaults.toolbarOptions, opts.toolbarOptions);
            this.exportOptions = {};
            _.extend(this.exportOptions, opts.exportOptions);

            this.collection.multipleSorting = this.multipleSorting;

            this._initRowActions();

            if (this.rowClickAction) {
                // This option property is used in orodatagrid.datagrid.Body
                opts.rowClassName = this.rowClickActionClass + ' ' + this.rowClassName;
            }

            opts.columns.push(this._createActionsColumn());
            if (!_.isEmpty(this.massActions)) {
                opts.columns.unshift(this._getSelectRowColumn());
            }

            this.loadingMask = this._createLoadingMask();
            this.toolbar = this._createToolbar(this.toolbarOptions);

            Backgrid.Grid.prototype.initialize.apply(this, arguments);

            // Listen and proxy events
            this._listenToCollectionEvents();
            this._listenToBodyEvents();
            this._listenToCommands();
        },

        /**
         * Inits this.rowActions and this.rowClickAction
         *
         * @private
         */
        _initRowActions: function () {
            if (!this.rowClickAction) {
                this.rowClickAction = _.find(this.rowActions, function (action) {
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
        _createActionsColumn: function () {
            return new this.actionsColumn({
                datagrid: this,
                actions:  this.rowActions,
                massActions: this.massActions
            });
        },

        /**
         * Creates mass actions column
         *
         * @return {Backgrid.Column}
         * @private
         */
        _getSelectRowColumn: function () {
            if (!this.selectRowColumn) {
                this.selectRowColumn = new Backgrid.Column({
                    name:       "massAction",
                    label:      __("Selected Rows"),
                    renderable: true,
                    sortable:   false,
                    editable:   false,
                    cell:       SelectRowCell,
                    headerCell: SelectAllHeaderCell
                });
            }

            return this.selectRowColumn;
        },

        /**
         * Gets selection state
         *
         * @returns {{selectedModels: *, inset: boolean}}
         */
        getSelectionState: function () {
            var selectAllHeader = this.header.row.cells[0];
            return selectAllHeader.getSelectionState();
        },

        /**
         * Resets selection state
         */
        resetSelectionState: function () {
            var selectAllHeader = this.header.row.cells[0];
            return selectAllHeader.selectNone();
        },

        /**
         * Creates loading mask
         *
         * @return {oro.LoadingMask}
         * @private
         */
        _createLoadingMask: function () {
            return new this.loadingMask();
        },

        /**
         * Creates instance of toolbar
         *
         * @return {orodatagrid.datagrid.Toolbar}
         * @private
         */
        _createToolbar: function (toolbarOptions) {
            return new this.toolbar(_.extend({}, toolbarOptions, {
                collection:   this.collection,
                actions:      this._getToolbarActions(),
                extraActions: this._getToolbarExtraActions()
            }));
        },

        /**
         * Get actions of toolbar
         *
         * @return {Array}
         * @private
         */
        _getToolbarActions: function () {
            var result = [];
            if (this.toolbarOptions.addRefreshAction) {
                result.push(this.getRefreshAction());
            }
            if (this.toolbarOptions.addResetAction) {
                result.push(this.getResetAction());
            }
            return result;
        },

        /**
         * Get actions of toolbar
         *
         * @return {Array}
         * @private
         */
        _getToolbarExtraActions: function () {
            var result = [];
            if (!_.isEmpty(this.exportOptions)) {
                result.push(this.getExportAction());
            }
            return result;
        },

        /**
         * Get action that refreshes grid's collection
         *
         * @return orodatagrid.datagrid.action.RefreshCollectionAction
         */
        getRefreshAction: function () {
            var grid = this;

            if (!grid.refreshAction) {
                grid.refreshAction = new RefreshCollectionAction({
                    datagrid: grid,
                    launcherOptions: {
                        label: 'Refresh',
                        className: 'btn',
                        iconClassName: 'icon-refresh'
                    }
                });

                mediator.on('datagrid:doRefresh:' + grid.name, function () {
                    if (grid.$el.is(':visible')) {
                        grid.refreshAction.execute();
                    }
                });

                grid.refreshAction.on('preExecute', function (action, options) {
                    grid.$el.trigger('preExecute:refresh:' + grid.name, [action, options]);
                });
            }

            return grid.refreshAction;
        },

        /**
         * Get action that resets grid's collection
         *
         * @return orodatagrid.datagrid.action.ResetCollectionAction
         */
        getResetAction: function () {
            var grid = this;

            if (!grid.resetAction) {
                grid.resetAction = new ResetCollectionAction({
                    datagrid: grid,
                    launcherOptions: {
                        label: 'Reset',
                        className: 'btn',
                        iconClassName: 'icon-repeat'
                    }
                });

                mediator.on('datagrid:doReset:' + grid.name, function () {
                    if (grid.$el.is(':visible')) {
                        grid.resetAction.execute();
                    }
                });

                grid.resetAction.on('preExecute', function (action, options) {
                    grid.$el.trigger('preExecute:reset:' + grid.name, [action, options]);
                });
            }

            return grid.resetAction;
        },

        /**
         * Get action that exports grid's data
         *
         * @return orodatagrid.datagrid.action.ExportAction
         */
        getExportAction: function () {
            var grid = this;

            if (!grid.exportAction) {
                var links = [];
                _.each(this.exportOptions, function (val, key) {
                    links.push({key: key, label: val.label, attributes: {'class': 'no-hash', 'download': null}});
                });
                grid.exportAction = new ExportAction({
                    datagrid: grid,
                    launcherOptions: {
                        label: 'Export',
                        className: 'btn',
                        iconClassName: 'icon-download-alt',
                        links: links
                    }
                });

                grid.exportAction.on('preExecute', function (action, options) {
                    grid.$el.trigger('preExecute:export:' + grid.name, [action, options]);
                });
            }

            return grid.exportAction;
        },

        /**
         * Listen to events of collection
         *
         * @private
         */
        _listenToCollectionEvents: function () {
            this.collection.on('request', function (model, xhr, options) {
                this._beforeRequest();
                var self = this;
                var always = xhr.always;
                xhr.always = function () {
                    always.apply(this, arguments);
                    self._afterRequest();
                };
            }, this);

            this.collection.on('remove', this._onRemove, this);

            var self = this;
            this.collection.on('change', function (model) {
                self.$el.trigger('datagrid:change:' + self.name, model);
            });
        },

        /**
         * Listen to events of body, proxies events "rowClicked", handle run of rowClickAction if required
         *
         * @private
         */
        _listenToBodyEvents: function () {
            this.listenTo(this.body, 'rowClicked', function (row) {
                this.trigger('rowClicked', this, row);
                this._runRowClickAction(row);
            });
        },

        /**
         * Create row click action
         *
         * @param {orodatagrid.datagrid.Row} row
         * @private
         */
        _runRowClickAction: function (row) {
            if (this.rowClickAction) {
                var action = new this.rowClickAction({
                        datagrid: this,
                        model:    row.model
                    }),
                    actionConfiguration = row.model.get('action_configuration');
                if (!actionConfiguration || actionConfiguration[action.name] !== false) {
                    action.run();
                }
            }
        },

        /**
         * Listen to commands on mediator
         */
        _listenToCommands: function () {
            var grid = this;

            mediator.on('datagrid:setParam:' + grid.name, function (param, value) {
                grid.setAdditionalParameter(param, value);
            });

            mediator.on('datagrid:restoreState:' + grid.name, function (columnName, dataField, included, excluded) {
                grid.collection.each(function (model) {
                    if (_.indexOf(included, model.get(dataField)) !== -1) {
                        model.set(columnName, true);
                    }
                    if (_.indexOf(excluded, model.get(dataField)) !== -1) {
                        model.set(columnName, false);
                    }
                });
            });
        },

        /**
         * Renders the grid, no data block and loading mask
         *
         * @return {*}
         */
        render: function () {
            this.$el.empty();

            this.$el = this.$el.append($(this.template()));

            this.renderToolbar();
            this.renderGrid();
            this.renderNoDataBlock();
            this.renderLoadingMask();
            this.collection.on('reset', _.bind(function () {
                this.renderNoDataBlock();
            }, this));

                /**
                 * Backbone event. Fired when the grid has been successfully rendered.
                 * @event rendered
                 */
                this.trigger("rendered");

                /**
                 * Backbone event. Fired when data for grid has been successfully rendered.
                 * @event grid_render:complete
                 */
                mediator.trigger('grid_render:complete', this.$el);

                return this;
            },

        /**
         * Renders the grid's header, then footer, then finally the body.
         */
        renderGrid: function () {
            var $el = this.$(this.selectors.grid);

            $el.append(this.header.render().$el);
            if (this.footer) {
                $el.append(this.footer.render().$el);
            }
            $el.append(this.body.render().$el);
        },

        /**
         * Renders grid toolbar.
         */
        renderToolbar: function () {
            this.$(this.selectors.toolbar).append(this.toolbar.render().$el);
        },

        /**
         * Renders loading mask.
         */
        renderLoadingMask: function () {
            this.$(this.selectors.loadingMask).append(this.loadingMask.render().$el);
            this.loadingMask.hide();
        },

        /**
         * Define no data block.
         */
        _defineNoDataBlock: function () {
            var placeholders = {entityHint: (this.entityHint || __('oro.datagrid.entityHint')).toLowerCase()},
                template = _.isEmpty(this.collection.state.filters) ?
                        'oro.datagrid.noentities' : 'oro.datagrid.noresults';
            template = this.noColumnsFlag ? 'oro.datagrid.nocolumns' : template;

            this.$(this.selectors.noDataBlock).html($(this.noDataTemplate({
                hint: __(template, placeholders).replace('\n', '<br />')
            }))).hide();
        },

        /**
         * Triggers when collection "request" event fired
         *
         * @private
         */
        _beforeRequest: function () {
            this.requestsCount += 1;
            this.showLoading();
        },

        /**
         * Triggers when collection request is done
         *
         * @private
         */
        _afterRequest: function () {
            this.requestsCount -= 1;
            if (this.requestsCount === 0) {
                this.hideLoading();
                /**
                 * Backbone event. Fired when data for grid has been successfully rendered.
                 * @event grid_load:complete
                 */
                mediator.trigger("grid_load:complete", this.collection, this.$el);
            }
        },

        /**
         * Show loading mask and disable toolbar
         */
        showLoading: function () {
            this.loadingMask.show();
            this.toolbar.disable();
            this.trigger('disable');
        },

        /**
         * Hide loading mask and enable toolbar
         */
        hideLoading: function () {
            this.loadingMask.hide();
            this.toolbar.enable();
            this.trigger('enable');
        },

        /**
         * Update no data block status
         *
         * @private
         */
        renderNoDataBlock: function () {
            this._defineNoDataBlock();
            if (this.collection.models.length > 0 && !this.noColumnsFlag) {
                this.$(this.selectors.toolbar).show();
                this.$(this.selectors.grid).show();
                this.$(this.selectors.filterBox).show();
                this.$(this.selectors.noDataBlock).hide();
            } else {
                this.$(this.selectors.grid).hide();
                this.$(this.selectors.toolbar).hide();
                this.$(this.selectors.filterBox).hide();
                this.$(this.selectors.noDataBlock).show();
            }
        },

        /**
         * Triggers when collection "remove" event fired
         *
         * @private
         */
        _onRemove: function () {
            this.collection.fetch();
        },

        /**
         * Set additional parameter to send on server
         *
         * @param {String} name
         * @param value
         */
        setAdditionalParameter: function (name, value) {
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
        removeAdditionalParameter: function (name) {
            var state = this.collection.state;
            if (_.has(state, 'parameters')) {
                delete state.parameters[name];
            }
        }
    });
});
