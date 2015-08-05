define(['../extends', './abstract-location-directive', '../settings'],
    function(__extends, AbstractLocationDirective, settings) {
        'use strict';
        __extends(StickLeftLocationDirective, AbstractLocationDirective);
        function StickLeftLocationDirective() {
            AbstractLocationDirective.apply(this, arguments);
        }
        StickLeftLocationDirective.prototype.getRecommendedPosition = function(lineNo) {
            var center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
            return center + settings.recommendedConnectionWidth * (lineNo + 0.5);
        };
        return StickLeftLocationDirective;
    });
