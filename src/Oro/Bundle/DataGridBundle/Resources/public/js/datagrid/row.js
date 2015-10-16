define([
    'jquery',
    'underscore',
    'backgrid'
], function($, _, Backgrid) {
    'use strict';

    var Row;
    var document = window.document;

    /**
     * Grid row.
     *
     * Triggers events:
     *  - "clicked" when row is clicked
     *
     * @export  orodatagrid/js/datagrid/row
     * @class   orodatagrid.datagrid.Row
     * @extends Backgrid.Row
     */
    Row = Backgrid.Row.extend({

        /** @property */
        events: {
            'click': 'onClick'
        },

        /** @property */
        clickData: {
            counter: 0,
            timeout: 100,
            hasSelectedText: false
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            Row.__super__.initialize.apply(this, arguments);

            this.listenTo(this.columns, 'sort', this.updateCellsOrder);
        },

        /**
         * Handles columns sort event and updates order of cells
         */
        updateCellsOrder: function() {
            var cell;
            var fragment = document.createDocumentFragment();

            for (var i = 0; i < this.columns.length; i++) {
                cell = _.find(this.cells, {column: this.columns.at(i)});
                if (cell) {
                    fragment.appendChild(cell.el);
                }
            }

            this.$el.html(fragment);
            this.trigger('content:update');
        },

        className: function() {
            return this.model.get('row_class_name');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            _.each(this.cells, function(cell) {
                cell.dispose();
            });
            delete this.cells;
            delete this.columns;
            Row.__super__.dispose.call(this);
        },

        /**
         * jQuery event handler for row click, trigger "clicked" event if row element was clicked
         *
         * @param {Event} e
         */
        onClick: function(e) {
            var exclude = 'a, .dropdown, .skip-row-click';
            var $target = this.$(e.target);
            // if the target is an action element, skip toggling the email
            if ($target.is(exclude) || $target.parents(exclude).length) {
                return;
            }

            this.clickData.counter += 1;
            if (this.clickData.counter === 1 && !this._hasSelectedText()) {
                _.delay(_.bind(function() {
                    if (!this._hasSelectedText() && this.clickData.counter === 1) {
                        this.trigger('clicked', this, e);
                    }
                    this.clickData.counter = 0;
                }, this), this.clickData.timeout);
            } else {
                this.clickData.counter = 0;
            }
        },

        /**
         * Checks if selected text is available
         *
         * @returns {string}
         * @return {boolean}
         */
        _hasSelectedText: function() {
            var text = '';
            if (_.isFunction(window.getSelection)) {
                text = window.getSelection().toString();
            } else if (!_.isUndefined(document.selection) && document.selection.type === 'Text') {
                text = document.selection.createRange().text;
            }
            return !_.isEmpty(text);
        },

        /**
         * @inheritDoc
         */
        makeCell: function(column) {
            var cell = new (column.get('cell'))({
                column: column,
                model: this.model
            });
            if (column.has('align')) {
                cell.$el.removeClass('align-left align-center align-right');
                cell.$el.addClass('align-' + column.get('align'));
            }
            if (!_.isUndefined(cell.skipRowClick) && cell.skipRowClick) {
                cell.$el.addClass('skip-row-click');
            }
            this._listenToCellEvents(cell);

            // use columns collection as event bus since there is no alternatives
            this.columns.trigger('afterMakeCell', this, cell);

            return cell;
        },

        /**
         * Listen to events of cell
         *
         * @param {Backgrid.Cell} cell
         * @private
         */
        _listenToCellEvents: function(cell) {
            if (cell.listenRowClick && _.isFunction(cell.onRowClicked)) {
                this.on('clicked', cell.onRowClicked, cell);
            }
        }
    });

    return Row;
});
