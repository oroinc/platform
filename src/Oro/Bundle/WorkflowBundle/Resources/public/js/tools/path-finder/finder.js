define(['./settings', './path', './directions'], function(settings, Path, directions) {
    'use strict';
    var sign = Math.hasOwnProperty('sign') ? Math.sign : function(x) {
        if (+x === x) {
            return x > 0 ? 1 : (x < 0 ? -1 : 0);
        }
    };

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

    /**
     * Class embeds search logic
     *
     * @constructor
     */
    function Finder() {
        this.from = [];
        this.to = [];
    }

    /**
     * Register path from which need to start search
     *
     * @param {Path} path
     */
    Finder.prototype.addFrom = function(path) {
        var siblings = path.getSiblings();
        for (var i = siblings.length - 1; i >= 0; i--) {
            this.internalAddFrom(siblings[i]);
        }
    };

    /**
     * Finds closest element index with specified heuristic in array using binary search technique
     *
     * @private
     * @param {number} heuristic
     */
    Finder.prototype.binarySearch = function(heuristic) {
        var from = -1;
        var to = this.from.length;
        while (to - from > 1) {
            var mid = Math.floor((from + to) / 2);
            if (this.from[mid].heuristic > heuristic) {
                to = mid;
            } else {
                from = mid;
            }
        }
        return from;
    };

    /**
     * Internal realization of adding new available path for search
     *
     * @param {Path} path
     */
    Finder.prototype.internalAddFrom = function(path) {
        path.heuristic = this.getHeuristic(path);
        var index = this.binarySearch(path.heuristic);
        this.from.splice(index + 1, 0, path);
    };

    /**
     * Adds needed path end chunk
     *
     * @param {Path} path
     */
    Finder.prototype.addTo = function(path) {
        this.to.push.apply(this.to, path.getSiblings());
    };

    /**
     * Executes search
     *
     * @returns {Path}
     */
    Finder.prototype.find = function() {
        var i;
        var result;
        var current;
        var operationsCount = 0;
        // that must be optimized
        var from = this.from;
        var to = this.to;
        // that must be optimized
        var closedNodes = [];
        this.filterNonTraversablePathes(from);
        this.filterNonTraversablePathes(to);
        while ((current = from.shift())) {
            if (closedNodes[current.uid]) {
                continue;
            }
            for (i = 0; i < to.length; i++) {
                if (current.canJoinWith(to[i])) {
                    result = current;
                }
            }
            if (result) {
                break;
            }
            if (operationsCount > 50000) {
                throw new Error('Maximum iterations limit reached. heu=' + current.heuristic);
            }
            operationsCount++;
            closedNodes[current.uid] = true;
            // replaced current.eachAvailableStep(registerPossiblePath);
            var toNode = current.toNode;
            for (i = 0; i < directionIds.length; i++) {
                var conn = toNode.connections[directionIds[i]];
                if (conn && conn !== current && conn.traversable) {
                    var vectorId = conn === toNode ? conn.vector.id : -conn.vector.id;
                    var pathUid = toNode.uid * 8 + shortDirectionUid[vectorId];
                    if (closedNodes[pathUid]) {
                        continue;
                    }
                    this.internalAddFrom(new Path(conn, toNode, current));
                }
            }
        }
        this.operationsCount = operationsCount;
        return result;
    };

    /**
     * Removes pathes that cannot be passed from array
     *
     * @param {Array.<Path>} pathes
     */
    Finder.prototype.filterNonTraversablePathes = function(pathes) {
        for (var i = pathes.length - 1; i >= 0; i--) {
            var current = pathes[i];
            if (current.connection.a.used && current.connection.b.used) {
                pathes.splice(i, 1);
                i--;
            }
        }
    };

    /**
     * Returns heuristic value for path, please see A* (a-star) algorythm description for details
     *
     * @param {Path} path
     * @returns {number}
     */
    Finder.prototype.getHeuristic = function(path) {
        var anglesCount;
        var distance = this.to[0].toNode.simpleDistanceTo(path.toNode);
        var sub = 0;
        for (var i = this.to.length - 1; i >= 0; i--) {
            if (path.canJoinWith(this.to[i])) {
                sub = path.connection.cost;
                distance = 0;
                anglesCount = 0;
                break;
            }
        }
        if (anglesCount === undefined) {
            var newFrom = path.toNode;
            var to = this.to[0].toNode;
            var midNodesDirection = to.sub(newFrom);
            if (Math.abs(midNodesDirection.x) < 0.00001) {
                midNodesDirection.x = 0;
            }
            if (Math.abs(midNodesDirection.y) < 0.00001) {
                midNodesDirection.y = 0;
            }
            midNodesDirection.x = sign(midNodesDirection.x);
            midNodesDirection.y = sign(midNodesDirection.y);
            var newDirection = path.connection.directionFrom(path.fromNode);
            var toDirection = this.to[0].connection.directionFrom(to);
            if (newDirection.x === toDirection.x && newDirection.y === toDirection.y) {
                // 3 possible cases
                if ((newDirection.x === 0 && (midNodesDirection.y === 0 || midNodesDirection.y === newDirection.y)) ||
                    newDirection.y === 0 && (midNodesDirection.x === 0 || midNodesDirection.x === newDirection.x)) {
                    anglesCount = ((newDirection.x === 0 && midNodesDirection.x === 0) ||
                        (newDirection.y === 0 && midNodesDirection.y === 0)) ? 0 : 2;
                } else {
                    anglesCount = 4;
                }
            } else if (newDirection.x === -toDirection.x && newDirection.y === -toDirection.y) {
                // 2 possible cases
                if ((newDirection.x === 0 && midNodesDirection.x === 0) ||
                    (newDirection.y === 0 && midNodesDirection.y === 0)) {
                    anglesCount = 4;
                } else {
                    anglesCount = 2;
                }
            } else {
                // 2 possible cases
                if ((newDirection.x === 0 && (midNodesDirection.y === 0 ||
                        (newDirection.y === midNodesDirection.y &&
                            (toDirection.x === midNodesDirection.x || midNodesDirection.x === 0)))) ||
                    (newDirection.y === 0 && (midNodesDirection.x === 0 ||
                        (newDirection.x === midNodesDirection.x &&
                            (toDirection.y === midNodesDirection.y || midNodesDirection.y === 0))))) {
                    anglesCount = 1;
                } else {
                    anglesCount = 3;
                }
            }
        }
        return path.cost +
            distance * 1.00000001 + /* closer nodes should be iterated at first */
            -sub +
            anglesCount * settings.optimizationCornerCost;
    };
    return Finder;
});

