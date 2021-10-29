define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backbone = require('backbone');
    const Backgrid = require('backgrid');
    const HeaderRow = require('./header-row');
    const HeaderCell = require('./header-cell/header-cell');

    /**
     * Datagrid header widget
     *
     * @export  orodatagrid/js/datagrid/header
     * @class   orodatagrid.datagrid.Header
     * @extends Backgrid.Header
     */
    const Header = Backgrid.Header.extend({
        /** @property */
        tagName: 'thead',

        /** @property */
        row: HeaderRow,

        /** @property */
        headerCell: HeaderCell,

        themeOptions: {
            optionPrefix: 'header',
            className: 'grid-header'
        },

        /**
         * @inheritdoc
         */
        constructor: function Header(options) {
            Header.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['themeOptions']));
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

            this.filteredColumns = options.filteredColumns;

            const rowOptions = {
                columns: this.columns,
                collection: this.filteredColumns,
                dataCollection: this.collection,
                headerCell: this.headerCell,
                ariaRowIndex: 1
            };
            this.columns.trigger('configureInitializeOptions', this.row, rowOptions);
            this.row = new this.row(rowOptions);

            this.subviews = [this.row];
        },

        /**
         * Get a number of rendered rows in a header
         *
         * @return {number}
         */
        getRowsCount() {
            return 1;
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.row.dispose();
            delete this.row;
            delete this.columns;
            delete this.filteredColumns;
            Header.__super__.dispose.call(this);
        }
    });

    return Header;
});
