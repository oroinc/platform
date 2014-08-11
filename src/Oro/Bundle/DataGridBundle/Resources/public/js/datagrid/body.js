/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'backgrid',
    './row'
], function (_, Backgrid, Row) {
    'use strict';

    var Body;

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
    Body = Backgrid.Body.extend({
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

            Body.__super__.initialize.apply(this, arguments);

            this._listenToRowsEvents(this.rows);
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            _.each(this.rows, function (row) {
                row.dispose();
            });
            delete this.rows;
            delete this.columns;
            Body.__super__.dispose.call(this);
        },

        /**
         * @inheritDoc
         */
        refresh: function () {
            this._stopListeningToRowsEvents(this.rows);
            Body.__super__.refresh.apply(this, arguments);
            this._listenToRowsEvents(this.rows);
            return this;
        },

        /**
         * @inheritDoc
         */
        insertRow: function (model, collection, options) {
            Body.__super__.insertRow.apply(this, arguments);
            var index = collection.indexOf(model);
            if (index < this.rows.length) {
                this._listenToOneRowEvents(this.rows[index]);
            }
        },

        /**
         * @inheritDoc
         */
        removeRow: function (model, collection, options) {
            var index = collection.indexOf(model);
            if (index < this.rows.length) {
                this._stopListeningToOneRowEvents(this.rows[index]);
            }
            Body.__super__.removeRow.apply(this, arguments);
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
         * Stop listening  to events of rows list
         *
         * @param {Array} rows
         * @private
         */
        _stopListeningToRowsEvents: function (rows) {
            _.each(rows, function (row) {
                this._stopListeningToOneRowEvents(row);
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
         * Stop listening to events of row
         *
         * @param {Backgrid.Row} row
         * @private
         */
        _stopListeningToOneRowEvents: function (row) {
            this.stopListening(row);
        },

        /**
         * @inheritDoc
         */
        render: function () {
            Body.__super__.render.apply(this, arguments);
            if (this.rowClassName) {
                this.$('> *').addClass(this.rowClassName);
            }
            return this;
        }
    });

    return Body;
});
