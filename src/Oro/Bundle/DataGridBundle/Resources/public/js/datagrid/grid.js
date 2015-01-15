/*jslint nomen: true, vars: true*/
/*global define*/
define(function (require) {
    'use strict';

    var Grid,
        $ = require('jquery'),
        _ = require('underscore'),
        Backgrid = require('backgrid'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        GridHeader = require('./header'),
        GridBody = require('./body'),
        GridFooter = require('./footer'),
        Toolbar = require('./toolbar'),
        ActionColumn = require('./column/action-column'),
        SelectRowCell = require('oro/datagrid/cell/select-row-cell'),
        SelectAllHeaderCell = require('./header-cell/select-all-header-cell'),
        RefreshCollectionAction = require('oro/datagrid/action/refresh-collection-action'),
        ResetCollectionAction = require('oro/datagrid/action/reset-collection-action'),
        ExportAction = require('oro/datagrid/action/export-action');

    require('orodatagrid/lib/jquery.floatThead');

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
        className: 'clearfix',

        /** @property */
        template: _.template(
            '<div class="toolbar"></div>' +
                '<div class="container-fluid grid-container-parent">' +
                    '<div class="grid-container">' +
                        '<table class="grid table-hover table table-bordered table-condensed"></table>' +
                        '<div class="no-data"></div>' +
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
            filterBox:   '.filter-box',
            loadingMaskContainer: '.grid-container-parent'
        },

        /** @property {orodatagrid.datagrid.Header} */
        header: GridHeader,

        /** @property {orodatagrid.datagrid.Body} */
        body: GridBody,

        /** @property {orodatagrid.datagrid.Footer} */
        footer: GridFooter,

        /** @property {orodatagrid.datagrid.Toolbar} */
        toolbar: Toolbar,

        /** @property {LoadingMaskView|null} */
        loadingMask: null,

        /** @property {orodatagrid.datagrid.column.ActionColumn} */
        actionsColumn: ActionColumn,

        /** @property true when no one column configured to be shown in th grid */
        noColumnsFlag: false,

        /**
         * @property {Object} Default properties values
         */
        defaults: {
            rowClickActionClass:    'row-click-action',
            rowClassName:           '',
            toolbarOptions:         {addResetAction: true, addRefreshAction: true},
            rowClickAction:         undefined,
            multipleSorting:        true,
            rowActions:             [],
            massActions:            [],
            enableFullScreenLayout: false
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
         * @param {Array<oro.datagrid.action.AbstractAction>} [options.massActions] Array of mass actions prototypes
         * @param {Boolean} [options.multiSelectRowEnabled] Option for enabling multi select row
         * @param {oro.datagrid.action.AbstractAction} [options.rowClickAction] Prototype for action that handles row click
         * @throws {TypeError} If mandatory options are undefined
         */
        initialize: function (options) {
            var opts = options || {};
            this.subviews = [];
            this.cid = _.uniqueId('grid-');

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

            if (opts.multiSelectRowEnabled) {
                opts.columns.unshift(this._createSelectRowColumn());
            }

            this.toolbar = this._createToolbar(this.toolbarOptions);

            Grid.__super__.initialize.apply(this, arguments);

            // Listen and proxy events
            this._listenToCollectionEvents();
            this._listenToBodyEvents();
            this._listenToCommands();

            this.listenTo(mediator, 'layout:reposition', this.checkLayout, this);
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            var subviews;
            if (this.disposed) {
                return;
            }

            this.setLayout('default');

            _.each(this.columns.models, function (column) {
                column.dispose();
            });
            this.columns.dispose();
            delete this.columns;
            delete this.refreshAction;
            delete this.resetAction;
            delete this.exportAction;

            subviews = ['header', 'body', 'footer', 'toolbar', 'loadingMask'];
            _.each(subviews, function (viewName) {
                this[viewName].dispose();
                delete this[viewName];
            }, this);

            Grid.__super__.dispose.call(this);
        },

        /**
         * Init this.rowActions and this.rowClickAction
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
            var column;
            column = new this.actionsColumn({
                datagrid: this,
                actions:  this.rowActions,
                massActions: this.massActions
            });
            return column;
        },

        /**
         * Creates mass actions column
         *
         * @return {Backgrid.Column}
         * @private
         */
        _createSelectRowColumn: function () {
            var coulmn;
            coulmn = new Backgrid.Column({
                name:       "massAction",
                label:      __("Selected Rows"),
                renderable: true,
                sortable:   false,
                editable:   false,
                cell:       SelectRowCell,
                headerCell: SelectAllHeaderCell
            });
            return coulmn;
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
            this.collection.trigger('backgrid:selectNone');
        },

        /**
         * Creates instance of toolbar
         *
         * @return {orodatagrid.datagrid.Toolbar}
         * @private
         */
        _createToolbar: function (options) {
            var toolbarOptions, toolbar;
            toolbarOptions = {
                collection:   this.collection,
                actions:      this._getToolbarActions(),
                extraActions: this._getToolbarExtraActions()
            };
            _.defaults(toolbarOptions, options);

            toolbar = new this.toolbar(toolbarOptions);
            return toolbar;
        },

        /**
         * Get actions of toolbar
         *
         *
         * @return {Array}
         * @private
         */
        _getToolbarActions: function () {
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
        _getToolbarExtraActions: function () {
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
        getRefreshAction: function () {
            if (!this.refreshAction) {
                this.refreshAction = new RefreshCollectionAction({
                    datagrid: this,
                    launcherOptions: {
                        label: __('oro_datagrid.action.refresh'),
                        className: 'btn',
                        iconClassName: 'icon-refresh'
                    }
                });

                this.listenTo(mediator, 'datagrid:doRefresh:' + this.name, function () {
                    if (this.$el.is(':visible')) {
                        this.refreshAction.execute();
                    }
                });

                this.listenTo(this.refreshAction, 'preExecute', function (action, options) {
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
        getResetAction: function () {
            if (!this.resetAction) {
                this.resetAction = new ResetCollectionAction({
                    datagrid: this,
                    launcherOptions: {
                        label: __('oro_datagrid.action.reset'),
                        className: 'btn',
                        iconClassName: 'icon-repeat'
                    }
                });

                this.listenTo(mediator, 'datagrid:doReset:' + this.name, function () {
                    if (this.$el.is(':visible')) {
                        this.resetAction.execute();
                    }
                });

                this.listenTo(this.resetAction, 'preExecute', function (action, options) {
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
        getExportAction: function () {
            if (!this.exportAction) {
                var links = [];
                _.each(this.exportOptions, function (val, key) {
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
                        iconClassName: 'icon-download-alt',
                        links: links
                    }
                });

                this.listenTo(this.exportAction, 'preExecute', function (action, options) {
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
        _listenToCollectionEvents: function () {
            this.listenTo(this.collection, 'request', function (model, xhr) {
                this._beforeRequest();
                var self = this;
                var always = xhr.always;
                xhr.always = function () {
                    always.apply(this, arguments);
                    self._afterRequest();
                };
            });

            this.listenTo(this.collection, 'remove', this._onRemove);

            this.listenTo(this.collection, 'change', function (model) {
                this.$el.trigger('datagrid:change:' + this.name, model);
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
            var action, config;
            if (!this.rowClickAction) {
                return;
            }

            action = new this.rowClickAction({
                datagrid: this,
                model: row.model
            });
            if (typeof action.dispose === 'function') {
                this.subviews.push(action);
            }
            config = row.model.get('action_configuration');
            if (!config || config[action.name] !== false) {
                action.run();
            }
        },

        /**
         * Listen to commands on mediator
         */
        _listenToCommands: function () {
            this.listenTo(mediator, 'datagrid:setParam:' + this.name, function (param, value) {
                this.setAdditionalParameter(param, value);
            });

            this.listenTo(mediator, 'datagrid:restoreState:' + this.name, function (columnName, dataField, included, excluded) {
                this.collection.each(function (model) {
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
            this.$el.append(this.template());

            this.renderToolbar();
            this.renderGrid();
            this.renderNoDataBlock();
            this.renderLoadingMask();
            this.listenTo(this.collection, 'reset', this.renderNoDataBlock);

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

            this.checkLayout();

            return this;
        },

        getCssHeightCalcExpression: function () {
            var $grid;
            $grid = this.$(this.selectors.grid);
            return 'calc(100vh - ' + ($grid.parents('.grid-container-parent:first')[0].getBoundingClientRect().top + 20) + 'px)';
        },

        reflow: function () {
            // for test
            var $grid;
            if (this.floatThead) {
                this.$(this.selectors.grid).floatThead('reflow');
                $grid = this.$(this.selectors.grid);
                $grid.parent().css({
                    maxHeight: this.getCssHeightCalcExpression()
                });
                $('body > .floatThead-dynamic-dropdown').remove();
            }
        },

        setFloatThead: function (newValue) {
            var $grid,
                $container,
                containerClass = '.grid-container',
                floatTheadContainerClass = '.floatThead-container',
                dropdownOpened = false,
                self = this;

            if (newValue !== this.floatThead) {
                this.floatThead = newValue;
                $grid = this.$(this.selectors.grid);
                if (newValue) {
                    $grid.floatThead({
                        useAbsolutePositioning: false,
                        scrollContainer: function($table){
                            return $table.closest(containerClass);
                        }
                    });
                    this.reflow();
                    $container = $grid.parent().find(floatTheadContainerClass);
                    $(document).on('click.floatThead-' + this.cid, function () {
                        if (dropdownOpened) {
                            $('body > .floatThead-dynamic-dropdown').remove();
                        }
                        dropdownOpened = false;
                    });
                    this.$el.on('click.floatThead-' + this.cid, '.floatThead-container .dropdown', function (e) {
                        dropdownOpened = true;

                        var $dropdown = $(e.target).closest('.dropdown'),
                            $dropdownMenu = $dropdown.find('.dropdown-menu');

                        // let bootstrap show menu
                        _.defer(function () {
                            var position = $dropdownMenu.offset(),
                                // not native clone
                                $dropdownMenuCopy = $dropdownMenu.cloneWithStyles();

                            $dropdownMenuCopy.addClass('floatThead-dynamic-dropdown');
                            $('body').append($dropdownMenuCopy);

                            $dropdownMenuCopy.css({
                                position: 'absolute',
                                top: position.top + 1, // not sure why but required to add 1 px to exactly match position
                                left: position.left
                            });
                            $dropdownMenuCopy.show();
                            $dropdownMenuCopy.on('mouseenter', function expandContainer() {
                                $container.css({
                                    height: self.getCssHeightCalcExpression()
                                });
                                $dropdownMenuCopy.hide();
                                $dropdownMenu.show();
                            });
                            $dropdownMenu.on('mouseleave', function collapseContainer() {
                                $dropdownMenu.hide();
                                $dropdownMenuCopy.show();
                                $container.css({
                                    height: ''
                                });
                            });
                        });
                    });
                } else {
                    $grid.floatThead('destroy');
                    $grid.parent().css({
                        maxHeight: ''
                    });
                    this.$el.off('.floatThead-' + this.cid);
                    $(document).off('.floatThead-' + this.cid);
                    $('body > .floatThead-dynamic-dropdown').remove();
                }
            }
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

            mediator.trigger("grid_load:complete", this.collection, $el);
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
            if (this.loadingMask) {
                this.loadingMask.dispose();
            }
            this.loadingMask = new LoadingMaskView({
                container: this.$(this.selectors.loadingMaskContainer)
            });
        },

        /**
         * Define no data block.
         */
        _defineNoDataBlock: function () {
            var placeholders = {entityHint: (this.entityHint || __('oro.datagrid.entityHint')).toLowerCase()},
                message = _.isEmpty(this.collection.state.filters) ?
                        'oro.datagrid.no.entities' : 'oro.datagrid.no.results';
            message = this.noColumnsFlag ? 'oro.datagrid.no.columns' : message;

            this.$(this.selectors.noDataBlock).html($(this.noDataTemplate({
                hint: __(message, placeholders).replace('\n', '<br />')
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
                mediator.execute('layout:init', this.$el, this);
                this.reflow();
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
                this.$el.removeClass('no-data-visible');
            } else {
                this.$(this.selectors.grid).hide();
                this.$(this.selectors.toolbar).hide();
                this.$(this.selectors.filterBox).hide();
                this.$(this.selectors.noDataBlock).show();
                this.$el.addClass('no-data-visible');
            }
        },

        /**
         * Triggers when collection "remove" event fired
         *
         * @private
         */
        _onRemove: function (model) {
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
        },


        getAvailableHeight: function () {
            return mediator.execute('layout:getAvailableHeight', this.$(this.selectors.grid));
        },

        checkLayout: function () {
            if (!this.enableFullScreenLayout) {
                this.setLayout('default');
                // do nothing
                return;
            }
            this.setLayout(mediator.execute('layout:getPreferredLayout', this.$(this.selectors.grid)));
        },

        setLayout: function (newLayout) {
            if (newLayout === this.layout) {
                this.reflow();
                return;
            }
            this.layout = newLayout;
            var $table = this.$(this.selectors.grid);
            switch (newLayout) {
                case 'fullscreen':
                    mediator.execute('layout:disablePageScroll', $table);
                    this.setFloatThead(true);
                    break;
                case 'scroll':
                case 'default':
                    this.setFloatThead(false);
                    mediator.execute('layout:enablePageScroll', $table);
                    break;
                default:
                    throw new Error('Unknown grid layout');
            }
        }
    });

    return Grid;
});
