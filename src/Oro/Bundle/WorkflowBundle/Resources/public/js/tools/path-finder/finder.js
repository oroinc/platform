define(function(require, exports, module) {
    'use strict';

    const settings = require('oroworkflow/js/tools/path-finder/settings');
    const Path = require('oroworkflow/js/tools/path-finder/path');
    const directions = require('oroworkflow/js/tools/path-finder/directions');
    const ComplexityError = require('oroworkflow/js/tools/path-finder/complexity-error');
    const config = require('module-config').default(module.id);

    const MAX_COMPLEXITY_NUMBER = config.MAX_COMPLEXITY_NUMBER || 80000;

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
        if (!this.to.length) {
            throw new Error('Please add required destination before this call (use addTo())');
        }
        const siblings = path.getSiblings();
        for (let i = siblings.length - 1; i >= 0; i--) {
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
        let from = -1;
        let to = this.from.length;
        while (to - from > 1) {
            const mid = Math.floor((from + to) / 2);
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
        const index = this.binarySearch(path.heuristic);
        this.from.splice(index + 1, 0, path);
    };

    /**
     * Adds required path end chunk
     *
     * @param {Path} path
     */
    Finder.prototype.addTo = function(path) {
        this.to.push(...path.getSiblings());
    };

    /**
     * Executes search
     *
     * @returns {Path}
     */
    Finder.prototype.find = function() {
        let i;
        let result;
        let current;
        let operationsCount = 0;
        // that must be optimized
        const from = this.from;
        const to = this.to;
        // that must be optimized
        const closedNodes = [];
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
            if (operationsCount > MAX_COMPLEXITY_NUMBER) {
                throw new ComplexityError('Maximum iterations limit reached. heu=' + current.heuristic);
            }
            operationsCount++;
            closedNodes[current.uid] = true;
            // replaced current.eachAvailableStep(registerPossiblePath);
            const toNode = current.toNode;
            for (i = 0; i < directionIds.length; i++) {
                const conn = toNode.connections[directionIds[i]];
                if (conn && conn !== current && conn.traversable) {
                    const vectorId = conn === toNode ? conn.vector.id : -conn.vector.id;
                    const pathUid = toNode.uid * 8 + shortDirectionUid[vectorId];
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
        for (let i = pathes.length - 1; i >= 0; i--) {
            const current = pathes[i];
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
        let anglesCount;
        let distance = this.to[0].toNode.simpleDistanceTo(path.toNode);
        let sub = 0;
        for (let i = this.to.length - 1; i >= 0; i--) {
            if (path.canJoinWith(this.to[i])) {
                sub = path.connection.cost;
                distance = 0;
                anglesCount = 0;
                break;
            }
        }
        if (anglesCount === undefined) {
            const newFrom = path.toNode;
            const to = this.to[0].toNode;
            const midNodesDirection = to.sub(newFrom);
            if (Math.abs(midNodesDirection.x) < 0.00001) {
                midNodesDirection.x = 0;
            }
            if (Math.abs(midNodesDirection.y) < 0.00001) {
                midNodesDirection.y = 0;
            }
            midNodesDirection.x = Math.sign(midNodesDirection.x);
            midNodesDirection.y = Math.sign(midNodesDirection.y);
            const newDirection = path.connection.directionFrom(path.fromNode);
            const toDirection = this.to[0].connection.directionFrom(to);
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

