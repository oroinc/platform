define(function() {
    'use strict';
    function Point2d(x, y) {
        this.x = x;
        this.y = y;
        this.uid = Point2d.uidCounter++;
    }
    Object.defineProperty(Point2d.prototype, 'id', {
        get: function() {
            return this.x * 200000 + this.y;
        },
        enumerable: true,
        configurable: true
    });
    Point2d.prototype.simpleDistanceTo = function(point) {
        var dx = point.x - this.x;
        var dy = point.y - this.y;
        return Math.abs(dx) + Math.abs(dy);
    };
    Point2d.prototype.distanceTo = function(point) {
        var dx = point.x - this.x;
        var dy = point.y - this.y;
        return Math.sqrt(dx * dx + dy * dy);
    };
    Point2d.prototype.opposite = function() {
        return new Point2d(-this.x, -this.y);
    };
    Point2d.prototype.add = function(point) {
        return new Point2d(this.x + point.x, this.y + point.y);
    };
    Point2d.prototype.sub = function(point) {
        return new Point2d(this.x - point.x, this.y - point.y);
    };
    Point2d.prototype.mul = function(n) {
        return new Point2d(this.x * n, this.y * n);
    };
    Object.defineProperty(Point2d.prototype, 'length', {
        get: function() {
            return Math.sqrt(this.x * this.x + this.y * this.y);
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Point2d.prototype, 'unitVector', {
        get: function() {
            var len = this.length;
            return new Point2d(this.x / len, this.y / len);
        },
        enumerable: true,
        configurable: true
    });
    Point2d.prototype.draw = function(color, radius) {
        if (color === void 0) {
            color = 'red';
        }
        if (radius === void 0) {
            radius = 2;
        }
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute;width:1000px;height:1000px;">' +
            '<circle fill="' + color + '" r="' + radius + '" ' + 'cx="' + this.x + '" cy="' + this.y +
            '"></circle></svg>');
    };
    Point2d.prototype.drawText = function(text, color) {
        if (color === void 0) {
            color = 'black';
        }
        document.body.insertAdjacentHTML('beforeEnd', '<svg style="position:absolute;width:1000px;height:1000px;">' +
            '<text x="' + (this.x + 5) + '" y="' + (this.y - 5) + '" fill="' + color + '" font-size="10">' +
            text + '</text></svg>');
    };
    Point2d.prototype.rot90 = function() {
        return new Point2d(this.y, -this.x);
    };
    Point2d.prototype.rot180 = function() {
        return new Point2d(-this.x, -this.y);
    };
    Point2d.prototype.rot270 = function() {
        return new Point2d(-this.y, this.x);
    };
    Point2d.prototype.abs = function() {
        return new Point2d(Math.abs(this.x), Math.abs(this.y));
    };
    Point2d.prototype.clone = function() {
        return new Point2d(this.x, this.y);
    };
    Point2d.uidCounter = 0;
    return Point2d;
});
