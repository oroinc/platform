define([
    'underscore',
    'oroui/js/mediator',
    'backbone',
    'chaplin',
    'backgrid',
    './row'
], function(_, mediator, Backbone, Chaplin, Backgrid, Row) {
    'use strict';

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
         * @inheritDoc
         */
        constructor: function Body(options) {
            Body.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['rowClassName', 'columns', 'filteredColumns', 'emptyText']));
            this.rows = this.subviews;
            if ('rowView' in options.themeOptions) {
                this.itemView = options.themeOptions.rowView;
            }
            Body.__super__.initialize.call(this, options);
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

        renderAllItems: function() {
            const result = Body.__super__.renderAllItems.call(this);
            mediator.trigger('layout:adjustHeight');
            return result;
        },

        initItemView: function(model) {
            Row = this.row || this.itemView;
            if (Row) {
                const rowOptions = {
                    autoRender: false,
                    model: model,
                    collection: this.filteredColumns,
                    columns: this.columns,
                    rowClassName: this.rowClassName
                };
                this.columns.trigger('configureInitializeOptions', Row, rowOptions);
                return new Row(rowOptions);
            } else {
                throw new Error('The one of Body#row or Body#itemView properties ' +
                    'must be defined or the initItemView() must be overridden.');
            }
        },

        /**
         * @inheritDoc
         */
        insertView: function(model, view, ...rest) {
            Body.__super__.insertView.call(this, model, view, ...rest);
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
         * @inheritDoc
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
