define([
    'underscore',
    'oroui/js/mediator',
    'backbone',
    'chaplin',
    'backgrid',
    './row'
], function(_, mediator, Backbone, Chaplin, Backgrid, Row) {
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

        /**
         * @inheritDoc
         */
        constructor: function Body() {
            Body.__super__.constructor.apply(this, arguments);
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

        renderAllItems: function() {
            var result = Body.__super__.renderAllItems.call(this, arguments);
            mediator.trigger('layout:adjustHeight');
            return result;
        },

        initItemView: function(model) {
            Row = this.row || this.itemView;
            if (Row) {
                var rowOptions = {
                    autoRender: false,
                    model: model,
                    collection: this.filteredColumns,
                    columns: this.columns
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
                this.fallbackSelector = _.map(fallbackElement.classList, function(name) {
                    return '.' + name;
                }).join('');
                this.$el.append(fallbackElement);
            }
            Body.__super__.initFallback.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            this._deferredRender();
            Body.__super__.render.apply(this, arguments);
            if (this.rowClassName) {
                this.$('> *').addClass(this.rowClassName);
            }
            this._resolveDeferredRender();
            return this;
        }
    });

    return Body;
});
