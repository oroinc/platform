/*jslint nomen: true, vars: true*/
/*global define*/
define(function (require) {
    'use strict';

    var Grid,
        $ = require('jquery'),
        _ = require('underscore'),
        Backbone = require('backbone'),
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
        ExportAction = require('oro/datagrid/action/export-action'),
        tools = require('oroui/js/tools');

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
            '<div class="other-scroll-container">' +
                '<div class="other-scroll"><div></div></div>' +
                '<div class="container-fluid grid-scrollable-container">' +
                    '<div class="grid-container">' +
                        '<table class="grid table-hover table table-bordered table-condensed"></table>' +
                    '</div>' +
                '</div>' +
                '<div class="no-data"></div>' +
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
         * @property {bool} becomes true if dropdown is opened when floatThead enabled
         */
        dropdownOpened: false,

        /**
         * @property {array} contains an array of currently opened dropdowns when floatThead enabled
         * NOTE: All instances share this property
         */
        dropdownsToReset: [],

        /**
         * Interval id of height fix check function
         * @private
         */
        heightFixIntervalId: null,

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

            this.listenTo(mediator, 'layout:reposition', this.updateLayout, this);
            this.reposition = _.bind(this.reposition, this);
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            var subviews;
            if (this.disposed) {
                return;
            }

            this.setFloatThead(false);
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
                    if (!self.disposed) {
                        self._afterRequest();
                    }
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

            this.$grid = this.$(this.selectors.grid);

            this.renderToolbar();
            this.renderGrid();
            this.renderNoDataBlock();
            this.renderLoadingMask();

            this.listenTo(this.collection, 'reset', this.renderNoDataBlock);

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
            mediator.execute('layout:init', this.$el, this);

            this.updateLayout();

            return this;
        },

        /**
         * Returns css expression to set what could be used as height of some block
         * @returns {string}
         */
        getCssHeightCalcExpression: function () {
            var documentHeight = $(document).height(),
                availableHeight = mediator.execute('layout:getAvailableHeight',
                    this.$grid.parents('.grid-scrollable-container:first'));
            return 'calc(100vh - ' + (documentHeight - availableHeight) + 'px)';
        },

        /**
         * Reflows floatThead dependent grid parts.
         * Must be called on resize.
         */
        reflow: function () {
            if (!this.floatThead) {
                return;
            }
            this.setupCache();
            this.fixHeaderCellWidth();
        },

        setupCache: function () {
            this.documentHeight = $(document).height();
            this.cachedEls = {
                gridContainer: this.$grid.parent(),
                headerCells: this.$grid.find('th:first').parent().find('th'),
                firstRowCells: this.$grid.find('tbody tr:not(.thead-sizing-row):first td'),
                otherScrollContainer: this.$grid.parents('.other-scroll-container'),
                thead: this.$grid.find('thead:first'),
                theadTr: this.$grid.find('thead:first tr:first')
            }
        },

        /**
         * Enables or disables float thead support
         *
         * @param newValue {boolean}
         */
        setFloatThead: function (newValue) {
            var self = this;
            if (newValue !== this.floatThead) {
                this.floatThead = newValue;
                if (newValue) {
                    this.addFloatThead();
                    this.heightFixIntervalId = setInterval(_.bind(this.fixHeightInFloatTheadMode, this), 400);
                } else {
                    self.removeFloatThead();
                    clearInterval(this.heightFixIntervalId);
                }
            }
        },

        fixHeaderCellWidth: function () {
            var headerCells = this.cachedEls.headerCells,
                firstRowCells = this.cachedEls.firstRowCells,
                totalWidth = 0,
                self = this,
                scrollBarWidth = mediator.execute('layout:scrollbarWidth');
            // remove style
            headerCells.attr('style', '');
            firstRowCells.attr('style', '');
            this.$grid.css({width: ''});
            this.cachedEls.gridContainer.css({width: ''});
            if (this.scrollVisible) {
                this.$grid.css({borderRight: scrollBarWidth + 'px solid darkblue'});
            }
            this.$el.removeClass('floatThead');

            // copy widths
            headerCells.each(function (i, headerCell) {
                var cellWidth = headerCell.offsetWidth;
                if (self.scrollVisible && i === headerCells.length - 1) {
                    cellWidth += scrollBarWidth;
                }
                totalWidth += cellWidth;
                headerCell.style.minWidth = headerCell.style.width = cellWidth + 'px';
                headerCell.style.boxSizing = 'border-box';
                if (firstRowCells[i]) {
                    firstRowCells[i].style.minWidth = firstRowCells[i].style.width = cellWidth + 'px';
                    firstRowCells[i].style.boxSizing = 'border-box';
                }
            });

            this.$grid.css({borderRight: 'none'});
            this.$el.addClass('floatThead');
            this.$grid.css({
                width: totalWidth
            });
            this.cachedEls.gridContainer.css({
                width: totalWidth
            });

            this.reposition();
        },

        reposition: function () {
            // get gridRect
            var tableRect = this.cachedEls.gridContainer[0].getBoundingClientRect(),
                visibleRect = this.getVisibleRect(this.cachedEls.gridContainer[0]),
                mode = 'default';
            if (visibleRect.top !== tableRect.top || this.layout === 'fullscreen') {
                mode = 'fixed';
            }
            this.setFloatTheadMode(mode, visibleRect, tableRect);
            // update lastClientRect to prevent calling this function again
            this.lastClientRect = this.cachedEls.otherScrollContainer[0].getBoundingClientRect();
            if (this.rescrollCb) {
                this.rescrollCb();
            }
        },

        setFloatTheadMode: function (mode, visibleRect, tableRect) {
            var theadRect, sizingThead;
            // pass this argument to avoid expensive calculations
            if (!visibleRect) {
                visibleRect = this.getVisibleRect(this.cachedEls.gridContainer[0]);
            }
            if (!tableRect) {
                tableRect = this.cachedEls.gridContainer[0].getBoundingClientRect();
            }
            switch (mode) {
                case 'relative':
                    // works well with dropdowns, but causes jumps while scrolling
                    if (this.currentFloatTheadMode !== mode) {
                        this.$el.removeClass('floatThead-fixed');
                        this.$el.addClass('floatThead-relative');
                        if (!this.$grid.find('.thead-sizing').length) {
                            sizingThead = this.cachedEls.thead.clone().addClass('thead-sizing');
                            sizingThead.find('th').attr('style', '');
                            sizingThead.insertAfter(this.cachedEls.thead);
                        }
                    }
                    this.cachedEls.thead.css({
                        top: visibleRect.top - tableRect.top
                    });
                    theadRect = this.cachedEls.thead[0].getBoundingClientRect();
                    this.cachedEls.theadTr.css({
                        marginLeft: tableRect.left - theadRect.left
                    });
                    break;
                case 'fixed':
                    // provides good scroll experience
                    if (this.currentFloatTheadMode !== mode) {
                        this.$el.removeClass('floatThead-relative');
                        this.$el.addClass('floatThead-fixed');
                        this.$grid.find('thead:first .dropdown.open').removeClass('open');
                        if (!this.$grid.find('.thead-sizing').length) {
                            sizingThead = this.cachedEls.thead.clone().addClass('thead-sizing');
                            sizingThead.find('th').attr('style', '');
                            sizingThead.insertAfter(this.cachedEls.thead);
                        }
                    }
                    this.cachedEls.thead.css({
                        // show only visible part
                        top: visibleRect.top,
                        width: visibleRect.right - visibleRect.left,
                        height: Math.min(this.headerHeight, visibleRect.bottom - visibleRect.top),

                        // left side should be also tracked
                        // gives incorrect rendering when "document" scrolled horizontally
                        left: visibleRect.left
                    });
                    theadRect = this.cachedEls.thead[0].getBoundingClientRect();
                    this.cachedEls.theadTr.css({
                        // possible solution set scrollLeft instead
                        // could be more fast for rendering
                        marginLeft: tableRect.left - theadRect.left
                    });
                    break;
                default:
                    if (this.currentFloatTheadMode !== mode) {
                        this.$grid.find('.thead-sizing').remove();
                        this.$el.removeClass('floatThead-relative floatThead-fixed');
                        // remove extra styles
                        this.cachedEls.thead.attr('style', '');
                        this.cachedEls.theadTr.attr('style', '');
                        // cleanup
                    }
                    break;
            }
            this.currentFloatTheadMode = mode;
        },

        addFloatThead: function () {
            this.setupCache();
            this.rescrollCb = this.rescroll();
            this.headerHeight = this.cachedEls.theadTr.height();
            this.fixHeaderCellWidth();
            this.$grid.on('click', 'thead:first .dropdown', _.bind(function () {
                this.setFloatTheadMode('relative');
            }, this));
            this.cachedEls.gridContainer.parents().add(document).on('scroll', this.reposition);
        },

        removeFloatThead: function () {
            this.setFloatTheadMode('default');
            this.$grid.parents().add(document).off('scroll', this.reposition);
            // remove css
            this.cachedEls.headerCells.attr('style', '');
            this.cachedEls.firstRowCells.attr('style', '');
        },

        rescroll: function () {
            var self = this,
                scrollContainer = this.$('.grid-scrollable-container'),
                otherScroll = this.$('.other-scroll'),
                otherScrollInner = this.$('.other-scroll > div'),
                scrollBarWidth = mediator.execute('layout:scrollbarWidth'),
                scrollStateModel = new Backbone.Model(),
                heightDec;

            if (scrollBarWidth === 0) {
                // nothing to do
                return _.noop;
            }

            scrollStateModel.on('change:headerHeight', function (model, val) {
                heightDec = val + 1; // compensate border
                otherScroll.css({
                    width: scrollBarWidth,
                    marginTop: heightDec
                });
                scrollStateModel.trigger('change:scrollHeight', scrollStateModel, scrollContainer[0].scrollHeight);
                scrollStateModel.trigger('change:clientHeight', scrollStateModel, scrollContainer[0].clientHeight);
            }, this);
            scrollStateModel.on('change:scrollVisible', function (model, val) {
                scrollContainer.css({
                    width: 'calc(100% + ' + (val ? scrollBarWidth : 0) + 'px)'
                });
                otherScroll.css({
                    display: val ? 'block' : 'none'
                });
                this.reflow();
            }, this);
            scrollStateModel.on('change:clientHeight', function (model, val) {
                otherScroll.css({
                    height: val - heightDec
                });
            }, this);
            scrollStateModel.on('change:clientWidth', function (model, val) {
                otherScroll.css({
                    marginLeft: val - scrollBarWidth
                });
            }, this);
            scrollStateModel.on('change:scrollHeight', function (model, val) {
                otherScrollInner.css({
                    height: val - heightDec
                });
            });
            scrollStateModel.on('change:scrollTop', function (model, val) {
                otherScroll.scrollTop(val);
            }, this);
            function setup() {
                scrollStateModel.set({
                    headerHeight: self.headerHeight
                });
                self.scrollVisible = scrollContainer[0].clientHeight + 1 /*IE fix*/ < scrollContainer[0].scrollHeight;
                scrollStateModel.set({
                    scrollVisible: self.scrollVisible,
                    scrollHeight:  scrollContainer[0].scrollHeight,
                    clientHeight:  scrollContainer[0].clientHeight,
                    clientWidth:   scrollContainer[0].clientWidth,
                    scrollTop:     scrollContainer[0].scrollTop
                });
            }
            scrollContainer.on('scroll', setup);
            otherScroll.on('scroll', function () {
                var mainScrollTop = scrollContainer.scrollTop(),
                    otherScrollTop = otherScroll.scrollTop();
                if (mainScrollTop !== otherScrollTop) {
                    scrollContainer.scrollTop(otherScroll.scrollTop());
                    if (self.currentFloatTheadMode === 'relative') {
                        self.reposition();
                    }
                }
            });
            setup();
            return setup;
        },

        fixHeightInFloatTheadMode: function () {
            var currentClientRect = this.cachedEls.otherScrollContainer[0].getBoundingClientRect();
            if (!this.lastClientRect || (this.lastClientRect.top !== currentClientRect.top ||
                this.lastClientRect.left !== currentClientRect.left ||
                this.lastClientRect.right !== currentClientRect.right)) {
                if (this.layout === 'fullscreen') {
                    // adjust max height
                    this.$grid.parents('.grid-scrollable-container').css({
                        maxHeight: this.getCssHeightCalcExpression()
                    });
                }
                if (!this.lastClientRect || (this.lastClientRect.left !== currentClientRect.left ||
                    this.lastClientRect.right !== currentClientRect.right)) {
                    this.reflow();
                } else {
                    this.reposition();
                }
            }
            this.lastClientRect = currentClientRect;
        },

        /**
         *
         * @param el
         * @returns {{top: number, left: Number, bottom: Number, right: Number}}
         */
        getVisibleRect: function (el) {
            var current = el,
                tableRect = current.getBoundingClientRect(),
                midRect = tableRect,
                borders,
                resultRect = {
                    top: midRect.top - this.headerHeight,
                    left: midRect.left,
                    bottom: midRect.bottom,
                    right: midRect.right
                };
            if (
                (resultRect.top === 0 && resultRect.bottom === 0) || // no-data block is shown
                (resultRect.top > this.documentHeight && this.currentFloatTheadMode === 'default') // grid is invisible
                ) {
                // no need to calculate anything
                return resultRect;
            }
            current = current.parentNode;
            while (current !== document.documentElement) {
                midRect = current.getBoundingClientRect();
                borders = $.fn.getBorders(current);

                // console.log(current, current.id, midRect);

                if (tools.isMobile()) {
                    /**
                     * Equals header height. Cannot calculate dynamically due to issues on ipad
                     */
                    if (resultRect.top < 54 && current.id === 'top-page') {
                        resultRect.top = 54;
                    } else if (resultRect.top < 44 && current.className === 'widget-content') {
                        resultRect.top = 44;
                    }

                }

                if (resultRect.top < midRect.top + borders.top) {
                    resultRect.top = midRect.top + borders.top;
                }
                if (resultRect.bottom > midRect.bottom - borders.bottom) {
                    resultRect.bottom = midRect.bottom - borders.bottom;
                }
                if (resultRect.left < midRect.left + borders.left) {
                    resultRect.left = midRect.left + borders.left;
                }
                if (resultRect.right > midRect.right - borders.right) {
                    resultRect.right = midRect.right - borders.right;
                }
                current = current.parentNode;
            }

            return resultRect;
        },

        /**
         * Renders the grid's header, then footer, then finally the body.
         */
        renderGrid: function () {
            this.$grid.append(this.header.render().$el);
            if (this.footer) {
                this.$grid.append(this.footer.render().$el);
            }
            this.$grid.append(this.body.render().$el);

            mediator.trigger('grid_load:complete', this.collection, this.$grid);
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
            })));
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
                mediator.trigger('grid_load:complete', this.collection, this.$el);
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
            this.$el.toggleClass('no-data-visible', this.collection.models.length <= 0  || this.noColumnsFlag);
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

        /**
         * Chooses layout on resize or during creation
         */
        updateLayout: function () {
            if (!this.$grid.parents('body').length) {
                // not ready to apply layout
                // try to do that at next js cycle1
                _.defer(_.bind(this.updateLayout, this));
                return;
            }
            this.setFloatThead(true);
            var layout = 'default';
            if (this.enableFullScreenLayout) {
                layout = mediator.execute('layout:getPreferredLayout', this.$grid);
            }
            this.setLayout(layout);
        },

        /**
         * Sets layout and perform all required operations
         */
        setLayout: function (newLayout) {
            if (newLayout === this.layout) {
                if (newLayout === 'fullscreen') {
                    this.$grid.parents('.grid-scrollable-container').css({
                        maxHeight: this.getCssHeightCalcExpression()
                    });
                    this.reflow();
                }
                return;
            }
            this.layout = newLayout;
            switch (newLayout) {
                case 'fullscreen':
                    this.$grid.parents('.grid-scrollable-container').css({
                        maxHeight: this.getCssHeightCalcExpression()
                    });
                    mediator.execute('layout:disablePageScroll', this.$el);
                    break;
                case 'scroll':
                case 'default':
                    this.$grid.parents('.grid-scrollable-container').css({
                        maxHeight: ''
                    });
                    mediator.execute('layout:enablePageScroll', this.$el);
                    break;
                default:
                    throw new Error('Unknown grid layout');
            }
        }
    });

    return Grid;
});
