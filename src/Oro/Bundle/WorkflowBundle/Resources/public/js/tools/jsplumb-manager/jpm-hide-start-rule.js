define(function(require) {
    'use strict';

    var HideStartRule;
    var Rule = require('./jpm-base-rule');

    HideStartRule = Rule.extend({
        name: 'HideStart',
        match: function(cell) {
            if (cell.step.get('order') < 0 && cell.children.length < 1) {
                this.root = cell;
                this.items = [];
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
                changed = true;
                context.remove(this.root);
                this.root = null;
                this.items = [];
            }
            return changed;
        }
    });

    return HideStartRule;
});
