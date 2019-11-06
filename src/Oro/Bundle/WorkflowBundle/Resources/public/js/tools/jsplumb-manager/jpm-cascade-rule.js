define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Rule = require('./jpm-base-rule');

    const CascadeRule = Rule.extend({
        name: 'Cascade',
        match: function(cell) {
            const child1 = _.find(cell.children, function(child) {
                return child.y === cell.y + 1;
            });
            const child2 = _.find(cell.children, function(child) {
                return child.y === cell.y + 2;
            });
            const child3 = _.find(cell.children, function(child) {
                return child.y === cell.y + 3;
            });
            const child4 = _.find(cell.children, function(child) {
                return child.y === cell.y + 4;
            });

            if (child1 && child2 && child3) {
                this.root = cell;
                this.items = [child1, child2, child3];
                return true;
            } else if (child2 && child3 && child4) {
                this.root = cell;
                this.items = [child2, child3, child4];
                return true;
            } else {
                this.root = null;
                this.items = [];
                return false;
            }
        },
        apply: function() {
            const context = this.context;
            let changed = false;
            if (this.root !== null) {
                changed = context.move(this.items[0], this.root.x + 2) || changed;
                changed = context.move(this.items[1], this.root.x + 1) || changed;
                changed = context.move(this.items[2], this.root.x) || changed;
                this.root = null;
                this.items = [];
            }
            return changed;
        }
    });

    return CascadeRule;
});
