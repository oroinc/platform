define(function(require) {
    'use strict';

    /**
     * Cell iterator
     * @class
     */
    var CellIterator;
    var BaseClass = require('oroui/js/base-class');
    var $ = require('jquery');

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

        getCurrentCellInfo: function() {
            var rowI = this.rows.indexOf(this.current.model);
            var isFirstRow = rowI === 0;
            var isLastRow = rowI >= this.rows.length - 1;
            var columnI = this.columns.indexOf(this.current.column);
            var isFirstColumn = columnI === 0;
            var isLastColumn = columnI >= this.columns.length - 1;
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
        next: function() {
            var info = this.getCurrentCellInfo();
            var columnI;
            var rowI;
            var _this = this;

            if (info.column.last) {
                if (info.row.last) {
                    // navigate to next page
                    if (this.rows.hasNext()) {
                        return this.rows.getNextPage().then(function() {
                            _this.current = _this.grid.findCellByIndex(0, 0);
                            return _this.current;
                        });
                    } else {
                        return this.rows.getPage(this.rows.state.firstPage).then(function() {
                            _this.current = _this.grid.findCellByIndex(0, 0);
                            return _this.current;
                        });
                    }
                }
                rowI = info.row.i + 1;
                columnI = 0;
            } else {
                rowI = info.row.i;
                columnI = info.column.i + 1;
            }

            this.current = this.grid.findCellByIndex(rowI, columnI);

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
                if (this.rows.hasNext()) {
                    return this.rows.getNextPage().then(function() {
                        _this.current = _this.grid.findCellByIndex(0, columnI);
                        return _this.current;
                    });
                } else {
                    return this.rows.getPage(this.rows.state.firstPage).then(function() {
                        _this.current = _this.grid.findCellByIndex(0, columnI);
                        return _this.current;
                    });
                }
            }

            this.current = this.grid.findCellByIndex(rowI, columnI);

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
                    if (this.rows.hasPrevious()) {
                        return this.rows.getPreviousPage().then(function() {
                            _this.current = _this.grid.findCellByIndex(_this.rows.length - 1, _this.columns.length - 1);
                            return _this.current;
                        });
                    } else {
                        return this.rows.getPage(this.rows.state.lastPage).then(function() {
                            _this.current = _this.grid.findCellByIndex(_this.rows.length - 1, _this.columns.length - 1);
                            return _this.current;
                        });
                    }
                }
                rowI = info.row.i - 1;
                columnI = this.columns.length - 1;
            } else {
                rowI = info.row.i;
                columnI = info.column.i - 1;
            }

            this.current = this.grid.findCellByIndex(rowI, columnI);

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
                if (this.rows.hasPrevious()) {
                    return this.rows.getPreviousPage().then(function() {
                        _this.current = _this.grid.findCellByIndex(_this.rows.length - 1, columnI);
                        return _this.current;
                    });
                } else {
                    return this.rows.getPage(this.rows.state.lastPage).then(function() {
                        _this.current = _this.grid.findCellByIndex(_this.rows.length - 1, columnI);
                        return _this.current;
                    });
                }
            }

            this.current = this.grid.findCellByIndex(rowI, columnI);

            return this.toResolvedPromise(this.current);
        }
    });

    return CellIterator;
});
