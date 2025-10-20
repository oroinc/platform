/**
 * Creates interval on 1d surface
 *
 * @param min
 * @param max
 * @constructor
 */
function Interval1d(min, max) {
    if (min > max) {
        throw new RangeError('Start of interval shouldn\'t be more that its end');
    }
    this.min = min;
    this.max = max;
}

/**
 * Width of interval
 */
Object.defineProperty(Interval1d.prototype, 'width', {
    get: function() {
        return this.max - this.min;
    },
    set: function(v) {
        this.max = v + this.min;
    },
    enumerable: true,
    configurable: true
});

/**
 * Returns interval intersection
 *
 * @param {Interval1d} s
 * @returns {Interval1d}
 */
Interval1d.prototype.intersection = function(s) {
    const min = Math.max(this.min, s.min);
    const max = Math.min(this.max, s.max);
    return min > max ? null : new Interval1d(min, max);
};

/**
 * Returns interval union. Please overview the code to see difference with mathematical union (set theory)
 *
 * @param {Interval1d} s
 * @returns {Interval1d}
 */
Interval1d.prototype.union = function(s) {
    return new Interval1d(Math.min(this.min, s.min), Math.max(this.max, s.max));
};

/**
 * Returns true if passed coordinate is within interval
 *
 * @param {number} coordinate
 * @returns {boolean}
 */
Interval1d.prototype.contains = function(coordinate) {
    return (coordinate < this.min) ? false : coordinate <= this.max;
};

/**
 * Returns true if passed coordinate is within interval (non inclusive)
 *
 * @param {number} coordinate
 * @returns {boolean}
 */
Interval1d.prototype.containsNonInclusive = function(coordinate) {
    return (coordinate <= this.min) ? false : coordinate < this.max;
};

/**
 * Returns distance to specified coordinate. If interval contains coordinate return 0;
 *
 * @param coordinate
 * @returns {number}
 */
Interval1d.prototype.distanceTo = function(coordinate) {
    return (coordinate < this.min) ? this.min - coordinate : (coordinate > this.max ? coordinate - this.max : 0);
};

export default Interval1d;
