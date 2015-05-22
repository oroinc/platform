define(function (require) {
    'use strict';
    var _ = require('underscore');
    return {
        xPadding: 15,
        yPadding: 15,
        xIncrement: 200,
        yIncrement: 75,
        checkPositions: function (workflow) {
            var i, j, step, key,
                steps = workflow.get('steps').filter(function (item) {
                    return !item.get('position');
                }),
                groupedSteps = _.groupBy(steps, function (item) {
                    return item.get('order');
                }),
                sortedKeys = _.each(_.keys(groupedSteps), parseInt).sort();
            for (i = 0; i < sortedKeys.length; i++) {
                key = sortedKeys[i];
                for (j = 0; j < groupedSteps[key].length; j++) {
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
