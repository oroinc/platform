/*global define*/
define(['underscore', 'backgrid', './row'
    ], function (_, Backgrid, Row) {
    'use strict';

    /**
     * Grid body widget
     *
     * Triggers events:
     *  - "rowClicked" when row of body is clicked
     *
     * @export  orodatagrid/js/datagrid/body
     * @class   orodatagrid.datagrid.Body
     * @extends Backgrid.Body
     */
    return Backgrid.Body.extend({
        /** @property */
        row: Row,

        /** @property {String} */
        rowClassName: undefined,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            var opts = options || {};

            if (!opts.row) {
                opts.row = this.row;
            }

            if (opts.rowClassName) {
                this.rowClassName = opts.rowClassName;
            }

            Backgrid.Body.prototype.initialize.apply(this, arguments);

            this._listenToRowsEvents(this.rows);
        },

        /**
         * @inheritDoc
         */
        refresh: function () {
            Backgrid.Body.prototype.refresh.apply(this, arguments);
            this._listenToRowsEvents(this.rows);
            return this;
        },

        /**
         * @inheritDoc
         */
        insertRow: function (model, collection, options) {
            Backgrid.Body.prototype.insertRow.apply(this, arguments);
            var index = collection.indexOf(model);
            if (index < this.rows.length) {
                this._listenToOneRowEvents(this.rows[index]);
            }
        },

        /**
         * Listen to events of rows list
         *
         * @param {Array} rows
         * @private
         */
        _listenToRowsEvents: function (rows) {
            _.each(rows, function (row) {
                this._listenToOneRowEvents(row);
            }, this);
        },

        /**
         * Listen to events of row
         *
         * @param {Backgrid.Row} row
         * @private
         */
        _listenToOneRowEvents: function (row) {
            this.listenTo(row, 'clicked', function (row, e) {
                this.trigger('rowClicked', row, e);
            });
        },

        /**
         * @inheritDoc
         */
        render: function () {
            Backgrid.Body.prototype.render.apply(this, arguments);
            if (this.rowClassName) {
                this.$('> *').addClass(this.rowClassName);
            }
            return this;
        }
    });
});
