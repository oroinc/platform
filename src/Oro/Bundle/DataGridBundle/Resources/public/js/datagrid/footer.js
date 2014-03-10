/*global define*/
define(['backbone', 'backgrid', './footer/footer-row', './footer/footer-cell'
    ], function (Backbone, Backgrid, FooterRow, FooterCell) {
    "use strict";

    /**
     * Datagrid footer widget
     *
     * @export  orodatagrid/js/datagrid/footer
     * @class   orodatagrid.datagrid.Footer
     * @extends Backgrid.Footer
     */
    return Backgrid.Footer.extend({
        /** @property */
        tagName: "tfoot",

        /** @property */
        row: FooterRow,

        /** @property */
        rows: [],

        /** @property */
        footerCell: FooterCell,

        renderable: false,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.rows = [];
            if (!options.collection) {
                throw new TypeError("'collection' is required");
            }
            if (!options.columns) {
                throw new TypeError("'columns' is required");
            }

            this.columns = options.columns;
            if (!(this.columns instanceof Backbone.Collection)) {
                this.columns = new Backgrid.Columns(this.columns);
            }

            var state = options.collection.state || {};
            if (state.totals && Object.keys(state.totals).length) {
                this.renderable = true;
                _.each(state.totals, function (total, rowName) {
                    this.rows[this.rows.length] = new this.row({
                        columns: this.columns,
                        collection: this.collection,
                        footerCell: this.footerCell,
                        rowName: rowName
                    });
                }, this);

            }
        },

        /**
         Renders this table footer with a single row of footer cells.
         */
        render: function () {
            if (this.renderable) {
                _.each(this.rows, function (row) {
                    this.$el.append(row.render().$el);
                }, this);
            }
            this.delegateEvents();
            return this;
        }
    });
});
