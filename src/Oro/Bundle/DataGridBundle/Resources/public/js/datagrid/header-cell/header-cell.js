define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backgrid = require('backgrid');
    const textUtil = require('oroui/js/tools/text-util');
    const HintView = require('orodatagrid/js/app/views/hint-view');
    const template = require('tpl-loader!orodatagrid/templates/datagrid/grid-header-cell.html');

    /**
     * Datagrid header cell
     *
     * @export  orodatagrid/js/datagrid/header-cell/header-cell
     * @class   orodatagrid.datagrid.headerCell.HeaderCell
     * @extends Backgrid.HeaderCell
     */
    const HeaderCell = Backgrid.HeaderCell.extend({
        /** @property */
        template: template,

        /** @property {Boolean} */
        allowNoSorting: true,

        /** @property {Number} */
        minWordsToAbbreviate: 4,

        keepElement: false,

        events: {
            mouseenter: 'onMouseEnter',
            mouseleave: 'onMouseLeave',
            focusin: 'onFocusin',
            focusout: 'onFocusout',
            click: 'onClick'
        },

        /**
         * @inheritdoc
         */
        constructor: function HeaderCell(options) {
            HeaderCell.__super__.constructor.call(this, options);
        },

        /**
         * Initialize.
         *
         * Add listening "reset" event of collection to able catch situation when
         * header cell should update it's sort state.
         */
        initialize: function(options) {
            this.allowNoSorting = this.collection.multipleSorting;
            HeaderCell.__super__.initialize.call(this, options);
            this._initCellDirection(this.collection);
            this.listenTo(this.collection, 'reset', this._initCellDirection);
            this.listenTo(this.column, 'change:direction', this._updateAttributes);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.column;
            HeaderCell.__super__.dispose.call(this);
        },

        /**
         * There is no need to reset cell direction because of multiple sorting
         *
         * @private
         */
        _resetCellDirection: function() {},

        /**
         * There is no need to reset cell direction because of multiple sorting
         *
         * @private
         */
        removeCellDirection: function() {},

        /**
         * Inits cell direction when collections loads first time.
         *
         * @param collection
         * @private
         */
        _initCellDirection: function(collection) {
            if (collection === this.collection) {
                const state = collection.state;
                let direction = null;
                const columnName = this.column.get('name');
                if (this.column.get('sortable') && _.has(state.sorters, columnName)) {
                    if (1 === parseInt(state.sorters[columnName], 10)) {
                        direction = 'descending';
                    } else if (-1 === parseInt(state.sorters[columnName], 10)) {
                        direction = 'ascending';
                    }
                }

                if (direction !== this.column.get('direction')) {
                    this.column.set({direction: direction});
                }

                if (this.column.get('sortable')) {
                    this._updateAttributes();
                }
            }
        },

        /**
         * Renders a header cell with a sorter and a label.
         *
         * @return {*}
         */
        render: function() {
            this.$el.empty();

            let label = this.column.get('label');

            if (this.column.get('shortenableLabel') !== false) {
                label = textUtil.abbreviate(label, this.minWordsToAbbreviate);
                this.isLabelAbbreviated = label !== this.column.get('label');
                if (!this.isLabelAbbreviated) {
                    // if abbreviation was not created -- add class to make label shorten over styles
                    this.$el.addClass('shortenable-label');
                }
            }

            this.$el.append(this.template({
                label: label,
                sortable: this.column.get('sortable')
            }));

            if (this.column.has('width')) {
                this.$el.width(this.column.get('width'));
            }

            const cell = this.column.get('oldCell') || this.column.get('cell');
            if (!_.isFunction(cell.prototype.className)) {
                this.$el.addClass(cell.prototype.className);
            }

            if (this.column.has('align')) {
                this.$el.removeClass('align-left align-center align-right');
                this.$el.addClass('align-' + this.column.get('align'));
            }

            if (this.isLabelAbbreviated) {
                this.$('[data-grid-header-cell-label]').attr('aria-label', this.column.get('label'));
                this.$('[data-grid-header-cell-text]').attr('aria-hidden', true);
            }

            return this;
        },

        /**
         * Click on column name to perform sorting
         *
         * @param {Event} e
         */
        onClick: function(e) {
            e.preventDefault();

            const column = this.column;
            const collection = this.collection;
            const event = 'backgrid:sort';

            const cycleSort = (header, col) => {
                if (column.get('direction') === 'ascending') {
                    collection.trigger(event, col, 'descending');
                } else if (this.allowNoSorting && column.get('direction') === 'descending') {
                    collection.trigger(event, col, null);
                } else {
                    collection.trigger(event, col, 'ascending');
                }
            };

            const toggleSort = function(header, col) {
                if (column.get('direction') === 'ascending') {
                    collection.trigger(event, col, 'descending');
                } else {
                    collection.trigger(event, col, 'ascending');
                }
            };

            const sortable = Backgrid.callByNeed(column.sortable(), column, this.collection);
            if (sortable) {
                const sortType = column.get('sortType');
                if (sortType === 'toggle') {
                    toggleSort(this, column);
                } else {
                    cycleSort(this, column);
                }
            }
        },

        /**
         * Mouse Enter on column name to show hint if label has been abbreviated
         *
         * @param {Event} e
         */
        onMouseEnter(e) {
            this.isHovered = true;
            this.showHint();
        },

        /**
         * Mouse Leave from column name to hide hint
         *
         * @param {Event} e
         */
        onMouseLeave(e) {
            delete this.isHovered;
            if (!this.isFocused) {
                this.hideHint();
            }
        },

        /**
         * Focusin on column name to show hint if label has been abbreviated
         *
         * @param {Event} e
         */
        onFocusin(e) {
            this.isFocused = true;
            this.showHint();
        },

        /**
         * Focus from column name to hide hint
         *
         * @param {Event} e
         */
        onFocusout(e) {
            delete this.isFocused;
            if (!this.isHovered) {
                this.hideHint();
            }
        },

        showHint() {
            if (!this.isLabelAbbreviated || this.hintTimeout || this.subview('hint')) {
                return;
            }

            this.subview('hint', new HintView({
                el: this.$('[data-grid-header-cell-label]'),
                offsetOfEl: this.$el,
                autoRender: true,
                popoverConfig: {
                    content: this.column.get('label')
                }
            }));

            this.hintTimeout = setTimeout(function() {
                const hint = this.subview('hint');

                if (hint && (this.isLabelAbbreviated || !hint.fullLabelIsVisible())) {
                    this.subview('hint').show();
                }
            }.bind(this), 300);
        },

        hideHint() {
            clearTimeout(this.hintTimeout);
            delete this.hintTimeout;
            if (this.subview('hint')) {
                this.removeSubview('hint');
            }
        },

        _updateAttributes() {
            if (this.disposed) {
                return;
            }
            this._setAttributes(this._collectAttributes());
        },

        _attributes() {
            const direction = this.column.get('direction');
            return {
                'aria-sort': direction ? direction : 'none'
            };
        }
    });

    return HeaderCell;
});
