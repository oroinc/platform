define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const Chaplin = require('chaplin');
    const Backbone = require('backbone');
    const tools = require('oroui/js/tools');
    const ColumnRendererComponent = require('../app/components/column-renderer-component');

    const document = window.document;

    // Cached regex to split keys for `delegate`.
    const delegateEventSplitter = /^(\S+)\s*(.*)$/;

    /**
     * Grid row.
     *
     * Triggers events:
     *  - "clicked" when row is clicked
     *
     * @export  orodatagrid/js/datagrid/row
     * @class   orodatagrid.datagrid.Row
     * @extends Chaplin.CollectionView
     */
    const Row = Chaplin.CollectionView.extend({
        tagName: 'tr',

        autoRender: false,

        animationDuration: 0,

        /**
         * Override Chaplin delegate events to use events as function
         * This code supports perfomance fix.
         */
        delegateEvents: Backbone.View.prototype.delegateEvents,

        events: function() {
            const resultEvents = {};

            const events = this.collection.getCellEventList().getEventsMap();
            // prevent CS error 'cause we must completely repeat Backbone behaviour
            // eslint-disable-next-line guard-for-in
            for (const key in events) {
                const match = key.match(delegateEventSplitter);
                const eventName = match[1];
                const selector = match[2];
                resultEvents[eventName + ' td' + (selector ? ' ' + selector : '')] =
                    _.partial(this.delegateEventToCell, key);
            }

            // the order is important, please do not move up
            _.extend(resultEvents, {
                mousedown: 'onMouseDown',
                mouseleave: 'onMouseLeave',
                mouseup: 'onMouseUp',
                click: 'onClick'
            });
            return resultEvents;
        },

        DOUBLE_CLICK_WAIT_TIMEOUT: 170,

        template: null,

        themeOptions: {
            view: '',
            optionPrefix: 'row',
            className: 'grid-row',
            actionSelector: ''
        },

        /**
         * @inheritdoc
         */
        constructor: function Row(options) {
            _.extend(this, _.pick(options, ['rowClassName', 'themeOptions', 'template', 'columns',
                'dataCollection', 'ariaRowsIndexShift']));
            Row.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize(options) {
            // let descendants override itemView
            if (!this.itemView) {
                // itemView function is called as new this.itemView
                // it is placed here to pass THIS within closure
                const rowView = this;
                this.itemView = function(options) {
                    const column = options.model;
                    const cellOptions = rowView.getConfiguredCellOptions(column);
                    cellOptions.model = rowView.model;
                    const Cell = column.get('cell');
                    return new Cell(cellOptions);
                };
            }

            // code related to simplified event binding
            const cellEvents = this.collection.getCellEventList();
            this.listenTo(cellEvents, 'change', this.delegateEvents);

            this.listenTo(this.model, 'backgrid:selected', this.onBackgridSelected);
            this.listenTo(this.model, 'change:row_class_name', this.onRowClassNameChanged);
            this.listenTo(this.model, 'change:isNew', this.onRowNewStatusChange);
            this.listenTo(this.dataCollection, 'add remove reset', this._updateAttributes);
            this.listenTo(this, 'visibilityChange', this.onVisibilityChange);

            this.columnRenderer = new ColumnRendererComponent(options);

            Row.__super__.initialize.call(this, options);
            this.cells = this.subviews;
        },

        initItemView(model) {
            const column = model;
            const cell = Row.__super__.initItemView.call(this, model);

            cell.$el.attr({
                'data-column-label': column.get('label')
            });
            if (column.has('align')) {
                cell.$el.removeClass('align-left align-center align-right');
                cell.$el.addClass('align-' + column.get('align'));
            }
            if (!_.isUndefined(cell.skipRowClick) && cell.skipRowClick) {
                cell.$el.addClass('skip-row-click');
            }
            return cell;
        },

        getConfiguredCellOptions: function(column) {
            let cellOptions = column.get('cellOptions');
            const bodyCellClassName = column.get('cellClassName') || '';

            if (!cellOptions) {
                cellOptions = {
                    column: column,
                    themeOptions: {
                        optionPrefix: 'cell',
                        className: `${bodyCellClassName.trim()} grid-cell grid-body-cell`
                    }
                };
                if (column.get('name')) {
                    cellOptions.themeOptions.className += ' grid-body-cell-' + column.get('name');
                }
                const Cell = column.get('cell');
                this.columns.trigger('configureInitializeOptions', Cell, cellOptions);
                column.set({
                    cellOptions: cellOptions
                });
            }
            return cellOptions;
        },

        /**
         * Run event handler on cell
         */
        delegateEventToCell: function(key, e) {
            const tdEl = $(e.target).closest('td, th')[0];

            for (let i = 0; i < this.subviews.length; i++) {
                const view = this.subviews[i];
                if (view.el === tdEl && view.events) {
                    // events cannot be function
                    // this kind of cell views are filtered in CellEventList.getEventsMap()
                    const events = view.events;
                    if (key in events) {
                        // run event
                        let method = events[key];
                        if (!_.isFunction(method)) {
                            method = view[events[key]];
                        }
                        if (!method) {
                            break;
                        }
                        const oldTarget = e.delegateTarget;
                        e.delegateTarget = tdEl;
                        method.call(view, e);
                        // must stop immediate propagation because of redelegation
                        if (e.isPropagationStopped()) {
                            e.stopImmediatePropagation();
                        }
                        e.delegateTarget = oldTarget;
                    }
                    break;
                }
            }
        },

        /**
         * Handles row "backgrid:selected" event
         *
         * @param model
         * @param isSelected
         */
        onBackgridSelected: function(model, isSelected) {
            if (_.isUndefined(isSelected)) {
                isSelected = false;
            }

            this.$el.toggleClass('row-selected', isSelected);
        },

        onRowNewStatusChange: function(model) {
            this.$el.toggleClass('row-new', model.get('isNew'));
        },

        onVisibilityChange(visibleItems) {
            this.countCellClassName(visibleItems.length);
        },

        onRowClassNameChanged: function(model) {
            const previousClass = model.previous('row_class_name');
            const newClass = _.result(this, 'className');
            if (previousClass) {
                this.$el.removeClass(previousClass);
            }
            if (newClass) {
                this.$el.addClass(newClass);
            }
        },

        /**
         * @param {number} cellsCount
         */
        countCellClassName(cellsCount) {
            if (this._cellsCountClassName) {
                this.$el.removeClass(this._cellsCountClassName);
            }

            this._cellsCountClassName = `row-${cellsCount}cells`;
            this.$el.addClass(this._cellsCountClassName);
        },

        className: function() {
            const classes = [];
            if (this.rowClassName) {
                classes.push(this.rowClassName);
            }
            if (this.model.get('row_class_name')) {
                classes.push(this.model.get('row_class_name'));
            }

            return classes.join(' ');
        },

        _attributes: function() {
            return {
                ...this.model.get('row_attributes'),
                'aria-rowindex': this.getAriaRowIndex()
            };
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.clickTimeout) {
                clearTimeout(this.clickTimeout);
            }
            delete this.columns;
            delete this.cells;
            Row.__super__.dispose.call(this);
        },

        onMouseDown: function(e) {
            if (this.clickTimeout) {
                // if timeout is set, it means that user makes double click
                clearTimeout(this.clickTimeout);
                delete this.clickTimeout;
                // prevent second click handler launch
                this.mouseDownSelection = null;
                this.mouseDownTarget = null;
                // prevent text selection on double click
                if ($(e.target).closest('.prevent-text-selection-on-dblclick').length) {
                    e.preventDefault();
                }
                return;
            }
            // remember selection and target
            this.mouseDownSelection = this.getSelectedText();
            this.mouseDownTarget = $(e.target).closest('td');
            this.$el.addClass('mouse-down');
        },

        onMouseLeave: function(e) {
            this.$el.removeClass('mouse-down');
        },

        onMouseUp: function(e) {
            this.clickPermit = false;
            // remember selection and target
            const $target = this.$(e.target);
            if (this.themeOptions.actionSelector) {
                const allowed = this.themeOptions.actionSelector;
                if (!$target.is(allowed) && !$target.parents(allowed).length) {
                    return;
                }
            } else {
                const exclude = 'a:not("[data-include]"), .dropdown, .skip-row-click, :input';
                // if the target is an action element, skip toggling the email
                if ($target.is(exclude) || $target.parents(exclude).length) {
                    return;
                }
            }

            if (this.mouseDownSelection !== this.getSelectedText()) {
                return;
            }

            if (this.mouseDownTarget[0] !== $target.closest('td')[0]) {
                return;
            }

            this.clickPermit = true;
        },

        onClick: function(e) {
            const options = {};
            const clickFunction = () => {
                if (this.disposed) {
                    return;
                }

                this.trigger('clicked', this, options);
                if (this.disposed) {
                    return;
                }

                for (let i = 0; i < this.subviews.length; i++) {
                    const cell = this.subviews[i];
                    if (cell.listenRowClick && _.isFunction(cell.onRowClicked)) {
                        cell.onRowClicked(this, e);
                    }
                }
                this.$el.removeClass('mouse-down');
                delete this.clickTimeout;
            };
            if (!this.clickPermit) {
                return;
            }
            e.preventDefault();
            if (tools.isTargetBlankEvent(e)) {
                options.target = '_blank';
                clickFunction();
                return;
            }
            this.clickTimeout = setTimeout(clickFunction, this.DOUBLE_CLICK_WAIT_TIMEOUT);
        },

        /**
         * Returns selected text is available
         *
         * @return {string}
         */
        getSelectedText: function() {
            let text = '';
            if (_.isFunction(window.getSelection)) {
                text = window.getSelection().toString();
            } else if (!_.isUndefined(document.selection) && document.selection.type === 'Text') {
                text = document.selection.createRange().text;
            }
            return text;
        },

        render: function() {
            this._deferredRender();
            Row.__super__.render.call(this);
            const state = {selected: false};
            this.model.trigger('backgrid:isSelected', this.model, state);
            this.$el.toggleClass('row-selected', state.selected);

            if (this.$el.data('layout') === 'separate') {
                const options = {};
                if (this.$el.data('layout-model')) {
                    options[this.$el.data('layout-model')] = this.model;
                }
                this.initLayout(options).always(() => {
                    this._resolveDeferredRender();
                });
            } else {
                this._resolveDeferredRender();
            }

            return this;
        },

        renderAllItems: function() {
            if (this.template) {
                this.renderCustomTemplate();
            } else {
                return Row.__super__.renderAllItems.call(this);
            }
        },

        renderCustomTemplate: function() {
            const self = this;
            this.$el.html(this.template({
                model: this.model ? this.model.attributes : {},
                themeOptions: this.themeOptions ? this.themeOptions : {},
                render: function(columnName) {
                    const columnModel = _.find(self.columns.models, function(model) {
                        return model.get('name') === columnName;
                    });
                    if (columnModel) {
                        return self.columnRenderer.getHtml(self.renderItem(columnModel).$el);
                    }
                    return '';
                },
                attributes: function(columnName, additionalAttributes) {
                    const attributes = additionalAttributes || {};
                    const columnModel = _.find(self.columns.models, function(model) {
                        return model.get('name') === columnName;
                    });
                    if (columnModel) {
                        return self.columnRenderer.getRawAttributes(self.renderItem(columnModel).$el, attributes);
                    }
                    return '';
                }
            }));
            const $checkbox = this.$('[data-role=select-row]:checkbox');
            if ($checkbox.length) {
                this.listenTo(this.model, 'backgrid:select', function(model, checked) {
                    $checkbox.prop('checked', checked);
                });
                $checkbox.on('change' + this.eventNamespace(), () => {
                    this.model.trigger('backgrid:selected', this.model, $checkbox.prop('checked'));
                });
                $checkbox.on('click' + this.eventNamespace(), function(e) {
                    e.stopPropagation();
                });
            }
            return this;
        },

        /**
         * Sync attributes for view element
         */
        _updateAttributes() {
            if (this.disposed) {
                return;
            }
            this._setAttributes(this._collectAttributes());
        },

        /**
         * @return {null|number}
         */
        getAriaRowIndex() {
            let ariaRowIndex = null;
            const indexInCollection = this.dataCollection
                .filter(model => model.get('isAuxiliary') !== true)
                .findIndex(model => model.cid === this.model.cid);

            if (indexInCollection !== -1) {
                const {currentPage, pageSize} = this.dataCollection.state;
                const indexInPage = (currentPage * pageSize) - pageSize;

                ariaRowIndex = indexInCollection + this.ariaRowsIndexShift + indexInPage + 1;
            }

            return ariaRowIndex;
        }
    });

    return Row;
});
