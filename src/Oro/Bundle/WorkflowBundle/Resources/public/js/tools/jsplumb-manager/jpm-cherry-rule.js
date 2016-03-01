define(function(require) {
    'use strict';

    var CherryRule;
    var _ = require('underscore');
    var Rule = require('./jpm-base-rule');

    CherryRule = Rule.extend({
        name: 'Cherry',
        match: function(cell) {
            var children = _.filter(cell.children, function(child) {
                return child.y > cell.y;
            });
            if (cell.step.get('order') >= 0 && children.length === 2) {
                this.root = cell;
                this.items = children;
                return true;
            } else {
                this.root = null;
                this.items = [];
                return false;
            }
        },
        apply: function() {
            var context = this.context;
            var changed = false;
            if (this.root !== null) {
                if (this.items[0].x > this.items[1].x) {
                    this.items.reverse();
                }
                if (this.items[0].getAllChildrenCount() < this.items[1].getAllChildrenCount()) {
                    changed = true;
                    context.swap(this.items[0], this.items[1]);
                    this.items.reverse();
                }
                if (this.items[1].x - this.items[0].x < 2) {
                    changed = context.move(this.items[1], this.items[0].x + 2) || changed;
                }
                context.move(this.root, this.items[0].x + 1);
                this.root = null;
                this.items = [];
            }
            return changed;
        }
    });

    return CherryRule;
});
