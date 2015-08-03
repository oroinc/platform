define(['./extends', './interval2d', './point2d', './settings'], function(__extends, Interval2d, Point2d, settings) {
    'use strict';
    __extends(Connection, Interval2d);
    function Connection(a, b, vector) {
        Interval2d.call(this, a, b);
        this.costMultiplier = 1;
        this.traversable = true;
        this.uid = Connection.uidCounter++;
        if (!vector) {
            vector = b.sub(a).unitVector;
        }
        this.vector = vector;
        a.connections[vector.id] = this;
        b.connections[vector.rot180().id] = this;
        if (this.axis.graph.isConnectionUnderRect(this)) {
            this.costMultiplier *= settings.overBlockLineCostMultiplier;
        }
    }
    Object.defineProperty(Connection.prototype, 'cost', {
        get: function() {
            return this.length * this.axis.costMultiplier * this.costMultiplier +
                (this.a.used || this.b.used ? settings.crossPathCost : 0);
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Connection.prototype, 'axis', {
        get: function() {
            return this.a.vAxis === this.b.vAxis ? this.a.vAxis : this.a.hAxis;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Connection.prototype, 'leftSibling', {
        get: function() {
            var leftPoint = this.a.nextNode(this.vector.rot90());
            if (leftPoint && leftPoint.x === this.a.x && leftPoint.y === this.a.y) {
                return leftPoint.connections[this.directionFrom(this.a).id];
            }
            return null;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Connection.prototype, 'rightSibling', {
        get: function() {
            var rightPoint = this.a.nextNode(this.vector.rot270());
            if (rightPoint && rightPoint.x === this.a.x && rightPoint.y === this.a.y) {
                return rightPoint.connections[this.directionFrom(this.a).id];
            }
            return null;
        },
        enumerable: true,
        configurable: true
    });
    Connection.prototype.remove = function() {
        this.a.removeConnection(this);
        this.b.removeConnection(this);
    };
    Connection.prototype.second = function(first) {
        return (first === this.a) ? this.b : this.a;
    };
    Connection.prototype.directionFrom = function(first) {
        return this.b === first ? this.vector.rot180() : this.vector;
    };
    Connection.prototype.draw = function(color) {
        if (color === void 0) {
            color = 'green';
        }
        (new Interval2d(
            new Point2d(this.a.recommendedX, this.a.recommendedY),
            new Point2d(this.b.recommendedX, this.b.recommendedY))
        ).draw(color);
    };
    Connection.uidCounter = 0;
    return Connection;
});
