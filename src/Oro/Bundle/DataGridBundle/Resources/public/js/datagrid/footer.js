/* global define */
define(['backbone', 'backgrid' ,'oro/datagrid/footer-row', 'oro/datagrid/footer-cell'],
function (Backbone, Backgrid , FooterRow, FooterCell) {
    "use strict";

    /**
     * Datagrid header widget
     *
     * @export  oro/datagrid/footer
     * @class   oro.datagrid.Footer
     * @extends Backgrid.Footer
     */
    return Backgrid.Footer.extend({
        /** @property */
        tagName: "tfoot",

        /** @property */
        row: FooterRow,

        /** @property */
        footerCell: FooterCell,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
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

            this.row = new this.row({
                columns: this.columns,
                collection: this.collection,
                footerCell: this.footerCell
            });
        },

        /**
         Renders this table footer with a single row of footer cells.
         */
        render: function () {
            this.$el.append(this.row.render().$el);
            this.delegateEvents();
            return this;
        }
    });
});
