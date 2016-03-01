define(['../../extends', './abstract-simple-constraint', '../../settings'],
    function(__extends, AbstractSimpleConstraint, settings) {
        'use strict';
        __extends(LeftSimpleConstraint, AbstractSimpleConstraint);
        function LeftSimpleConstraint(recomendedStart) {
            AbstractSimpleConstraint.call(this);
            this.recomendedStart = recomendedStart;
        }
        Object.defineProperty(LeftSimpleConstraint.prototype, 'recommendedEnd', {
            get: function() {
                return this.recomendedStart + this.axis.linesIncluded * settings.recommendedConnectionWidth;
            },
            enumerable: true,
            configurable: true
        });
        return LeftSimpleConstraint;
    });
