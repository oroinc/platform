define(function(require) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const Chaplin = require('chaplin');
    const Backgrid = require('backgrid');
    const Row = require('./row');

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
    const Body = Chaplin.CollectionView.extend({

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

        /**
         * @inheritdoc
         */
        constructor: function Body(options) {
            Body.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['rowClassName', 'columns', 'filteredColumns', 'emptyText',
                'gridRowsCounter']));
            this.rows = this.subviews;
            if ('rowView' in options.themeOptions) {
                this.itemView = options.themeOptions.rowView;
            }
            Body.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.columns;
            delete this.filteredColumns;
            delete this.gridRowsCounter;

            Body.__super__.dispose.call(this);
        },

        renderAllItems: function() {
            const result = Body.__super__.renderAllItems.call(this);
            mediator.trigger('layout:adjustHeight');
            return result;
        },

        initItemView: function(model) {
            const RowView = this.row || this.itemView;
            if (RowView) {
                const rowOptions = {
                    autoRender: false,
                    model: model,
                    dataCollection: this.collection,
                    collection: this.filteredColumns,
                    columns: this.columns,
                    rowClassName: this.rowClassName,
                    ariaRowsIndexShift: this.gridRowsCounter.getHeaderRowsCount()
                };
                this.columns.trigger('configureInitializeOptions', RowView, rowOptions);

                const row = new RowView(rowOptions);
                this.attachListenerToSingleRow(row);

                return row;
            } else {
                throw new Error('The one of Body#row or Body#itemView properties ' +
                    'must be defined or the initItemView() must be overridden.');
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

        initFallback: function() {
            if (!this.fallbackSelector && this.emptyText) {
                const fallbackElement = new Backgrid.EmptyRow({
                    emptyText: this.emptyText,
                    columns: this.columns
                }).render().el;
                this.fallbackSelector = _.map(fallbackElement.classList, function(name) {
                    return '.' + name;
                }).join('');
                this.$el.append(fallbackElement);
            }
            Body.__super__.initFallback.call(this);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            this._deferredRender();
            Body.__super__.render.call(this);
            this._resolveDeferredRender();
            return this;
        }
    });

    return Body;
});
