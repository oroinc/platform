define([
    './footer-cell',
    'chaplin'
], function(FooterCell, Chaplin) {
    'use strict';

    var FooterRow;

    FooterRow = Chaplin.CollectionView.extend({
        tagName: 'tr',
        className: '',
        animationDuration: 0,

        /** @property */
        footerCell: FooterCell,

        initialize: function(options) {
            this.columns = options.columns;
            this.dataCollection = options.dataCollection;

            // itemView function is called as new this.itemView
            // it is placed here to pass THIS within closure
            var _this = this;
            // let descendants override itemView
            if (!this.itemView) {
                this.itemView = function(itemViewOptions) {
                    var column = itemViewOptions.model;
                    var FooterCell = column.get('footerCell') || options.footerCell || _this.footerCell;
                    var cellOptions = {
                        column: column,
                        collection: _this.dataCollection,
                        rowName: options.rowName,
                        themeOptions: {
                            className: 'grid-cell grid-footer-cell'
                        }
                    };
                    if (column.get('name')) {
                        cellOptions.themeOptions.className += ' grid-footer-cell-' + column.get('name');
                    }
                    _this.columns.trigger('configureInitializeOptions', FooterCell, cellOptions);
                    return new FooterCell(cellOptions);
                };
            }
            FooterRow.__super__.initialize.apply(this, arguments);
            this.cells = this.subviews;
        },

        /**
         * @inheritDoc
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
