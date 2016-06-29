define([
    'underscore',
    'backbone',
    'backgrid',
    './footer/footer-row',
    './footer/footer-cell'
], function(_, Backbone, Backgrid, FooterRow, FooterCell) {
    'use strict';

    var Footer;

    /**
     * Datagrid footer widget
     *
     * @export  orodatagrid/js/datagrid/footer
     * @class   orodatagrid.datagrid.Footer
     * @extends Backgrid.Footer
     */
    Footer = Backgrid.Footer.extend({
        /** @property */
        tagName: 'tfoot',

        /** @property */
        row: FooterRow,

        /** @property */
        rows: [],

        /** @property */
        footerCell: FooterCell,

        renderable: false,

        themeOptions: {
            optionPrefix: 'footer',
            className: 'grid-footer'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['themeOptions']));
            var state;

            this.rows = [];
            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }
            if (!options.columns) {
                throw new TypeError('"columns" is required');
            }

            this.columns = options.columns;
            this.filteredColumns = options.filteredColumns;

            state = options.collection.state || {};
            if (state.totals && Object.keys(state.totals).length) {
                this.renderable = true;
                _.each(state.totals, function(total, rowName) {
                    this.rows[this.rows.length] = new this.row({
                        columns: this.columns,
                        collection: this.filteredColumns,
                        dataCollection: this.collection,
                        footerCell: this.footerCell,
                        rowName: rowName
                    });
                }, this);
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            _.each(this.rows, function(row) {
                row.dispose();
            });
            delete this.rows;
            delete this.columns;
            delete this.filteredColumns;
            Footer.__super__.dispose.call(this);
        },

        /**
         * Renders this table footer with a single row of footer cells.
         */
        render: function() {
            if (this.renderable) {
                _.each(this.rows, function(row) {
                    row.render();
                    this.$el.append(row.$el);
                }, this);
            }
            this.delegateEvents();
            return this;
        }
    });

    return Footer;
});
