define(function(require) {
    'use strict';

    /**
     * Cell iterator
     * @class
     * @augments [BaseClass](../../../../UIBundle/Resources/doc/reference/client-side/base-class.md)
     */
    var CellIterator;
    var BaseClass = require('oroui/js/base-class');
    var $ = require('jquery');
    var _ = require('underscore');

    CellIterator = BaseClass.extend({
        constructor: function(grid, cell) {
            this.current = cell;
            this.grid = grid;
            this.columns = grid.columns;
            this.rows = grid.collection;
        },

        toResolvedPromise: function(result) {
            var deferred = $.Deferred();
            deferred.resolve(result);
            return deferred.promise();
        },

        isColumnVisible: function(column) {
            return column.get('renderable');
        },

        getCurrentCellInfo: function() {
            var rowI = this.rows.indexOf(this.current.model);
            var isFirstRow = rowI === 0;
            var isLastRow = rowI >= this.rows.length - 1;
            var columnI = this.columns.indexOf(this.current.column);
            var isFirstColumn = columnI <= _.findIndex(this.columns.models, this.isColumnVisible);
            var isLastColumn = columnI >= _.findLastIndex(this.columns.models, this.isColumnVisible);
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
            var info = this.getCurrentCellInfo();
            var columnI;
            var rowI;
            var _this = this;

            if (info.column.last) {
                if (info.row.last) {
                    // navigate to next page
                    return this.getNextPage().then(function() {
                        _this.current = _this.findCellByIndexOrNext(0, 0, 1);
                        return _this.current;
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
            var info = this.getCurrentCellInfo();
            var columnI;
            var rowI;
            var _this = this;
            rowI = info.row.i + 1;
            columnI = info.column.i;

            if (info.row.last) {
                // navigate to next page
                return this.getNextPage().then(function() {
                    _this.current = _this.findCellByIndexOrNext(0, columnI, 1);
                    return _this.current;
                });
            }

            this.current = this.findCellByIndexOrNext(rowI, columnI, 1);

            return this.toResolvedPromise(this.current);
        },

        prev: function() {
            var info = this.getCurrentCellInfo();
            var columnI;
            var rowI;
            var _this = this;

            if (info.column.first) {
                if (info.row.first) {
                    // navigate to prev page
                    return this.getPreviousPage().then(function() {
                        _this.current =
                            _this.findCellByIndexOrNext(_this.rows.length - 1, _this.columns.length - 1, -1);
                        return _this.current;
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
            var info = this.getCurrentCellInfo();
            var columnI;
            var rowI;
            var _this = this;

            rowI = info.row.i - 1;
            columnI = info.column.i;

            if (info.row.first) {
                // navigate to prev page
                return this.getPreviousPage().then(function() {
                    _this.current = _this.findCellByIndexOrNext(_this.rows.length - 1, columnI, -1);
                    return _this.current;
                });
            }

            this.current = this.findCellByIndexOrNext(rowI, columnI, -1);

            return this.toResolvedPromise(this.current);
        },

        findCellByIndexOrNext: function(rowI, columnI, direction) {
            var current;
            while (!current && columnI >= 0 && columnI < this.columns.length) {
                current = this.grid.findCellByIndex(rowI, columnI);
                columnI += direction;
            }
            return current;
        }
    });

    return CellIterator;
});
