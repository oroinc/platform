define(function() {
    'use strict';

    var sign = Math.hasOwnProperty('sign') ? Math.sign : function(x) {
        if (+x === x) {
            return x > 0 ? 1 : (x < 0 ? -1 : 0);
        }
    };

    /**
     * Constructs Points on 2d surface
     *
     * @param {number} x
     * @param {number} y
     * @constructor
     */
    function Point2d(x, y) {
        this.x = x;
        this.y = y;
        this.uid = Point2d.uidCounter++;
        /**
         * Point identifier
         * @type {number}
         */
        this.id = this.x * 200000 + this.y;
    }

    Point2d.uidCounter = 0;

    /**
     * Returns simplified distance to another point
     *
     * @param {Point2d} point
     * @returns {number}
     */
    Point2d.prototype.simpleDistanceTo = function(point) {
        var dx = point.x - this.x;
        var dy = point.y - this.y;
        return Math.abs(dx) + Math.abs(dy);
    };

    /**
     * Returns distance to another point
     *
     * @param {Point2d} point
     * @returns {number}
     */
    Point2d.prototype.distanceTo = function(point) {
        var dx = point.x - this.x;
        var dy = point.y - this.y;
        return Math.sqrt(dx * dx + dy * dy);
    };

    /**
     * Reflects point from coordinate center [0, 0]
     *
     * @returns {Point2d}
     */
    Point2d.prototype.opposite = function() {
        return new Point2d(-this.x, -this.y);
    };

    /**
     * Sum two vectors
     *
     * @param {Point2d} point
     * @returns {Point2d}
     */
    Point2d.prototype.add = function(point) {
        return new Point2d(this.x + point.x, this.y + point.y);
    };

    /**
     * Subtract two vectors
     *
     * @param {Point2d} point
     * @returns {Point2d}
     */
    Point2d.prototype.sub = function(point) {
        return new Point2d(this.x - point.x, this.y - point.y);
    };

    /**
     * Multiplies point in <n> times
     *
     * @param {number} n
     * @returns {Point2d}
     */
    Point2d.prototype.mul = function(n) {
        return new Point2d(this.x * n, this.y * n);
    };

    /**
     * Returns distance to coordinate center
     */
    Object.defineProperty(Point2d.prototype, 'length', {
        get: function() {
            if (this._length === void 0) {
                this._length = Math.sqrt(this.x * this.x + this.y * this.y);
            }
            return this._length;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Returns vector with 1 unit length and same direction
     *
     * @returns {Point2d}
     */
    Object.defineProperty(Point2d.prototype, 'unitVector', {
        get: function() {
            if (this.x === 0) {
                return new Point2d(this.x, sign(this.y));
            }
            if (this.y === 0) {
                return new Point2d(sign(this.x), this.y);
            }
            var len = this.length;
            return new Point2d(this.x / len, this.y / len);
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Rotates vector clockwise by 90 degrees
     *
     * @returns {Point2d}
     */
    Point2d.prototype.rot90 = function() {
        return new Point2d(this.y, -this.x);
    };

    /**
     * Rotates vector clockwise by 180 degrees
     *
     * @returns {Point2d}
     */
    Point2d.prototype.rot180 = function() {
        return new Point2d(-this.x, -this.y);
    };

    /**
     * Rotates vector clockwise by 270 degrees
     *
     * @returns {Point2d}
     */
    Point2d.prototype.rot270 = function() {
        return new Point2d(-this.y, this.x);
    };

    /**
     * Return obsolete value of vector
     *
     * @returns {Point2d}
     */
    Point2d.prototype.abs = function() {
        return new Point2d(Math.abs(this.x), Math.abs(this.y));
    };

    /**
     * Return clone of point
     *
     * @returns {Point2d}
     */
    Point2d.prototype.clone = function() {
        return new Point2d(this.x, this.y);
    };
    return Point2d;
});
