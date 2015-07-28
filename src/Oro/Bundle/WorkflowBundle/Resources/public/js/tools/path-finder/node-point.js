define(['./extends', './point2d', './directions', './connection'],
    function(__extends, Point2d, directions, Connection) {
    'use strict';

    var directionIds = [
        directions.BOTTOM_TO_TOP.id,
        directions.TOP_TO_BOTTOM.id,
        directions.LEFT_TO_RIGHT.id,
        directions.RIGHT_TO_LEFT.id
    ];

    __extends(NodePoint, Point2d);
    function NodePoint(x, y) {
        Point2d.call(this, x, y);
        this.connections = {};
        this.stale = false;
        this.used = false;
    }
    Object.defineProperty(NodePoint.prototype, 'recommendedX', {
        get: function() {
            if (this.vAxis) {
                var recommendation = this.vAxis.recommendedPosition;
                if (recommendation !== undefined) {
                    return recommendation;
                }
            }
            return this.x;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(NodePoint.prototype, 'recommendedY', {
        get: function() {
            if (this.hAxis) {
                var recommendation = this.hAxis.recommendedPosition;
                if (recommendation !== undefined) {
                    return recommendation;
                }
            }
            return this.y;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(NodePoint.prototype, 'recommendedPoint', {
        get: function() {
            return new Point2d(this.recommendedX, this.recommendedY);
        },
        enumerable: true,
        configurable: true
    });
    NodePoint.prototype.connect = function(direction, node) {
        if (this.connections[direction.id]) {
            this.connections[direction.id].remove();
        }
        /* jshint ignore:start */
        if (node) {
            new Connection(this, node, direction);
        }
        /* jshint ignore:end */
    };
    NodePoint.prototype.removeConnection = function(conn) {
        for (var key in this.connections) {
            if (this.connections.hasOwnProperty(key)) {
                if (this.connections[key] === conn) {
                    this.connections[key] = null;
                    return;
                }
            }
        }
    };
    NodePoint.prototype.eachConnection = function(fn) {
        for (var i = 0; i < directionIds.length; i++) {
            var conn = this.connections[directionIds[i]];
            if (conn) {
                fn(conn);
            }
        }
    };
    NodePoint.prototype.eachTraversableConnection = function(from, fn) {
        for (var i = 0; i < directionIds.length; i++) {
            var conn = this.connections[directionIds[i]];
            if (conn && conn !== from && conn.traversable) {
                fn(conn.second(this), conn);
            }
        }
    };
    NodePoint.prototype.clone = function() {
        var node = new NodePoint(this.x, this.y);
        node.vAxis = this.vAxis;
        node.hAxis = this.hAxis;
        return node;
    };
    NodePoint.prototype.nextNode = function(direction) {
        var connection = this.connections[direction.id];
        return connection ? connection.second(this) : null;
    };
    NodePoint.prototype.draw = function(color, radius) {
        this.recommendedPoint.draw(color, radius);
    };
    return NodePoint;
});
