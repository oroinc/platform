define([
    'underscore',
    'backgrid',
    './row',
    '../pageable-collection'
], function(_, Backgrid, Row, PageableCollection) {
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

        themeOptions: {
            optionPrefix: 'body',
            className: 'grid-body'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var opts = options || {};

            if (!opts.row) {
                opts.row = this.row;
            }

            if (opts.rowClassName) {
                this.rowClassName = opts.rowClassName;
            }

            this.backgridInitialize(opts);
        },

        /**
         * Create this function instead of original Body.__super__.initialize to customize options for subviews
         *
         * @param {Object} options
         */
        backgridInitialize: function(options) {
            this.columns = options.columns;

            this.row = options.row || Row;
            this.createRows();

            this.emptyText = options.emptyText;
            this._unshiftEmptyRowMayBe();

            var collection = this.collection;
            this.listenTo(collection, 'add', this.insertRow);
            this.listenTo(collection, 'remove', this.removeRow);
            this.listenTo(collection, 'sort', this.refresh);
            this.listenTo(collection, 'reset', this.refresh);
            this.listenTo(collection, 'backgrid:sort', this.sort);
            this.listenTo(collection, 'backgrid:edited', this.moveToNextCell);
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
            Body.__super__.dispose.call(this);
        },

        createRows: function() {
            this.rows = this.collection.map(function(model) {
                var rowOptions = {
                    columns: this.columns,
                    model: model
                };
                this.columns.trigger('configureInitializeOptions', this.row, rowOptions);
                var row = new this.row(rowOptions);
                this.attachListenerToSingleRow(row);
                return row;
            }, this);
        },

        /**
         * @inheritDoc
         */
        refresh: function() {
            _.each(this.rows, function(row) {
                // dispose in Chaplin's way, instead of Backbone's remove
                row.dispose();
            });
            this.rows = [];
            this.backgridRefresh();
            return this;
        },

        /**
         * Create this function instead of original Body.__super__.refresh to customize options for subviews
         */
        backgridRefresh: function() {
            this.createRows();
            this._unshiftEmptyRowMayBe();

            this.render();

            this.collection.trigger('backgrid:refresh', this);

            return this;
        },

        /**
         * @inheritDoc
         */
        insertRow: function(model, collection, options) {
            Body.__super__.insertRow.apply(this, arguments);
            var index = collection.indexOf(model);
            if (index < this.rows.length) {
                this.attachListenerToSingleRow(this.rows[index]);
            }
        },

        /**
         * Listen to events of row
         *
         * @param {Backgrid.Row} row
         * @private
         */
        attachListenerToSingleRow: function(row) {
            row.on('clicked', function(row, options) {
                this.trigger('rowClicked', row, options);
            }, this);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            Body.__super__.render.apply(this, arguments);
            if (this.rowClassName) {
                this.$('> *').addClass(this.rowClassName);
            }
            return this;
        },

        /**
         * @param {string} column
         * @param {null|"ascending"|"descending"} direction
         */
        sort: function(column, direction) {
            if (!_.contains(['ascending', 'descending', null], direction)) {
                throw new RangeError('direction must be one of "ascending", "descending" or `null`');
            }
            if (_.isString(column)) {
                column = this.columns.findWhere({name: column});
            }

            var collection = this.collection;

            var order;

            if (direction === 'ascending') {
                order = '-1';
            } else if (direction === 'descending') {
                order = '1';
            } else {
                order = null;
            }

            var extractorDelegate;
            if (order) {
                extractorDelegate = column.sortValue();
            } else {
                extractorDelegate = function(model) {
                    return model.cid.replace('c', '') * 1;
                };
            }
            var comparator = this.makeComparator(column.get('name'), order, extractorDelegate);

            if (collection instanceof PageableCollection) {
                collection.setSorting(column.get('name'), order, {sortValue: column.sortValue()});

                if (collection.fullCollection) {
                    if (collection.fullCollection.comparator === null ||
                        collection.fullCollection.comparator === undefined) {
                        collection.fullCollection.comparator = comparator;
                    }
                    collection.fullCollection.sort();
                    collection.trigger('backgrid:sorted', column, direction, collection);
                } else {
                    collection.fetch({reset: true, success: function() {
                        collection.trigger('backgrid:sorted', column, direction, collection);
                    }});
                }
            } else {
                collection.comparator = comparator;
                collection.sort();
                collection.trigger('backgrid:sorted', column, direction, collection);
            }

            column.set('direction', direction);

            return this;
        }
    });

    return Body;
});
