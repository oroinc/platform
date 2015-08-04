define(['./directions', './settings'], function(directions, settings) {
    'use strict';

    var directionIds = [
        directions.BOTTOM_TO_TOP.id,
        directions.TOP_TO_BOTTOM.id,
        directions.LEFT_TO_RIGHT.id,
        directions.RIGHT_TO_LEFT.id
    ];

    var shortDirectionUid = {};
    for (var i = directionIds.length - 1; i >= 0; i--) {
        shortDirectionUid[directionIds[i]] = i;
    }

    function Path(connection, fromNode, previous) {
        this.connection = connection;
        this.previous = previous;
        this.fromNode = fromNode;
        this.cost = (this.previous ? this.previous.cost : 0) + this.connection.cost;
        if (this.previous && this.connection.directionFrom(this.fromNode).id !==
            this.previous.connection.directionFrom(this.previous.fromNode).id) {
            this.cost += settings.cornerCost;
        }
    }
    Object.defineProperty(Path.prototype, 'uid', {
        get: function() {
            if (this._uid === void 0) {
                var vectorId = this.connection.a === this.fromNode ?
                    this.connection.vector.id :
                    this.connection.vector.rot180().id;
                this._uid = this.fromNode.uid * 10 + shortDirectionUid[vectorId];
            }
            return this._uid;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Path.prototype, 'toNode', {
        get: function() {
            return this.connection.second(this.fromNode);
        },
        enumerable: true,
        configurable: true
    });
    Path.prototype.eachAvailableStep = function(fn) {
        var _this = this;
        var toNode = this.toNode;
        toNode.eachTraversableConnection(this.connection, function(to, conn) {
            fn(new Path(conn, toNode, _this));
        });
    };
    Path.prototype.canJoinWith = function(path) {
        return this.connection === path.connection && this.toNode === path.fromNode;
    };
    Path.prototype.draw = function(color) {
        if (color === void 0) {
            color = 'red';
        }
        this.connection.draw(color);
        if (this.previous) {
            this.previous.draw(color);
        }
    };
    Object.defineProperty(Path.prototype, 'allConnections', {
        get: function() {
            if (this.previous) {
                var result = this.previous.allConnections;
                result.push.apply(result, this.includedConnections);
                return result;
            }
            return this.includedConnections;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Path.prototype, 'includedConnections', {
        get: function() {
            var node = this.fromNode;
            var needle = this.toNode;
            var directionId = this.connection.directionFrom(node).id;
            var connection = node.connections[directionId];
            if (connection !== this.connection) {
                var result = [];
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
    Object.defineProperty(Path.prototype, 'allNodes', {
        get: function() {
            if (this.previous) {
                var result = this.previous.allNodes;
                result.push(this.toNode);
                return result;
            }
            return [this.fromNode, this.toNode];
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Path.prototype, 'points', {
        get: function() {
            var points = [];
            var current = this;
            var currentAxis = this.connection.axis;
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
    Path.prototype.getSiblings = function() {
        if (this.previous) {
            throw new Error('Unable to get path siblings');
        }
        var result = [this];
        var connectionDirection = this.connection.directionFrom(this.fromNode);
        var direction = connectionDirection.rot90().abs();
        var oppositeDirection = direction.rot180();
        var nextNode;
        var nextConnection;
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
    return Path;
});
