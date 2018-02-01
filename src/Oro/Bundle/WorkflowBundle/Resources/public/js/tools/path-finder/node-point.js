define(['./extends', './point2d', './directions', './connection'
], function(__extends, Point2d, directions, Connection) {
    'use strict';

    var directionIds = [
        directions.BOTTOM_TO_TOP.id,
        directions.TOP_TO_BOTTOM.id,
        directions.LEFT_TO_RIGHT.id,
        directions.RIGHT_TO_LEFT.id
    ];

    __extends(NodePoint, Point2d);

    /**
     * Constructs graph node at [x, y] position
     *
     * @param {number} x
     * @param {number} y
     * @constructor
     */
    function NodePoint(x, y) {
        Point2d.call(this, x, y);
        this.connections = {};
        this.stale = false;
        this.used = false;
    }

    /**
     * Returns recommended X coordinate for this node
     *
     * @type {number}
     */
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

    /**
     * Returns recommended Y coordinate for this node
     *
     * @type {number}
     */
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

    /**
     * Returns recommended location for this node
     *
     * @type {Point2d}
     */
    Object.defineProperty(NodePoint.prototype, 'recommendedPoint', {
        get: function() {
            return new Point2d(this.recommendedX, this.recommendedY);
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Connects this node with another one using direction
     *
     * @param {Point2d} direction
     * @param {NodePoint} node
     */
    NodePoint.prototype.connect = function(direction, node) {
        if (this.connections[direction.id]) {
            this.connections[direction.id].remove();
        }
        if (node) {
            new Connection(this, node, direction);
        }
    };

    /**
     * Iterator for all connections
     *
     * @param {Function} fn
     */
    NodePoint.prototype.eachConnection = function(fn) {
        for (var i = 0; i < directionIds.length; i++) {
            var conn = this.connections[directionIds[i]];
            if (conn) {
                fn(conn);
            }
        }
    };

    /**
     * Iterator for connections that could be traversed after coming to node from 'from' connection
     *
     * @param {Connection} from
     * @param {Function} fn
     */
    NodePoint.prototype.eachTraversableConnection = function(from, fn) {
        for (var i = 0; i < directionIds.length; i++) {
            var conn = this.connections[directionIds[i]];
            if (conn && conn !== from && conn.traversable) {
                fn(conn.second(this), conn);
            }
        }
    };

    /**
     * Creates copy of this node
     *
     * @returns {NodePoint}
     */
    NodePoint.prototype.clone = function() {
        var node = new NodePoint(this.x, this.y);
        node.vAxis = this.vAxis;
        node.hAxis = this.hAxis;
        return node;
    };

    /**
     * Finds and returns node at direction
     *
     * @param {Point2d} direction
     * @returns {NodePoint}
     */
    NodePoint.prototype.nextNode = function(direction) {
        var connection = this.connections[direction.id];
        return connection ? connection.second(this) : null;
    };

    /**
     * Draws nodePoint
     *
     * @param {string} color
     * @param {number} radius
     */
    NodePoint.prototype.draw = function(color, radius) {
        this.recommendedPoint.draw(color, radius);
    };

    return NodePoint;
});
