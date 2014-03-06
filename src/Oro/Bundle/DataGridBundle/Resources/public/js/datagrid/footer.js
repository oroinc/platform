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
        footerCell: FooterCell,

        renderable: false,

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

            var state = options.collection.state || {};
            if (state.totals && Object.keys(state.totals).length) {
                this.renderable = true;
                this.row = new this.row({
                    columns: this.columns,
                    collection: this.collection,
                    footerCell: this.footerCell
                });
            }
        },

        /**
         Renders this table footer with a single row of footer cells.
         */
        render: function () {
            if (this.renderable) {
                this.$el.append(this.row.render().$el);
            }
            this.delegateEvents();
            return this;
        }
    });
});
