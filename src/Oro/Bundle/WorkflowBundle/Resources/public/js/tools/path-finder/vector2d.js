define(['./point2d', './line2d', './interval2d', './util'], function(Point2d, Line2d, Interval2d, util) {
    'use strict';
    var sign = Math.hasOwnProperty('sign') ? Math.sign : function(x) {
        if (+x === x) {
            return x > 0 ? 1 : (x < 0 ? -1 : 0);
        }
    };
    function Vector2d(x, y, direction) {
        this.start = new Point2d(x, y);
        this.direction = direction;
    }
    Vector2d.prototype.crosses = function(rect) {
        return this.getCrossPointWithRect(rect) !== null;
    };
    Object.defineProperty(Vector2d.prototype, 'line', {
        get: function() {
            var slope = this.direction.y / this.direction.x;
            if (slope === Infinity || slope === -Infinity) {
                return new Line2d(Infinity, this.start.x);
            }
            return new Line2d(slope, this.start.y + this.start.x * slope);
        },
        enumerable: true,
        configurable: true
    });
    Vector2d.prototype.getCrossPointWithRect = function(rect) {
        var crossPoint = null;
        switch (this.direction.y) {
            case -1:
                crossPoint = this.getCrossPointWithInterval(rect.bottomSide);
                break;
            case 1:
                crossPoint = this.getCrossPointWithInterval(rect.topSide);
                break;
        }
        switch (this.direction.x) {
            case -1:
                crossPoint = this.getCrossPointWithInterval(rect.rightSide);
                break;
            case 1:
                crossPoint = this.getCrossPointWithInterval(rect.leftSide);
                break;
        }
        return crossPoint;
    };
    Vector2d.prototype.getCrossPointWithInterval = function(interval) {
        var intersectionPoint = this.line.intersection(interval.line);
        if (!isNaN(intersectionPoint.x) && Math.abs(intersectionPoint.x) !== Infinity) {
            var relativePoint = intersectionPoint.sub(this.start);
            if ((sign(relativePoint.x) === sign(this.direction.x)) &&
                (sign(relativePoint.y) === sign(this.direction.y))) {
                if (interval.a.x !== interval.b.x) {
                    if (util.between(intersectionPoint.x, interval.a.x, interval.b.x)) {
                        return intersectionPoint;
                    }
                } else {
                    if (util.between(intersectionPoint.y, interval.a.y, interval.b.y)) {
                        return intersectionPoint;
                    }
                }
            }
        }
        return null;
    };
    Vector2d.prototype.draw = function(color) {
        if (color === void 0) {
            color = 'rgba(0,0,0,0.7)';
        }
        this.start.draw(color, 3);
        var interval = new Interval2d(this.start, this.start.add(this.direction.unitVector.mul(100000)));
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute;width:1000px;height: 1000px;">' +
            '<path stroke-width="1" stroke="' + color + '" fill="none" d="' +
            'M ' + interval.a.x + ' ' + interval.a.y + ' L ' + interval.b.x + ' ' + interval.b.y +
            '"></path></svg>');
    };
    return Vector2d;
});
