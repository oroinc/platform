define(['./settings'], function(settings) {
    'use strict';
    var sign = Math.hasOwnProperty('sign') ? Math.sign : function(x) {
        if (+x === x) {
            return x > 0 ? 1 : (x < 0 ? -1 : 0);
        }
    };

    function Finder() {
        this.from = [];
        this.to = [];
    }
    Finder.prototype.addFrom = function(path) {
        var siblings = path.getSiblings();
        for (var i = siblings.length - 1; i >= 0; i--) {
            this.internalAddFrom(siblings[i]);
        }
    };
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
    Finder.prototype.internalAddFrom = function(path) {
        path.heuristic = this.getHeuristic(path);
        var index = this.binarySearch(path.heuristic);
        this.from.splice(index + 1, 0, path);
    };
    Finder.prototype.addTo = function(path) {
        this.to.push.apply(this.to, path.getSiblings());
    };
    Finder.prototype.find = function() {
        var _this = this;
        function registerPossiblePath(path) {
            if (closedNodes[path.uid]) {
                return;
            }
            _this.internalAddFrom(path);
        }
        var result;
        var current;
        var operationsCount = 0;
        var from = this.from;
        var to = this.to;
        var closedNodes = [];
        this.filterNonTraversablePathes(from);
        this.filterNonTraversablePathes(to);
        while ((current = from.shift())) {
            if (closedNodes[current.uid]) {
                continue;
            }
            for (var i = 0; i < to.length; i++) {
                if (current.canJoinWith(to[i])) {
                    result = current;
                }
            }
            if (result) {
                break;
            }
            if (operationsCount > 10000) {
                throw new Error('Maximum iterations limit reached' + current.heuristic);
            }
            operationsCount++;
            closedNodes[current.uid] = true;
            current.eachAvailableStep(registerPossiblePath);
        }
        this.operationsCount = operationsCount;
        return result;
    };
    Finder.prototype.filterNonTraversablePathes = function(pathes) {
        for (var i = pathes.length - 1; i >= 0; i--) {
            var current = pathes[i];
            if (current.connection.a.used && current.connection.b.used) {
                pathes.splice(i, 1);
                i--;
            }
        }
    };
    Finder.prototype.getHeuristic = function(newPath) {
        var anglesCount;
        var distance = this.to[0].toNode.simpleDistanceTo(newPath.toNode);
        var sub = 0;
        for (var i = this.to.length - 1; i >= 0; i--) {
            if (newPath.canJoinWith(this.to[i])) {
                sub = this.to[i].cost;
                distance = 0;
                anglesCount = 0;
            }
        }
        if (anglesCount === undefined) {
            var newFrom = newPath.toNode;
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
            var newDirection = newPath.connection.directionFrom(newPath.fromNode);
            var toDirection = this.to[0].connection.directionFrom(to);
            if (newDirection.id === toDirection.id) {
                // 3 possible cases
                if ((newDirection.x === 0 && (midNodesDirection.y === 0 || midNodesDirection.y === newDirection.y)) ||
                    newDirection.y === 0 && (midNodesDirection.x === 0 || midNodesDirection.x === newDirection.x)) {
                    anglesCount = ((newDirection.x === 0 && midNodesDirection.x === 0) ||
                        (newDirection.y === 0 && midNodesDirection.y === 0)) ? 0 : 2;
                } else {
                    anglesCount = 4;
                }
            } else if (newDirection.id === toDirection.rot180().id) {
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
        // console.log(newDirection, toDirection, midNodesDirection, anglesCount);
        return newPath.cost + distance + distance * 0.00000001 - sub + anglesCount * settings.optimizationCornerCost;
    };
    return Finder;
});

