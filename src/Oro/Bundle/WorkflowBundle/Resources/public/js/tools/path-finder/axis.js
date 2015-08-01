define(['./extends', './interval2d', './directions', './point2d'],
    function(__extends, Interval2d, directions, Point2d) {
    'use strict';
    __extends(Axis, Interval2d);
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
    Axis.createFromInterval = function(interval, graph) {
        var costMultiplier = interval.costMultiplier;
        var isVertical = interval.isVertical;
        var clone = new Axis(interval.a, interval.b, graph, costMultiplier);
        // this is fix for zero length axises
        if (isVertical !== undefined) {
            clone.isVertical = isVertical;
        }
        return clone;
    };
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
    Axis.prototype.finalize = function() {
        // this.nodes.forEach((node)=>node.draw('red'));
        var firstNode = this.nodes[0];
        var lastNode = this.nodes[this.nodes.length - 1];
        var node;
        var i;
        if (this.isVertical) {
            firstNode.removeConnection(firstNode.connections[directions.TOP_TO_BOTTOM.id]);
            lastNode.removeConnection(firstNode.connections[directions.BOTTOM_TO_TOP.id]);
            for (i = this.nodes.length - 1; i >= 0; i--) {
                node = this.nodes[i];
                node.vAxis = this;
                node.connect(directions.BOTTOM_TO_TOP, this.nodes[i - 1]);
            }
        } else {
            firstNode.removeConnection(firstNode.connections[directions.LEFT_TO_RIGHT.id]);
            lastNode.removeConnection(firstNode.connections[directions.RIGHT_TO_LEFT.id]);
            for (i = this.nodes.length - 1; i >= 0; i--) {
                node = this.nodes[i];
                node.hAxis = this;
                node.connect(directions.RIGHT_TO_LEFT, this.nodes[i - 1]);
            }
        }
    };
    Axis.prototype.sortNodes = function() {
        // sort nodes and connect them
        this.nodes.sort(function(a, b) {
            return a.x - b.x + a.y - b.y;
        });
    };
    Axis.prototype.merge = function(axis) {
        var points = [this.a, this.b, axis.a, axis.b];
        points.sort(function(a, b) {
            return a.x + a.y - b.x - b.y;
        });
        this.a = points[0];
        this.b = points[points.length - 1];
    };
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
    Axis.prototype.ensureTraversableSiblings = function() {
        var clone;
        clone = this.closestLeftClone;
        if (!clone || clone.isUsed) {
            // console.log(this.a.sub(this.b).unitVector.rot90().abs().rot180());
            this.clonesAtLeft.unshift(this.cloneAtDirection(this.nextNodeConnVector.rot90().abs().rot180()));
        }
        clone = this.closestRightClone;
        if (!clone || clone.isUsed) {
            // console.log(this.a.sub(this.b).unitVector.rot90().abs());
            this.clonesAtRight.unshift(this.cloneAtDirection(this.nextNodeConnVector.rot90().abs()));
        }
    };
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
    Axis.uidCounter = 0;
    return Axis;
});
