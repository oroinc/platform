define(function(require) {
    'use strict';

    /**
     * Cell iterator
     * @class
     * @augments [BaseClass](../../../../UIBundle/Resources/doc/reference/client-side/base-class.md)
     */
    const BaseClass = require('oroui/js/base-class');
    const $ = require('jquery');
    const _ = require('underscore');

    const CellIterator = BaseClass.extend({
        constructor: function CellIterator(grid, cell) {
            this.current = cell;
            this.grid = grid;
            this.columns = grid.columns;
            this.rows = grid.collection;

            CellIterator.__super__.constructor.call(this, grid, cell);
        },

        toResolvedPromise: function(result) {
            const deferred = $.Deferred();
            deferred.resolve(result);
            return deferred.promise();
        },

        isColumnVisible: function(column) {
            return column.get('renderable');
        },

        getCurrentCellInfo: function() {
            const rowI = this.rows.indexOf(this.current.model);
            const isFirstRow = rowI === 0;
            const isLastRow = rowI >= this.rows.length - 1;
            const columnI = this.columns.indexOf(this.current.column);
            const isFirstColumn = columnI <= _.findIndex(this.columns.models, this.isColumnVisible);
            const isLastColumn = columnI >= _.findLastIndex(this.columns.models, this.isColumnVisible);
            return {
                row: {
                    i: rowI,
                    first: isFirstRow,
                    last: isLastRow
                },
                column: {
                    i: columnI,
                    first: isFirstColumn,
                    last: isLastColumn
                }
            };
        },

        getNextPage: function() {
            if (this.rows.hasNext()) {
                return this.rows.getNextPage();
            } else if (this.rows.state.firstPage !== this.rows.state.currentPage) {
                return this.rows.getPage(this.rows.state.firstPage);
            }
            return this.toResolvedPromise({});
        },

        getPreviousPage: function() {
            // navigate to prev page
            if (this.rows.hasPrevious()) {
                return this.rows.getPreviousPage();
            } else if (this.rows.state.lastPage !== this.rows.state.currentPage) {
                return this.rows.getPage(this.rows.state.lastPage);
            }
            return this.toResolvedPromise({});
        },

        next: function() {
            const info = this.getCurrentCellInfo();
            let columnI;
            let rowI;

            if (info.column.last) {
                if (info.row.last) {
                    // navigate to next page
                    return this.getNextPage().then(() => {
                        this.current = this.findCellByIndexOrNext(0, 0, 1);
                        return this.current;
                    });
                }
                rowI = info.row.i + 1;
                columnI = 0;
            } else {
                rowI = info.row.i;
                columnI = info.column.i + 1;
            }

            this.current = this.findCellByIndexOrNext(rowI, columnI, 1);

            return this.toResolvedPromise(this.current);
        },

        nextRow: function() {
            const info = this.getCurrentCellInfo();
            const rowI = info.row.i + 1;
            const columnI = info.column.i;

            if (info.row.last) {
                // navigate to next page
                return this.getNextPage().then(() => {
                    this.current = this.findCellByIndexOrNext(0, columnI, 1);
                    return this.current;
                });
            }

            this.current = this.findCellByIndexOrNext(rowI, columnI, 1);

            return this.toResolvedPromise(this.current);
        },

        prev: function() {
            const info = this.getCurrentCellInfo();
            let columnI;
            let rowI;

            if (info.column.first) {
                if (info.row.first) {
                    // navigate to prev page
                    return this.getPreviousPage().then(() => {
                        this.current =
                            this.findCellByIndexOrNext(this.rows.length - 1, this.columns.length - 1, -1);
                        return this.current;
                    });
                }
                rowI = info.row.i - 1;
                columnI = this.columns.length - 1;
            } else {
                rowI = info.row.i;
                columnI = info.column.i - 1;
            }

            this.current = this.findCellByIndexOrNext(rowI, columnI, -1);

            return this.toResolvedPromise(this.current);
        },

        prevRow: function() {
            const info = this.getCurrentCellInfo();

            const rowI = info.row.i - 1;
            const columnI = info.column.i;

            if (info.row.first) {
                // navigate to prev page
                return this.getPreviousPage().then(() => {
                    this.current = this.findCellByIndexOrNext(this.rows.length - 1, columnI, -1);
                    return this.current;
                });
            }

            this.current = this.findCellByIndexOrNext(rowI, columnI, -1);

            return this.toResolvedPromise(this.current);
        },

        findCellByIndexOrNext: function(rowI, columnI, direction) {
            let current;
            while (!current && columnI >= 0 && columnI < this.columns.length) {
                current = this.grid.findCellByIndex(rowI, columnI);
                columnI += direction;
            }
            return current;
        }
    });

    return CellIterator;
});
