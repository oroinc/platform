define(function() {
    'use strict';
    function Interval1d(min, max) {
        if (min > max) {
            throw new RangeError('Start of interval shouldn\'t be more that its end');
        }
        this.min = min;
        this.max = max;
    }
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
    Interval1d.prototype.intersection = function(s) {
        var min = Math.max(this.min, s.min);
        var max = Math.min(this.max, s.max);
        return min > max ? null : new Interval1d(min, max);
    };
    Interval1d.prototype.union = function(s) {
        return new Interval1d(Math.min(this.min, s.min), Math.max(this.max, s.max));
    };
    Interval1d.prototype.contains = function(coordinate) {
        return (coordinate < this.min) ? false : coordinate <= this.max;
    };
    Interval1d.prototype.containsNonInclusive = function(coordinate) {
        return (coordinate <= this.min) ? false : coordinate < this.max;
    };
    Interval1d.prototype.distanceTo = function(coordinate) {
        return (coordinate < this.min) ? this.min - coordinate : (coordinate > this.max ? coordinate - this.max : 0);
    };
    return Interval1d;
});
