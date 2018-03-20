define(function(require) {
    'use strict';

    var util = require('./util');
    var Line2d = require('./line2d');
    var draw = require('./draw');

    /**
     * Interval on 2d surface specified by two points
     *
     * @param {Point2d} a
     * @param {Point2d} b
     * @constructor
     */
    function Interval2d(a, b) {
        this.a = a;
        this.b = b;
    }

    /**
     * Start point
     *
     * @type {Point2d}
     */
    Object.defineProperty(Interval2d.prototype, 'a', {
        get: function() {
            return this._a;
        },
        set: function(value) {
            delete this._length;
            delete this._simpleLength;
            delete this._line;
            delete this._center;
            this._a = value;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * End Point
     *
     * @type {Point2d}
     */
    Object.defineProperty(Interval2d.prototype, 'b', {
        get: function() {
            return this._b;
        },
        set: function(value) {
            delete this._length;
            delete this._simpleLength;
            delete this._line;
            delete this._center;
            this._b = value;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Returns distance between start and end point
     *
     * @type {number}
     */
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

    /**
     * Simplified distance version, if only horizontal and vertical moves encounted.
     *
     * @type {number}
     */
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

    /**
     * Returns true if interval crosses another
     *
     * @param {Interval2d} interval
     * @returns {boolean}
     */
    Interval2d.prototype.crosses = function(interval) {
        return this.getCrossPoint(interval) !== null;
    };

    /**
     * Returns cross point with another interval or null if it doesn't exists.
     *
     * @param {Interval2d} interval
     * @returns {Point2d}
     */
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

    /**
     * Returns true if point is on interval
     *
     * @param {Point2d} point
     * @returns {boolean}
     */
    Interval2d.prototype.includesPoint = function(point) {
        var line = this.line;
        return line.slope === Infinity
            ? (point.x === this.a.x && util.between(point.y, this.a.y, this.b.y))
            : (util.between(point.x, this.a.x, this.b.x) && point.y === line.intercept + point.x * line.slope);
    };

    /**
     * Returns whether interval crosses rectangle or not
     *
     * @param {Rectangle} rect
     * @returns {boolean|*}
     */
    Interval2d.prototype.crossesRect = function(rect) {
        return rect.topSide.crosses(this) ||
            rect.bottomSide.crosses(this) ||
            rect.leftSide.crosses(this) ||
            rect.rightSide.crosses(this);
    };

    /**
     * Returns line which goes over this interval
     *
     * @type {Line2d}
     */
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

    /**
     * Returns point at center of this interval
     *
     * @type {Point2d}
     */
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

    /**
     * Draws interval
     *
     * @param {string} color
     */
    Interval2d.prototype.draw = function(color) {
        if (color === void 0) {
            color = 'green';
        }
        draw.line(this.a, this.b, color);
    };

    return Interval2d;
});
