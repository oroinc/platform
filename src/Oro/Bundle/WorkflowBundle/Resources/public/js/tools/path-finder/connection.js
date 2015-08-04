define([
    './extends', './interval2d', './node-point', './point2d', './settings'
], function(__extends, Interval2d, NodePoint, Point2d, settings) {
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
    Connection.prototype.remove = function() {
        this.a.removeConnection(this);
        this.b.removeConnection(this);
    };
    Connection.prototype.second = function(first) {
        if (first === this.a) {
            return this.b;
        } else if (first === this.b) {
            return this.a;
        }
    };
    Connection.prototype.directionFrom = function(first) {
        if (first === this.a) {
            return this.vector;
        } else if (first === this.b) {
            return this.vector.rot180();
        }
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
