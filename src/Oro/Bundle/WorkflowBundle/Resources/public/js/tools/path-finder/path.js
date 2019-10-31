define(['./directions', './settings'], function(directions, settings) {
    'use strict';

    const directionIds = [
        directions.BOTTOM_TO_TOP.id,
        directions.TOP_TO_BOTTOM.id,
        directions.LEFT_TO_RIGHT.id,
        directions.RIGHT_TO_LEFT.id
    ];

    const shortDirectionUid = {};
    for (let i = directionIds.length - 1; i >= 0; i--) {
        shortDirectionUid[directionIds[i]] = i;
    }

    /**
     * Path constructor
     *
     * @param {Connection} connection
     * @param {NodePoint} fromNode
     * @param {Path} previous
     * @constructor
     */
    function Path(connection, fromNode, previous) {
        this.connection = connection;
        this.previous = previous;
        this.fromNode = fromNode;
        /**
         * Node this path points to
         * @type {NodePoint}
         */
        this.toNode = connection.second(fromNode);
        this.cost = (previous ? previous.cost : 0) + connection.cost;
        if (previous && connection.directionFrom(fromNode).id !==
            previous.connection.directionFrom(previous.fromNode).id) {
            this.cost += settings.cornerCost;
        }
    }

    /**
     * Returns uid of this path.
     * Uid is fromNode and connection direction combined into one value
     *
     * @type {number}
     */
    Object.defineProperty(Path.prototype, 'uid', {
        get: function() {
            if (this._uid === void 0) {
                const vectorId = this.connection.a === this.fromNode
                    ? this.connection.vector.id
                    : this.connection.vector.rot180().id;
                this._uid = this.fromNode.uid * 8 + shortDirectionUid[vectorId];
            }
            return this._uid;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Returns true if two pathes are complementary
     *
     * @param {Path} path
     * @returns {boolean}
     */
    Path.prototype.canJoinWith = function(path) {
        return this.connection === path.connection && this.toNode === path.fromNode;
    };

    /**
     * All connection specified by path
     *
     * @type {Array.<Connection>}
     */
    Object.defineProperty(Path.prototype, 'allConnections', {
        get: function() {
            if (this.previous) {
                const result = this.previous.allConnections;
                result.push(...this.includedConnections);
                return result;
            }
            return this.includedConnections;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Connections included into current path step
     *
     * @type {Array.<Connection>}
     */
    Object.defineProperty(Path.prototype, 'includedConnections', {
        get: function() {
            let node = this.fromNode;
            const needle = this.toNode;
            const directionId = this.connection.directionFrom(node).id;
            let connection = node.connections[directionId];
            if (connection !== this.connection) {
                const result = [];
                result.push(connection);
                node = connection.second(node);
                while (node !== needle) {
                    connection = node.connections[directionId];
                    result.push(connection);
                    node = connection.second(node);
                }
                return result;
            } else {
                return [this.connection];
            }
        },
        enumerable: true,
        configurable: true
    });

    /**
     * All Nodes specified by this path
     *
     * @type {Array.<NodePoint>}
     */
    Object.defineProperty(Path.prototype, 'allNodes', {
        get: function() {
            if (this.previous) {
                const result = this.previous.allNodes;
                result.push(this.toNode);
                return result;
            }
            return [this.fromNode, this.toNode];
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Returns real points on 2d surface
     *
     * @type {Array.<Point2d>}
     */
    Object.defineProperty(Path.prototype, 'points', {
        get: function() {
            const points = [];
            let current = this;
            let currentAxis = this.connection.axis;
            points.push(this.toNode.recommendedPoint);
            while (current) {
                if (current.connection.axis !== currentAxis) {
                    points.push(current.toNode.recommendedPoint);
                    currentAxis = current.connection.axis;
                }
                if (!current.previous) {
                    points.push(current.fromNode.recommendedPoint);
                }
                current = current.previous;
            }
            return points;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Finds all siblings of current path withing current axis clones
     *
     * @returns {Array,<Path>}
     */
    Path.prototype.getSiblings = function() {
        if (this.previous) {
            throw new Error('Unable to get path siblings');
        }
        const result = [this];
        const connectionDirection = this.connection.directionFrom(this.fromNode);
        const direction = connectionDirection.rot90().abs();
        const oppositeDirection = direction.rot180();
        let nextNode;
        let nextConnection;
        nextNode = this.fromNode;
        while ((nextNode = nextNode.nextNode(direction))) {
            if (nextNode.x !== this.fromNode.x || nextNode.y !== this.fromNode.y) {
                break;
            }
            nextConnection = nextNode.connections[connectionDirection.id];
            if (nextConnection) {
                result.push(new Path(nextConnection, nextNode, null));
            }
        }
        nextNode = this.fromNode;
        while ((nextNode = nextNode.nextNode(oppositeDirection))) {
            if (nextNode.x !== this.fromNode.x || nextNode.y !== this.fromNode.y) {
                break;
            }
            nextConnection = nextNode.connections[connectionDirection.id];
            if (nextConnection) {
                result.unshift(new Path(nextConnection, nextNode, null));
            }
        }
        return result;
    };

    /**
     * Draws path
     *
     * @param {string} color
     */
    Path.prototype.draw = function(color) {
        if (color === void 0) {
            color = 'red';
        }
        this.connection.draw(color);
        if (this.previous) {
            this.previous.draw(color);
        }
    };

    return Path;
});
