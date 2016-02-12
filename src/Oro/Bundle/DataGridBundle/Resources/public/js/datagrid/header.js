define([
    'underscore',
    'backbone',
    'backgrid',
    './header-row',
    './header-cell/header-cell'
], function(_, Backbone, Backgrid, HeaderRow, HeaderCell) {
    'use strict';

    var Header;

    /**
     * Datagrid header widget
     *
     * @export  orodatagrid/js/datagrid/header
     * @class   orodatagrid.datagrid.Header
     * @extends Backgrid.Header
     */
    Header = Backgrid.Header.extend({
        /** @property */
        tagName: 'thead',

        /** @property */
        row: HeaderRow,

        /** @property */
        headerCell: HeaderCell,

        viewOptions: {
            className: 'grid-header',
            childViews: ['row']
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }
            if (!options.columns) {
                throw new TypeError('"columns" is required');
            }

            this.columns = options.columns;
            if (!(this.columns instanceof Backbone.Collection)) {
                this.columns = new Backgrid.Columns(this.columns);
            }

            this.row = new this.row({
                columns: this.columns,
                collection: this.collection,
                headerCell: this.headerCell
            });

            this.subviews = [this.row];
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            _.each(this.row.cells, function(cell) {
                cell.dispose();
            });
            delete this.row.cells;
            delete this.row;
            delete this.columns;
            Header.__super__.dispose.apply(this, arguments);
        }
    });

    return Header;
});
