define([
    'underscore',
    'backbone',
    'chaplin',
    'backgrid',
    './row',
    '../pageable-collection'
], function(_, Backbone, Chaplin, Backgrid, Row, PageableCollection) {
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
    Body = Chaplin.CollectionView.extend({

        tagName: 'tbody',
        autoRender: false,
        /** @property */
        itemView: Row,
        animationDuration: 0,
        renderItems: true,

        /** @property {String} */
        rowClassName: undefined,

        themeOptions: {
            optionPrefix: 'body',
            className: 'grid-body'
        },

        listen: {
            'backgrid:sort collection': 'sort'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['rowClassName', 'columns', 'filteredColumns', 'emptyText']));
            this.rows = this.subviews;
            Body.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.columns;
            delete this.filteredColumns;

            Body.__super__.dispose.call(this);
        },

        initItemView: function(model) {
            Row = this.row || this.itemView;
            if (Row) {
                return new Row({
                    autoRender: false,
                    model: model,
                    collection: this.filteredColumns,
                    columns: this.columns
                });
            } else {
                throw new Error('The one of Body#row or Body#itemView properties ' +
                    'must be defined or the initItemView() must be overridden.');
            }
        },

        /**
         * Create this function instead of original Body.__super__.refresh to customize options for subviews
         */
        backgridRefresh: function() {
            this.render();
            this.collection.trigger('backgrid:refresh', this);
            return this;
        },

        /**
         * @inheritDoc
         */
        insertView: function(model, view) {
            Body.__super__.insertView.apply(this, arguments);
            this.attachListenerToSingleRow(view);
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

        initFallback: function() {
            if (!this.fallbackSelector && this.emptyText) {
                var fallbackElement = new Backgrid.EmptyRow({
                    emptyText: this.emptyText,
                    columns: this.columns
                }).render().el;
                this.fallbackSelector = _.map(fallbackElement.classList, function(name) {return '.' + name;}).join('');
                this.$el.append(fallbackElement);
            }
            Body.__super__.initFallback.apply(this, arguments);
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

        makeComparator: function(attr, order, func) {

            return function(left, right) {
                // extract the values from the models
                var t;
                var l = func(left, attr);
                var r = func(right, attr);
                // if descending order, swap left and right
                if (order === 1) {
                    t = l;
                    l = r;
                    r = t;
                }
                // compare as usual
                if (l === r) {
                    return 0;
                } else if (l < r) {
                    return -1;
                }
                return 1;
            };
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
