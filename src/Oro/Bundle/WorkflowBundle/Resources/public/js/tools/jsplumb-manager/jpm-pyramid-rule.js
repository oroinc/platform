define(function(require) {
    'use strict';

    var PyramidRule;
    var _ = require('underscore');
    var Rule = require('./jpm-base-rule');

    PyramidRule = Rule.extend({
        name: 'Pyramid',
        priority: 20,
        match: function(cell) {
            var a;
            var b;
            var c;
            var temp;
            var children = _.filter(cell.children, function(child) {
                return child.y === cell.y + 1 || child.y === cell.y + 2;
            });
            children.sort(function(a, b) {
                return a.x - b.x;
            });

            if (children.length === 3) {
                this.items = [];
                for (a = 0; a < 3; a++) {
                    b = (a + 1) % 3;
                    c = (a + 2) % 3;
                    if (children[a].isRelativeWith(children[b]) && children[a].isRelativeWith(children[c])) {
                        this.items = [children[b], children[a], children[c]];
                    }
                }
                if (this.items.length === 0) {
                    for (a = 0; a < 3; a++) {
                        b = (a + 1) % 3;
                        c = (a + 2) % 3;
                        if (children[a].isRelativeWith(children[b])) {
                            if (children[a].y < children[b].y && a < b) {
                                temp = children[a];
                                children[a] = children[b];
                                children[b] = temp;
                            }
                            this.items = children;
                            break;
                        }
                    }
                }
                if (this.items.length > 0) {
                    this.root = cell;
                    return true;
                }
            }
            this.root = null;
            this.items = [];
            return false;
        },
        apply: function() {
            var context = this.context;
            var changed = false;
            if (this.root) {
                changed = context.move(this.items[0], this.root.x - 1) || changed;
                changed = context.move(this.items[1], this.root.x) || changed;
                changed = context.move(this.items[2], this.root.x + 1) || changed;
                this.root = null;
                this.items = [];
            }
            return changed;
        }
    });

    return PyramidRule;
});
