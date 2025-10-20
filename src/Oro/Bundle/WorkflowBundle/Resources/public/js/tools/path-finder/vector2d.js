import util from './util';
import Point2d from './point2d';
import Line2d from './line2d';
import Interval2d from './interval2d';

/**
 * Constructs vector. Vector is specified by start point and direction
 *
 * @param {number} x - start x coordinate
 * @param {number} y - start y coordinate
 * @param {Point2d} direction - vector of direction
 * @constructor
 */
function Vector2d(x, y, direction) {
    this.start = new Point2d(x, y);
    this.direction = direction;
}

/**
 * Returns true if this vector crosses rect. Note that if start point is inside rectangle this check will always
 * return false
 *
 * @param {Rectangle} rect
 * @returns {boolean}
 */
Vector2d.prototype.crosses = function(rect) {
    return this.getCrossPointWithRect(rect) !== null;
};

/**
 * Line on which this vector is placed
 * @type {Line2d}
 */
Object.defineProperty(Vector2d.prototype, 'line', {
    get: function() {
        const slope = this.direction.y / this.direction.x;
        if (slope === Infinity || slope === -Infinity) {
            return new Line2d(Infinity, this.start.x);
        }
        return new Line2d(slope, this.start.y - this.start.x * slope);
    },
    enumerable: true,
    configurable: true
});

/**
 * Calculates point where this vector crosses rectangle
 *
 * @param {Rectangle} rect
 * @returns {Point2d}
 */
Vector2d.prototype.getCrossPointWithRect = function(rect) {
    let crossPoint = null;
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

/**
 * Calculates point where this vector crosses interval
 *
 * @param {Interval2d} interval
 * @returns {Point2d}
 */
Vector2d.prototype.getCrossPointWithInterval = function(interval) {
    const intersectionPoint = this.line.intersection(interval.line);
    if (!isNaN(intersectionPoint.x) && Math.abs(intersectionPoint.x) !== Infinity) {
        const relativePoint = intersectionPoint.sub(this.start);
        if ((Math.sign(relativePoint.x) === Math.sign(this.direction.x)) &&
            (Math.sign(relativePoint.y) === Math.sign(this.direction.y))) {
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

/**
 * Draws vector
 *
 * @param {string} color
 */
Vector2d.prototype.draw = function(color) {
    if (color === void 0) {
        color = 'rgba(0,0,0,0.7)';
    }
    this.start.draw(color, 3);
    const interval = new Interval2d(this.start, this.start.add(this.direction.unitVector.mul(100000)));
    interval.draw(color);
};

export default Vector2d;
