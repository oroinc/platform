define(['underscore'], function(_) {
    'use strict';

    return {
        applyTo: function(cellConstructor) {
            cellConstructor.simplifiedEventBinding = true;
            cellConstructor.prototype.delegateEvents = _.noop;
            cellConstructor.prototype.undelegateEvents = _.noop;
        }
    };
});
