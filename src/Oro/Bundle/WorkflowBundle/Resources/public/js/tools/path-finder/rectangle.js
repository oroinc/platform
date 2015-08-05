define(['./interval1d', './interval2d', './point2d'], function(Interval1d, Interval2d, Point2d) {
    'use strict';

    /**
     * Creates rectangle
     *
     * @param {number} left
     * @param {number} top
     * @param {number} width
     * @param {number} height
     * @constructor
     */
    function Rectangle(left, top, width, height) {
        if (left === void 0) {
            left = 0;
        }
        if (top === void 0) {
            top = 0;
        }
        if (width === void 0) {
            width = 0;
        }
        if (height === void 0) {
            height = 0;
        }
        if (left instanceof Interval1d) {
            this.horizontalInterval = left;
            this.verticalInterval = top;
        } else {
            this.horizontalInterval = new Interval1d(left, left + width);
            this.verticalInterval = new Interval1d(top, top + height);
        }
    }

    /**
     * Left side position
     * @type {number}
     */
    Object.defineProperty(Rectangle.prototype, 'left', {
        get: function() {
            return this.horizontalInterval.min;
        },
        set: function(value) {
            this.horizontalInterval.min = value;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Right side position
     * @type {number}
     */
    Object.defineProperty(Rectangle.prototype, 'right', {
        get: function() {
            return this.horizontalInterval.max;
        },
        set: function(value) {
            this.horizontalInterval.max = value;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Top side position
     * @type {number}
     */
    Object.defineProperty(Rectangle.prototype, 'top', {
        get: function() {
            return this.verticalInterval.min;
        },
        set: function(value) {
            this.verticalInterval.min = value;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Bottom side position
     * @type {number}
     */
    Object.defineProperty(Rectangle.prototype, 'bottom', {
        get: function() {
            return this.verticalInterval.max;
        },
        set: function(value) {
            this.verticalInterval.max = value;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Width
     * @type {number}
     */
    Object.defineProperty(Rectangle.prototype, 'width', {
        get: function() {
            return this.horizontalInterval.width;
        },
        set: function(value) {
            this.horizontalInterval.width = value;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Height
     * @type {number}
     */
    Object.defineProperty(Rectangle.prototype, 'height', {
        get: function() {
            return this.verticalInterval.width;
        },
        set: function(value) {
            this.verticalInterval.width = value;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Center of rectangle
     * @type {Point2d}
     */
    Object.defineProperty(Rectangle.prototype, 'center', {
        get: function() {
            return new Point2d((this.left + this.right) / 2, (this.top + this.bottom) / 2);
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Clones rectangle
     * @returns {Rectangle}
     */
    Rectangle.prototype.clone = function() {
        return new Rectangle(this.left, this.top, this.width, this.height);
    };

    /**
     * Returns intersection of two rectangles
     *
     * @param {Rectangle} box
     * @returns {Rectangle}
     */
    Rectangle.prototype.intersection = function(box) {
        return new Rectangle(this.horizontalInterval.intersection(box.horizontalInterval),
            this.verticalInterval.intersection(box.verticalInterval));
    };

    /**
     * Returns intersection of two rectangles
     *
     * @param {Rectangle} box
     * @returns {Rectangle}
     */
    Rectangle.prototype.union = function(box) {
        return new Rectangle(this.horizontalInterval.union(box.horizontalInterval),
            this.verticalInterval.union(box.verticalInterval));
    };

    /**
     * True if rectangle has positive width and height
     * @type {boolean}
     */
    Object.defineProperty(Rectangle.prototype, 'isValid', {
        get: function() {
            return this.horizontalInterval.isValid && this.verticalInterval.isValid;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Shifts rectangle on provided vector
     *
     * @param {Point2d} vector
     * @returns {Rectangle}
     */
    Rectangle.prototype.relative = function(vector) {
        return new Rectangle(this.left - vector.x, this.top - vector.y, this.width, this.height);
    };

    /**
     * Returns simple distance to point
     *
     * @param point
     * @returns {number}
     */
    Rectangle.prototype.distanceToPoint = function(point) {
        var dx = this.horizontalInterval.distanceTo(point.x);
        var dy = this.verticalInterval.distanceTo(point.y);
        return Math.sqrt(dx * dx + dy * dy);
    };

    /**
     * Top side
     * @type {Interval2d}
     */
    Object.defineProperty(Rectangle.prototype, 'topSide', {
        get: function() {
            return new Interval2d(new Point2d(this.left, this.top), new Point2d(this.right, this.top));
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Bottom side
     * @type {Interval2d}
     */
    Object.defineProperty(Rectangle.prototype, 'bottomSide', {
        get: function() {
            return new Interval2d(new Point2d(this.left, this.bottom), new Point2d(this.right, this.bottom));
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Left side
     * @type {Interval2d}
     */
    Object.defineProperty(Rectangle.prototype, 'leftSide', {
        get: function() {
            return new Interval2d(new Point2d(this.left, this.top), new Point2d(this.left, this.bottom));
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Right side
     * @type {Interval2d}
     */
    Object.defineProperty(Rectangle.prototype, 'rightSide', {
        get: function() {
            return new Interval2d(new Point2d(this.right, this.top), new Point2d(this.right, this.bottom));
        },
        enumerable: true,
        configurable: true
    });

    /**
     * all sides iterator
     * @param {Function} fn
     */
    Rectangle.prototype.eachSide = function(fn) {
        fn(this.leftSide);
        fn(this.bottomSide);
        fn(this.rightSide);
        fn(this.topSide);
    };

    /**
     * Returns true if point is within this interval
     *
     * @param {Point2d} point
     * @returns {boolean}
     */
    Rectangle.prototype.containsPoint = function(point) {
        return this.horizontalInterval.containsNonInclusive(point.x) &&
            this.verticalInterval.containsNonInclusive(point.y);
    };

    /**
     * Draws rectangle
     *
     * @param {string} color
     */
    Rectangle.prototype.draw = function(color) {
        if (color === void 0) {
            color = 'violet';
        }
        this.eachSide(function(side) {
            return side.draw(color);
        });
    };

    return Rectangle;
});
