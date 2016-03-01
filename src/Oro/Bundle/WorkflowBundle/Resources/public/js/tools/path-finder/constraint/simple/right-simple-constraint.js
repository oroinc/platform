define(['../../extends', './abstract-simple-constraint', '../../settings'],
    function(__extends, AbstractSimpleConstraint, settings) {
        'use strict';
        __extends(RightSimpleConstraint, AbstractSimpleConstraint);
        function RightSimpleConstraint(recommendedStart) {
            AbstractSimpleConstraint.call(this);
            this.recomendedStart = recommendedStart;
        }
        Object.defineProperty(RightSimpleConstraint.prototype, 'recommendedBound', {
            get: function() {
                return this.recomendedStart + this.axis.linesIncluded * settings.recommendedConnectionWidth;
            },
            enumerable: true,
            configurable: true
        });
        return RightSimpleConstraint;
    });
