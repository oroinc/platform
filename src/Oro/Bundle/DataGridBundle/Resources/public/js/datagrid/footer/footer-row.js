define(function(require) {
    'use strict';

    const _ = require('underscore');
    const FooterCell = require('./footer-cell');
    const Chaplin = require('chaplin');

    const FooterRow = Chaplin.CollectionView.extend({
        tagName: 'tr',

        className: '',

        animationDuration: 0,

        /**
         * @inheritdoc
         */
        constructor: function FooterRow(options) {
            this.ariaRowIndex = options.ariaRowIndex;
            FooterRow.__super__.constructor.call(this, options);
        },

        /** @property */
        footerCell: FooterCell,

        initialize: function(options) {
            this.columns = options.columns;
            this.dataCollection = options.dataCollection;

            // itemView function is called as new this.itemView
            // it is placed here to pass THIS within closure
            const footerRowView = this;
            // let descendants override itemView
            if (!this.itemView) {
                this.itemView = function(itemViewOptions) {
                    const column = itemViewOptions.model;
                    const FooterCell = column.get('footerCell') || options.footerCell || footerRowView.footerCell;
                    const cellOptions = {
                        column: column,
                        collection: footerRowView.dataCollection,
                        rowName: options.rowName,
                        themeOptions: {
                            className: 'grid-cell grid-footer-cell'
                        }
                    };
                    if (column.get('name')) {
                        cellOptions.themeOptions.className += ' grid-footer-cell-' + column.get('name');
                    }
                    footerRowView.columns.trigger('configureInitializeOptions', FooterCell, cellOptions);
                    return new FooterCell(cellOptions);
                };
            }
            FooterRow.__super__.initialize.call(this, options);
            this.listenTo(this.dataCollection, 'add remove reset', this.syncAttrs);
            this.cells = this.subviews;
        },

        syncAttrs() {
            if (this.disposed) {
                return;
            }
            this.$el.attr('aria-rowindex', this.ariaRowIndex);
        },

        attributes() {
            let attrs = FooterRow.__super__.attributes || {};

            if (_.isFunction(attrs)) {
                attrs = attrs.call(this);
            }

            attrs['aria-rowindex'] = this.ariaRowIndex;

            return attrs;
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.cells;
            delete this.columns;
            delete this.dataCollection;
            FooterRow.__super__.dispose.call(this);
        }
    });

    return FooterRow;
});
