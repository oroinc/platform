define(function(require) {
    'use strict';

    var _ = require('underscore');

    function recurAllChildrenCount(cell, ignores) {
        var count = 1;
        ignores.push(cell);
        _.each(cell.children, function(child) {
            if (ignores.indexOf(child) < 0 && child.y >= cell.y) {
                ignores.push(child);
                count += recurAllChildrenCount(child, ignores);
            }
        });
        return count;
    }

    var Cell = function(options) {
        _.extend(this, options);
        _.defaults(this, {
            children: []
        });
        this.name = this.step.get('name');
    };
    _.extend(Cell.prototype, {
        setChildren: function(cells) {
            this.children = cells;
        },
        hasChild: function(cell) {
            return this.children.indexOf(cell) >= 0;
        },
        isRelativeWith: function(cell) {
            return this.hasChild(cell) || cell.hasChild(this);
        },
        getAllChildrenCount: function() {
            return recurAllChildrenCount(this, []);
        }
    });

    return Cell;
});
