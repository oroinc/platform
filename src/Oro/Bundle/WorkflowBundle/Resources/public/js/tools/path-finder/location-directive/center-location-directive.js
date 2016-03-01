define(['../extends', './abstract-location-directive', '../settings'],
    function(__extends, AbstractLocationDirective, settings) {
        'use strict';
        __extends(CenterLocationDirective, AbstractLocationDirective);
        function CenterLocationDirective() {
            AbstractLocationDirective.apply(this, arguments);
        }
        CenterLocationDirective.prototype.getRecommendedPosition = function(lineNo) {
            var center = this.axis.isVertical ? this.axis.a.x : this.axis.a.y;
            var requiredWidth = this.axis.linesIncluded * settings.recommendedConnectionWidth;
            return center - requiredWidth / 2 + settings.recommendedConnectionWidth * lineNo;
        };
        return CenterLocationDirective;
    });
