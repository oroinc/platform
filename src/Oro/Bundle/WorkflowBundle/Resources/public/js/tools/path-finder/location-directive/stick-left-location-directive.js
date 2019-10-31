define(['../extends', './abstract-location-directive', '../settings'],
    function(__extends, AbstractLocationDirective, settings) {
        'use strict';
        __extends(StickLeftLocationDirective, AbstractLocationDirective);
        function StickLeftLocationDirective(...args) {
            AbstractLocationDirective.apply(this, args);
        }
        StickLeftLocationDirective.prototype.getRecommendedPosition = function(lineNo) {
            const center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
            return center + settings.recommendedConnectionWidth * (lineNo + 0.5);
        };
        return StickLeftLocationDirective;
    });
