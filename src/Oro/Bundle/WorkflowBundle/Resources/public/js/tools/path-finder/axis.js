define(['./extends', './interval2d', './directions', './point2d'
], function(__extends, Interval2d, directions, Point2d) {
    'use strict';
    __extends(Axis, Interval2d);

    var TOP_TO_BOTTOM_ID = directions.TOP_TO_BOTTOM.id;
    var BOTTOM_TO_TOP_ID = directions.BOTTOM_TO_TOP.id;
    var LEFT_TO_RIGHT_ID = directions.LEFT_TO_RIGHT.id;
    var RIGHT_TO_LEFT_ID = directions.RIGHT_TO_LEFT.id;
    /**
     * Creates axis
     *
     * @param {NodePoint} a
     * @param {NodePoint} b
     * @param {Graph} graph
     * @param {number} costMultiplier
     * @constructor
     */
    function Axis(a, b, graph, costMultiplier) {
        if (costMultiplier === void 0) {
            costMultiplier = 1;
        }
        Interval2d.call(this, a, b);
        this.clonesAtLeft = [];
        this.clonesAtRight = [];
        this.used = false;
        this.nodes = [];
        this.isVertical = this.a.x === this.b.x;
        this.uid = Axis.uidCounter++;
        this.costMultiplier = costMultiplier;
        this.graph = graph;
    }

    /**
     * @type {number}
     */
    Axis.uidCounter = 0;

    /**
     * Returns all connection on axis
     *
     * @type {Array.<Connection>}
     */
    Object.defineProperty(Axis.prototype, 'connections', {
        get: function() {
            var result = [];
            var vectorId = this.b.sub(this.a).unitVector.abs().rot180().id;
            for (var i = this.nodes.length - 1; i >= 1; i--) {
                var node = this.nodes[i];
                result.push(node.connections[vectorId]);
            }
            return result;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * @type {Axis}
     */
    Object.defineProperty(Axis.prototype, 'closestLeftClone', {
        get: function() {
            var closestLeftClone = null;
            var leftClone = this.clonesAtLeft[0];
            while (leftClone) {
                closestLeftClone = leftClone;
                leftClone = leftClone.clonesAtRight[leftClone.clonesAtRight.length - 1];
            }
            return closestLeftClone;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * @type {Axis}
     */
    Object.defineProperty(Axis.prototype, 'closestRightClone', {
        get: function() {
            var closestRightClone = null;
            var rightClone = this.clonesAtRight[0];
            while (rightClone) {
                closestRightClone = rightClone;
                rightClone = rightClone.clonesAtLeft[rightClone.clonesAtLeft.length - 1];
            }
            return closestRightClone;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * @type {Array.<Axis>}
     */
    Object.defineProperty(Axis.prototype, 'allClones', {
        get: function() {
            var clones = [];
            var i;
            var clone;
            for (i = 0; i < this.clonesAtLeft.length; i++) {
                clone = this.clonesAtLeft[i];
                clones.push.apply(clones, clone.allClones);
            }
            clones.push(this);
            for (i = 0; i < this.clonesAtRight.length; i++) {
                clone = this.clonesAtRight[i];
                clones.push.apply(clones, clone.allClones);
            }
            return clones;
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Creates Axis from given interval
     *
     * @param {Interval2d} interval
     * @param {Graph} graph
     * @returns {Axis}
     */
    Axis.createFromInterval = function(interval, graph) {
        var costMultiplier = interval.costMultiplier;
        var isVertical = interval.isVertical;
        var axis = new Axis(interval.a, interval.b, graph, costMultiplier);
        // this is fix for zero length axises
        if (isVertical !== undefined) {
            axis.isVertical = isVertical;
        }
        return axis;
    };

    /**
     * Vector to the next node (node that matches following condition nextNode.x >= node.x && nextNode.y >= node.y)
     * Used for navigation between nodes
     *
     * @type {Point2d}
     */
    Object.defineProperty(Axis.prototype, 'nextNodeConnVector', {
        get: function() {
            if (this.isVertical) {
                return directions.TOP_TO_BOTTOM;
            } else {
                return directions.LEFT_TO_RIGHT;
            }
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Vector to the previous node (node that matches following condition nextNode.x <= node.x && nextNode.y <= node.y)
     * Used for navigation between nodes
     *
     * @type {Point2d}
     */
    Object.defineProperty(Axis.prototype, 'prevNodeConnVector', {
        get: function() {
            if (this.isVertical) {
                return directions.BOTTOM_TO_TOP;
            } else {
                return directions.RIGHT_TO_LEFT;
            }
        },
        enumerable: true,
        configurable: true
    });

    /**
     * Adds node to axis.
     * @param {NodePoint} node
     */
    Axis.prototype.addNode = function(node) {
        if (this.isVertical) {
            node.vAxis = this;
        } else {
            node.hAxis = this;
        }
        if (this.nodes.indexOf(node) !== -1) {
            return;
        }
        this.nodes.push(node);
    };

    /**
     * Adds node to axis. That function keeps all connections up to date, while regular addNode() require
     * axis.finalize() call after all nodes addition. Used to update axis on fly
     *
     * @param {NodePoint} node
     */
    Axis.prototype.addFinalNode = function(node) {
        var nextNodeConnVector = this.nextNodeConnVector;
        var nextNodeConn;
        var prevNodeConn;
        if ((nextNodeConn = node.connections[nextNodeConnVector.id])) {
            var nextNode = nextNodeConn.second(node);
            var nextIndex = this.nodes.indexOf(nextNode);
            if (nextIndex === -1) {
                throw new Error('Invalid add node call');
            }
            this.nodes.splice(nextIndex, 0, node);
        } else if ((prevNodeConn = node.connections[this.prevNodeConnVector.id])) {
            var prevNode = prevNodeConn.second(node);
            var prevIndex = this.nodes.indexOf(prevNode);
            if (prevIndex === -1) {
                throw new Error('Invalid add node call');
            }
            this.nodes.splice(prevIndex + 1, 0, node);
        } else {
            throw new Error('Node should be connected before addition');
        }
    };

    /**
     * Used in pair with addNode(). This function establishes connection between axis nodes.
     */
    Axis.prototype.finalize = function() {
        // this.nodes.forEach((node)=>node.draw('red'));
        var firstNode = this.nodes[0];
        var lastNode = this.nodes[this.nodes.length - 1];
        var node;
        var i;
        if (this.isVertical) {
            if (firstNode.connections[TOP_TO_BOTTOM_ID]) {
                firstNode.connections[TOP_TO_BOTTOM_ID].remove();
            }
            if (lastNode.connections[BOTTOM_TO_TOP_ID]) {
                lastNode.connections[BOTTOM_TO_TOP_ID].remove();
            }
            for (i = this.nodes.length - 1; i >= 0; i--) {
                node = this.nodes[i];
                node.vAxis = this;
                node.connect(directions.BOTTOM_TO_TOP, this.nodes[i - 1]);
            }
        } else {
            if (firstNode.connections[LEFT_TO_RIGHT_ID]) {
                firstNode.connections[LEFT_TO_RIGHT_ID].remove();
            }
            if (lastNode.connections[RIGHT_TO_LEFT_ID]) {
                lastNode.connections[RIGHT_TO_LEFT_ID].remove();
            }

            for (i = this.nodes.length - 1; i >= 0; i--) {
                node = this.nodes[i];
                node.hAxis = this;
                node.connect(directions.RIGHT_TO_LEFT, this.nodes[i - 1]);
            }
        }
    };

    /**
     * Sort axis nodes.
     */
    Axis.prototype.sortNodes = function() {
        // sort nodes and connect them
        this.nodes.sort(function(a, b) {
            return a.x - b.x + a.y - b.y;
        });
    };

    /**
     * Updates axis endpoints, so axis becomes include both itself and axis passed as an argument
     *
     * @param {Axis} axis
     */
    Axis.prototype.merge = function(axis) {
        var points = [this.a, this.b, axis.a, axis.b];
        points.sort(function(a, b) {
            return a.x - b.x + a.y - b.y;
        });
        this.a = points[0];
        this.b = points[points.length - 1];
    };

    /**
     * Allows to create clone of axis at left/right side.
     *
     * @param {Point2d} direction
     */
    Axis.prototype.cloneAtDirection = function(direction) {
        var axis = Axis.createFromInterval(this, this.graph);
        for (var i = 0; i < this.nodes.length; i++) {
            var node = this.nodes[i];
            var clonedNode = node.clone();
            var secondNode = node.nextNode(direction);
            if (node.used && secondNode.used) {
                clonedNode.used = true;
            }
            node.connect(direction, clonedNode);
            if (secondNode) {
                clonedNode.connect(direction, secondNode);
            }
            if (this.isVertical) {
                node.hAxis.addFinalNode(clonedNode);
            } else {
                node.vAxis.addFinalNode(clonedNode);
            }
            axis.addNode(clonedNode);
        }
        axis.finalize();
        return axis;
    };

    /**
     * Function checks if axis has an siblings(clones) at left and right side which are not used
     * It will creates them if they are not found.
     */
    Axis.prototype.ensureTraversableSiblings = function() {
        var clone;
        clone = this.closestLeftClone;
        if (!clone || clone.isUsed) {
            this.clonesAtLeft.unshift(this.cloneAtDirection(this.nextNodeConnVector.rot90().abs().rot180()));
        }
        clone = this.closestRightClone;
        if (!clone || clone.isUsed) {
            this.clonesAtRight.unshift(this.cloneAtDirection(this.nextNodeConnVector.rot90().abs()));
        }
    };

    /**
     * Draws axis
     *
     * @param {string} color
     */
    Axis.prototype.draw = function(color) {
        if (color === void 0) {
            color = 'green';
        }
        if (this.nodes.length) {
            (new Interval2d(
                new Point2d(this.nodes[0].recommendedX, this.nodes[0].recommendedY),
                new Point2d(
                    this.nodes[this.nodes.length - 1].recommendedX,
                    this.nodes[this.nodes.length - 1].recommendedY)
            )).draw(color);
        } else {
            Interval2d.prototype.draw.call(this, color);
        }
    };

    return Axis;
});
