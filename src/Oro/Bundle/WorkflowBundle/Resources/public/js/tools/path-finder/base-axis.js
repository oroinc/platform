define(['./extends', './axis'], function(__extends, Axis) {
    'use strict';
    __extends(BaseAxis, Axis);
    function BaseAxis(a, b, graph, costMultiplier, leftConstraint, rightConstraint, locationDirective) {
        Axis.call(this, a, b, graph, costMultiplier);
        this.linesIncluded = 1;
        leftConstraint.axis = this;
        rightConstraint.axis = this;
        locationDirective.axis = this;
        this.leftConstraint = leftConstraint;
        this.rightConstraint = rightConstraint;
        this.locationDirective = locationDirective;
    }
    BaseAxis.createFromInterval = function(interval, graph, leftConstraint, rightConstraint, locationDirective) {
        var costMultiplier = interval.costMultiplier;
        var isVertical = interval.isVertical;
        var clone = new BaseAxis(interval.a, interval.b, graph, costMultiplier, leftConstraint, rightConstraint,
            locationDirective);
        // this is fix for zero length axises
        if (isVertical !== undefined) {
            clone.isVertical = isVertical;
        }
        return clone;
    };
    return BaseAxis;
});
