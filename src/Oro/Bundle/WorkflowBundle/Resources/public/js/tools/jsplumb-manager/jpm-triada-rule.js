define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Rule = require('./jpm-base-rule');

    const TriadaRule = Rule.extend({
        name: 'Triada',
        match: function(cell) {
            const children = _.filter(cell.children, function(child) {
                return child.y === cell.y + 1 || child.y === cell.y + 2;
            });
            children.sort(function(a, b) {
                return a.y > b.y;
            });
            if (children.length === 3) {
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
            const context = this.context;
            let average;
            let changed = false;
            if (this.root) {
                average = Math.floor((this.items[0].x + this.items[1].x + this.items[2].x) / 3);
                changed = context.move(this.root, average) || changed;
                changed = context.move(this.items[0], average - 1) || changed;
                changed = context.move(this.items[1], average) || changed;
                changed = context.move(this.items[2], average + 1) || changed;
                this.root = null;
                this.items = [];
            }
            return changed;
        }
    });
    return TriadaRule;
});
