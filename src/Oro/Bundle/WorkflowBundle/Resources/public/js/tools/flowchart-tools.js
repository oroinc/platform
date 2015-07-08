define(function(require) {
    'use strict';

    var _ = require('underscore');

    return {
        xPadding: 15,
        yPadding: 15,
        xIncrement: 400,
        yIncrement: 125,
        checkPositions: function(workflow) {
            var step;
            var key;
            var steps = workflow.get('steps').filter(function(item) {
                return !item.get('position');
            });
            var groupedSteps = _.groupBy(steps, function(item) {
                return item.get('order');
            });
            var sortedKeys = _.each(_.keys(groupedSteps), parseInt).sort();
            for (var i = 0; i < sortedKeys.length; i++) {
                key = sortedKeys[i];
                for (var j = 0; j < groupedSteps[key].length; j++) {
                    step = groupedSteps[key][j];
                    step.set('position', [
                        this.xIncrement * j + this.xPadding,
                        this.yIncrement * i + this.yPadding
                    ]);
                }
            }
        }
    };
});
