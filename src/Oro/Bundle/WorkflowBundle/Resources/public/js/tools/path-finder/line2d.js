define(['./point2d'], function(Point2d) {
    'use strict';
    /**
     * Creates line on 2d surface
     *
     * @param {number} slope
     * @param {number} intercept
     * @constructor
     */
    function Line2d(slope, intercept) {
        this.slope = slope;
        this.intercept = intercept;
    }

    /**
     * Returns point of intersection of two lines
     *
     * @param {Line2d} line
     * @returns {Point2d}
     */
    Line2d.prototype.intersection = function(line) {
        if (this.slope === Infinity) {
            if (line.slope === Infinity) {
                return new Point2d(NaN, NaN);
            }
            return new Point2d(this.intercept, line.intercept + line.slope * this.intercept);
        }
        if (line.slope === Infinity) {
            return new Point2d(line.intercept, this.intercept + this.slope * line.intercept);
        }
        var x = (line.intercept - this.intercept) / (this.slope - line.slope);
        var y = this.slope * x + this.intercept;
        return new Point2d(x, y);
    };
    return Line2d;
});
