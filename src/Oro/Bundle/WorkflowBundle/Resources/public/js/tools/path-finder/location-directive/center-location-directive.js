define(['../extends', './abstract-location-directive', '../settings'],
    function(__extends, AbstractLocationDirective, settings) {
        'use strict';
        __extends(CenterLocationDirective, AbstractLocationDirective);
        function CenterLocationDirective(...args) {
            AbstractLocationDirective.apply(this, args);
        }
        CenterLocationDirective.prototype.getRecommendedPosition = function(lineNo) {
            const center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
            const requiredWidth = this.axis.linesIncluded * settings.recommendedConnectionWidth;
            return center - requiredWidth / 2 + settings.recommendedConnectionWidth * lineNo;
        };
        return CenterLocationDirective;
    });
