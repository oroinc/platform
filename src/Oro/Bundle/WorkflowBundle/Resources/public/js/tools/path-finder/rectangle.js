define(['./interval1d', './interval2d', './point2d'], function(Interval1d, Interval2d, Point2d) {
    'use strict';
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
    Object.defineProperty(Rectangle.prototype, 'center', {
        get: function() {
            return new Point2d((this.left + this.right) / 2, (this.top + this.bottom) / 2);
        },
        enumerable: true,
        configurable: true
    });
    Rectangle.prototype.clone = function() {
        return new Rectangle(this.left, this.top, this.width, this.height);
    };
    Rectangle.prototype.intersection = function(box) {
        return new Rectangle(this.horizontalInterval.intersection(box.horizontalInterval),
            this.verticalInterval.intersection(box.verticalInterval));
    };
    Rectangle.prototype.union = function(box) {
        return new Rectangle(this.horizontalInterval.union(box.horizontalInterval),
            this.verticalInterval.union(box.verticalInterval));
    };
    Object.defineProperty(Rectangle.prototype, 'isValid', {
        get: function() {
            return this.horizontalInterval.isValid && this.verticalInterval.isValid;
        },
        enumerable: true,
        configurable: true
    });
    Rectangle.prototype.relative = function(point) {
        return new Rectangle(this.left - point.x, this.top - point.y, this.width, this.height);
    };
    Rectangle.prototype.distanceToPoint = function(point) {
        var dx = this.horizontalInterval.distanceTo(point.x);
        var dy = this.verticalInterval.distanceTo(point.y);
        return Math.sqrt(dx * dx + dy * dy);
    };
    Object.defineProperty(Rectangle.prototype, 'topSide', {
        get: function() {
            return new Interval2d(new Point2d(this.left, this.top), new Point2d(this.right, this.top));
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Rectangle.prototype, 'bottomSide', {
        get: function() {
            return new Interval2d(new Point2d(this.left, this.bottom), new Point2d(this.right, this.bottom));
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Rectangle.prototype, 'leftSide', {
        get: function() {
            return new Interval2d(new Point2d(this.left, this.top), new Point2d(this.left, this.bottom));
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Rectangle.prototype, 'rightSide', {
        get: function() {
            return new Interval2d(new Point2d(this.right, this.top), new Point2d(this.right, this.bottom));
        },
        enumerable: true,
        configurable: true
    });
    Rectangle.prototype.eachSide = function(fn) {
        fn(this.leftSide);
        fn(this.bottomSide);
        fn(this.rightSide);
        fn(this.topSide);
    };
    Rectangle.prototype.draw = function(color) {
        if (color === void 0) {
            color = 'violet';
        }
        this.eachSide(function(side) {
            return side.draw(color);
        });
    };
    Rectangle.prototype.containsPoint = function(point) {
        return this.horizontalInterval.containsNonInclusive(point.x) &&
            this.verticalInterval.containsNonInclusive(point.y);
    };
    return Rectangle;
});
