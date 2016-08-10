define([
    'underscore',
    'jquery',
    'backgrid',
    'oroui/js/tools/text-util'
], function(_, $, Backgrid, textUtil) {
    'use strict';

    var HeaderCell;

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
                '<a class="grid-header-cell-link" href="#">' +
                    '<span class="grid-header-cell-label"><%- label %></span>' +
                    '<span class="caret"></span>' +
                '</a>' +
            '<% } else { %>' +
                '<span class="grid-header-cell-label-container">' +
                    '<span class="grid-header-cell-label"><%- label %></span>' +
                '</span>' +
            '<% } %>'
        ),

        /** @property {Boolean} */
        allowNoSorting: true,

        /** @property {Number} */
        minWordsToAbbreviate: 4,

        keepElement: false,

        events: {
            mouseover: 'onMouseOver',
            mouseout: 'onMouseOut',
            click: 'onClick'
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
                    this.column.set({'direction': direction});
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
            var abbreviation = textUtil.abbreviate(label, this.minWordsToAbbreviate);

            this.isLabelAbbreviated = abbreviation !== label;

            this.$el.toggleClass('abbreviated', this.isLabelAbbreviated);

            this.$el.append(this.template({
                label: abbreviation,
                sortable: this.column.get('sortable')
            }));

            if (this.column.has('width')) {
                this.$el.width(this.column.get('width'));
            }

            if (!_.isFunction(this.column.attributes.cell.prototype.className)) {
                this.$el.addClass(this.column.attributes.cell.prototype.className);
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

        onMouseOver: function() {
            var _this = this;
            var $label = this.$('.grid-header-cell-label');

            // measure text content
            var realWidth = $label[0].clientWidth;
            $label.css({overflow: 'visible'});
            var fullWidth = $label[0].clientWidth;
            $label.css({overflow: ''});

            if (!this.isLabelAbbreviated && fullWidth === realWidth) {
                // hint is not required all text is visible
                return;
            }

            this.hintTimeout = setTimeout(function addHeaderCellHint() {
                $(document.body).append('<span class="grid-header-cell-hint">' +
                    _.escape(_this.column.get('label')) + '</span>');
                var hint = $('body > .grid-header-cell-hint');
                hint.position({
                    my: 'left top',
                    at: 'left bottom+5',
                    of: $label
                });
            }, 300);
        },

        onMouseOut: function() {
            clearTimeout(this.hintTimeout);
            $('body > .grid-header-cell-hint').remove();
        }
    });

    return HeaderCell;
});
