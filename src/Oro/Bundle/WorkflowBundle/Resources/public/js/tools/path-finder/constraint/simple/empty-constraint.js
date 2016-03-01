define(['../../extends', './abstract-simple-constraint'], function(__extends, AbstractSimpleConstraint) {
    'use strict';
    __extends(EmptyConstraint, AbstractSimpleConstraint);
    function EmptyConstraint() {
        AbstractSimpleConstraint.apply(this, arguments);
    }
    Object.defineProperty(EmptyConstraint.prototype, 'recommendedEnd', {
        get: function() {
            return undefined;
        },
        enumerable: true,
        configurable: true
    });
    return EmptyConstraint;
});
