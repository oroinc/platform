define(['./util', './line2d'], function(util, Line2d) {
    'use strict';
    function Interval2d(a, b) {
        this.a = a;
        this.b = b;
    }
    Object.defineProperty(Interval2d.prototype, 'length', {
        get: function() {
            if (this._length === void 0) {
                this._length = this.a.distanceTo(this.b);
            }
            return this._length;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Interval2d.prototype, 'simpleLength', {
        get: function() {
            if (this._simpleLength === void 0) {
                this._simpleLength = this.a.simpleDistanceTo(this.b);
            }
            return this._simpleLength;
        },
        enumerable: true,
        configurable: true
    });
    Interval2d.prototype.crosses = function(interval) {
        return this.getCrossPoint(interval) !== null;
    };
    Interval2d.prototype.getCrossPoint = function(interval) {
        if (interval.simpleLength === 0) {
            return this.includesPoint(interval.a) ? interval.a : null;
        } else if (this.simpleLength === 0) {
            return interval.includesPoint(this.a) ? this.a : null;
        }
        var point = this.line.intersection(interval.line);
        if (!isNaN(point.x)) {
            var v1;
            var v2;
            if (this.a.x !== this.b.x) {
                // compare by x
                v1 = util.between(point.x, this.a.x, this.b.x);
            } else {
                // compare by y
                v1 = util.between(point.y, this.a.y, this.b.y);
            }
            if (interval.a.x !== interval.b.x) {
                // compare by x
                v2 = util.between(point.x, interval.a.x, interval.b.x);
            } else {
                // compare by y
                v2 = util.between(point.y, interval.a.y, interval.b.y);
            }
            if (v1 && v2) {
                return point;
            }
        }
        return null;
    };
    Interval2d.prototype.includesPoint = function(point) {
        var line = this.line;
        return line.slope === Infinity ?
            (point.x === this.a.x && util.between(point.y, this.a.y, this.b.y)) :
            (util.between(point.x, this.a.x, this.b.x) && point.y === line.intercept + point.x * line.slope);
    };
    Interval2d.prototype.crossesNonInclusive = function(interval) {
        var point = this.line.intersection(interval.line);
        if (!isNaN(point.x)) {
            if (this.a.x !== this.b.x) {
                // compare by x
                return util.betweenNonInclusive(point.x, this.a.x, this.b.x);
            } else {
                // compare by y
                return util.betweenNonInclusive(point.y, this.a.y, this.b.y);
            }
        }
        return false;
    };
    Interval2d.prototype.crossesRect = function(rect) {
        return rect.topSide.crosses(this) ||
            rect.bottomSide.crosses(this) ||
            rect.leftSide.crosses(this) ||
            rect.rightSide.crosses(this);
    };
    Object.defineProperty(Interval2d.prototype, 'line', {
        get: function() {
            if (this._line) {
                return this._line;
            }
            var slope = (this.a.y - this.b.y) / (this.a.x - this.b.x);
            if (slope === Infinity || slope === -Infinity) {
                this._line = new Line2d(Infinity, this.a.x);
            } else {
                this._line = new Line2d(slope, this.a.y - this.a.x * slope);
            }
            return this._line;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Interval2d.prototype, 'center', {
        get: function() {
            if (this._center === void 0) {
                this._center = this.a.add(this.b).mul(0.5);
            }
            return this._center;
        },
        enumerable: true,
        configurable: true
    });
    Interval2d.prototype.draw = function(color) {
        if (color === void 0) {
            color = 'green';
        }
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute;width:1000px;height:1000px;">' +
            '<path stroke-width="1" stroke="' + color +
            '" fill="none" d="' + 'M ' + this.a.x + ' ' + this.a.y + ' L ' + this.b.x + ' ' + this.b.y +
            '"></path></svg>');
    };
    return Interval2d;
});
