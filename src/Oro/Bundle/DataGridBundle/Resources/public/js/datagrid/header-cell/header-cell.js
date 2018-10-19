define(function(require) {
    'use strict';

    var HeaderCell;
    var _ = require('underscore');
    var Backgrid = require('backgrid');
    var textUtil = require('oroui/js/tools/text-util');

    /**
     * Datagrid header cell
     *
     * @export  orodatagrid/js/datagrid/header-cell/header-cell
     * @class   orodatagrid.datagrid.headerCell.HeaderCell
     * @extends Backgrid.HeaderCell
     */
    HeaderCell = Backgrid.HeaderCell.extend({

        /** @property */
        template: _.template(
            '<% if (sortable) { %>' +
                '<a class="grid-header-cell__link" href="#" role="button">' +
                    '<span class="grid-header-cell__label"><%- label %></span>' +
                    '<span class="caret" aria-hidden="true"></span>' +
                '</a>' +
            '<% } else { %>' +
                '<span class="grid-header-cell__label-container">' +
                    '<span class="grid-header-cell__label"><%- label %></span>' +
                '</span>' +
            '<% } %>'
        ),

        /** @property {Boolean} */
        allowNoSorting: true,

        /** @property {Number} */
        minWordsToAbbreviate: 4,

        keepElement: false,

        events: {
            mouseenter: 'onMouseEnter',
            mouseleave: 'onMouseLeave',
            click: 'onClick'
        },

        /**
         * @inheritDoc
         */
        constructor: function HeaderCell() {
            HeaderCell.__super__.constructor.apply(this, arguments);
        },

        /**
         * Initialize.
         *
         * Add listening "reset" event of collection to able catch situation when
         * header cell should update it's sort state.
         */
        initialize: function() {
            this.allowNoSorting = this.collection.multipleSorting;
            HeaderCell.__super__.initialize.apply(this, arguments);
            this._initCellDirection(this.collection);
            this.listenTo(this.collection, 'reset', this._initCellDirection);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.column;
            HeaderCell.__super__.dispose.apply(this, arguments);
        },

        /**
         * There is no need to reset cell direction because of multiple sorting
         *
         * @private
         */
        _resetCellDirection: function() {},

        /**
         * Inits cell direction when collections loads first time.
         *
         * @param collection
         * @private
         */
        _initCellDirection: function(collection) {
            if (collection === this.collection) {
                var state = collection.state;
                var direction = null;
                var columnName = this.column.get('name');
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
            }
        },

        /**
         * Renders a header cell with a sorter and a label.
         *
         * @return {*}
         */
        render: function() {
            this.$el.empty();

            var label = this.column.get('label');

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

            var cell = this.column.get('oldCell') || this.column.get('cell');
            if (!_.isFunction(cell.prototype.className)) {
                this.$el.addClass(cell.prototype.className);
            }

            if (this.column.has('align')) {
                this.$el.removeClass('align-left align-center align-right');
                this.$el.addClass('align-' + this.column.get('align'));
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

            var column = this.column;
            var collection = this.collection;
            var event = 'backgrid:sort';

            var cycleSort = _.bind(function(header, col) {
                if (column.get('direction') === 'ascending') {
                    collection.trigger(event, col, 'descending');
                } else if (this.allowNoSorting && column.get('direction') === 'descending') {
                    collection.trigger(event, col, null);
                } else {
                    collection.trigger(event, col, 'ascending');
                }
            }, this);

            var toggleSort = function(header, col) {
                if (column.get('direction') === 'ascending') {
                    collection.trigger(event, col, 'descending');
                } else {
                    collection.trigger(event, col, 'ascending');
                }
            };

            var sortable = Backgrid.callByNeed(column.sortable(), column, this.collection);
            if (sortable) {
                var sortType = column.get('sortType');
                if (sortType === 'toggle') {
                    toggleSort(this, column);
                } else {
                    cycleSort(this, column);
                }
            }
        },

        /**
         * Mouse Enter on column name to show popover
         *
         * @param {Event} e
         */
        onMouseEnter: function(e) {
            var $label = this.$('.grid-header-cell__label');

            // measure text content
            var realWidth = $label[0].clientWidth;
            $label.css({overflow: 'visible'});
            var fullWidth = $label[0].clientWidth;
            $label.css({overflow: ''});

            if (!this.isLabelAbbreviated && fullWidth === realWidth) {
                // hint is not required all text is visible
                return;
            }

            this.popoverAdded = true;
            $label.popover({
                content: this.column.get('label'),
                trigger: 'manual',
                placement: 'bottom',
                animation: false,
                container: 'body',
                offset: this.calcPopoverOffset(),
                template: '<div class="popover" role="tooltip">' +
                              '<div class="arrow"></div>' +
                              '<h3 class="popover-header"></h3>' +
                              '<div class="popover-body popover-no-close-button"></div>' +
                          '</div>'
            });

            this.hintTimeout = setTimeout(function addHeaderCellHint() {
                $label.popover('show');
            }, 300);
        },

        /**
         * Mouse Leave from column name to hide popover
         *
         * @param {Event} e
         */
        onMouseLeave: function(e) {
            clearTimeout(this.hintTimeout);
            var $label = this.$('.grid-header-cell__label');
            $label.popover('hide');
            $label.popover('dispose');
            this.popoverAdded = false;
        },

        /**
         * Calculation offset of column label for popover
         *
         * @return {String}
         */
        calcPopoverOffset: function() {
            var x = 0;
            var y = 0;
            var $label = this.$('.grid-header-cell__label');

            var elBottom = this.$el[0].getBoundingClientRect().bottom;
            var labelBottom = $label[0].getBoundingClientRect().bottom;

            y = elBottom - labelBottom;

            return [x, y].join(', ');
        }
    });

    return HeaderCell;
});
