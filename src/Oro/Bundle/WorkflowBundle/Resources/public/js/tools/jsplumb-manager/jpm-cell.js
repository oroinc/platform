define(function (require) {
    'use strict';
    var _ = require('underscore');

    function recurCalcTree(cell, ignore) {
        var num = 1;
        ignore.push(cell);
        _.each(cell.children, function (child) {
            if (ignore.indexOf(child) < 0 && child.y >= cell.y) {
                ignore.push(child);
                num += recurCalcTree(child, ignore);
            }
        });
        return num;
    }

    var Cell = function (options) {
        _.extend(this, options);
        _.defaults(this, {
            children: []
        });
        this.name = this.step.get('name');
    }
    _.extend(Cell.prototype, {
        setChildren: function (cells) {
            this.children = cells
        },
        hasChild: function (cell) {
            return this.children.indexOf(cell) >= 0;
        },
        isRelativeWith: function (cell) {
            return this.hasChild(cell) || cell.hasChild(this);
        },
        calculateTree: function () {
            var ignore = [], num;
            num = recurCalcTree(this, ignore);
            return num;
        }
    });

    return Cell;
});
