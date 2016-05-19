define([
    'backgrid',
    'chaplin',
    './util'
], function(Backgrid, Chaplin, util) {
    'use strict';

    var HeaderRow;

    HeaderRow = Chaplin.CollectionView.extend({
        tagName: 'tr',
        className: '',

        themeOptions: {
            optionPrefix: 'headerRow',
            className: 'grid-header-row'
        },

        initialize: function(options) {
            // itemView function is called as new this.itemView
            // it is placed here to pass THIS within closure
            var _this = this;

            this.columns = options.columns;
            // let descendants override itemView
            if (!this.itemView) {
                this.itemView = function(options) {
                    var column = options.model;
                    var HeaderCell = column.get('headerCell') || options.headerCell || Backgrid.HeaderCell;
                    var cellOptions = {
                        column: column,
                        collection: _this.collection,
                        themeOptions: {
                            className: 'grid-cell grid-header-cell'
                        }
                    };
                    if (column.get('name')) {
                        cellOptions.themeOptions.className += ' grid-header-cell-' + column.get('name');
                    }
                    _this.columns.trigger('configureInitializeOptions', HeaderCell, cellOptions);
                    return new HeaderCell(cellOptions);
                };
            }
            HeaderRow.__super__.initialize.apply(this, arguments);
            this.cells = this.subviews;
        },

        /**
         * Cells is not removed from DOM by Chaplin.CollectionView or their realizations
         * Do that manually as it is critical for FloatingHeader plugin
         */
        removeSubview: util.removeSubview
    });

    return HeaderRow;
});
