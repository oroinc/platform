define(['../extends', './abstract-location-directive', '../settings'],
    function(__extends, AbstractLocationDirective, settings) {
        'use strict';
        __extends(StickRightLocationDirective, AbstractLocationDirective);
        function StickRightLocationDirective(...args) {
            AbstractLocationDirective.apply(this, args);
        }
        StickRightLocationDirective.prototype.getRecommendedPosition = function(lineNo) {
            const center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
            const requiredWidth = (this.axis.linesIncluded + 0.5) * settings.recommendedConnectionWidth;
            return center - requiredWidth + settings.recommendedConnectionWidth * lineNo;
        };
        return StickRightLocationDirective;
    });
