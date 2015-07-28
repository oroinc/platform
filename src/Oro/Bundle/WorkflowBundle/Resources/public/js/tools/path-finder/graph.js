define(['./settings', './directions', './Vector2d', './constraint/simple/empty-constraint',
    './constraint/simple/left-simple-constraint', './constraint/simple/right-simple-constraint',
    './location-directive/stick-left-location-directive', './location-directive/stick-right-location-directive',
    './location-directive/center-location-directive', './rectangle', './base-axis', './point2d', './path',
    './interval2d', './line2d', './node-point'],
    function(settings, directions, Vector2d, EmptyConstraint,
              LeftSimpleConstraint, RightSimpleConstraint,
              StickLeftLocationDirective, StickRightLocationDirective,
              CenterLocationDirective, Rectangle, BaseAxis, Point2d, Path,
              Interval2d, Line2d, NodePoint) {
        'use strict';
        function Graph() {
            this.rectangles = [];
            this.baseAxises = [];
            this.horizontalAxises = [];
            this.verticalAxises = [];
            this.nodes = {};
            this.mergeAxisesQueue = [];
            this.axisesConnectedAtLeft = [];
            this.axisesConnectedAtRight = [];
            this.centerLineMinimalRequiredWidth = 32;
        }
        Graph.prototype.build = function() {
            this.outerRect = this.rectangles.reduce(function(prev, current) {
                return current.union(prev);
            }, new Rectangle(this.rectangles[0].top, this.rectangles[0].left, 0, 0));
            this.baseAxises.push(
                BaseAxis.createFromInterval(
                    this.outerRect.topSide,
                    this,
                    new EmptyConstraint(),
                    new RightSimpleConstraint(this.outerRect.top),
                    new StickRightLocationDirective()),
                BaseAxis.createFromInterval(
                    this.outerRect.bottomSide,
                    this,
                    new LeftSimpleConstraint(this.outerRect.bottom),
                    new EmptyConstraint(),
                    new StickLeftLocationDirective()),
                BaseAxis.createFromInterval(
                    this.outerRect.leftSide,
                    this,
                    new EmptyConstraint(),
                    new RightSimpleConstraint(this.outerRect.left),
                    new StickRightLocationDirective()),
                BaseAxis.createFromInterval(
                    this.outerRect.rightSide,
                    this,
                    new LeftSimpleConstraint(this.outerRect.right),
                    new EmptyConstraint(),
                    new StickLeftLocationDirective())
            );
            this.buildCornerAxises();
            this.buildCenterAxises();
            this.buildCenterLinesBetweenNodes();
            this.createAxises();
            this.buildMergeRequests();
            this.mergeAxises();
            this.buildNodes();
            this.finalizeAxises();
        };
        Graph.prototype.getPathFromCid = function(cid, direction) {
            return this.getPathFrom(this.getRectByCid(cid), direction);
        };
        Graph.prototype.getPathFrom = function(rect, direction) {
            var center = rect.center;
            var node;
            switch (direction.id) {
                case directions.BOTTOM_TO_TOP.id:
                    node = this.getNodeAt(new Point2d(center.x, center.y - 1));
                    break;
                case directions.TOP_TO_BOTTOM.id:
                    node = this.getNodeAt(new Point2d(center.x, center.y + 1));
                    break;
                case directions.LEFT_TO_RIGHT.id:
                    node = this.getNodeAt(new Point2d(center.x - 1, center.y));
                    break;
                case directions.RIGHT_TO_LEFT.id:
                    node = this.getNodeAt(new Point2d(center.x + 1, center.y));
                    break;
                default:
                    throw new Error('Not supported direction');
            }
            return new Path(node.connections[direction.id], node, null);
        };
        Graph.prototype.getRectByCid = function(cid) {
            for (var i = 0; i < this.rectangles.length; i++) {
                var rect = this.rectangles[i];
                if (rect.cid === cid) {
                    return rect;
                }
            }
            return null;
        };
        Graph.prototype.draw = function() {
            var i;
            this.outerRect.draw('red');
            for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
                this.horizontalAxises[i].allClones.forEach(function(clone) {
                    return clone.draw('cyan');
                });
            }
            for (i = this.verticalAxises.length - 1; i >= 0; i--) {
                this.verticalAxises[i].allClones.forEach(function(clone) {
                    return clone.draw('cyan');
                });
            }
            for (i = this.rectangles.length - 1; i >= 0; i--) {
                this.rectangles[i].draw('black');
            }
            for (var key in this.nodes) {
                if (this.nodes.hasOwnProperty(key)) {
                    this.nodes[key].draw('black');
                }
            }
        };
        Graph.prototype.createAxises = function() {
            var newAxis;
            for (var i = 0; i < this.baseAxises.length; i++) {
                var axis = this.baseAxises[i];
                if (axis.isVertical) {
                    newAxis = new BaseAxis(axis.a, axis.b, this, axis.costMultiplier, axis.leftConstraint,
                        axis.rightConstraint, axis.locationDirective);
                    newAxis.isVertical = axis.isVertical;
                    this.verticalAxises.push(newAxis);
                } else if (axis.a.y === axis.b.y) {
                    newAxis = new BaseAxis(axis.a, axis.b, this, axis.costMultiplier, axis.leftConstraint,
                        axis.rightConstraint, axis.locationDirective);
                    newAxis.isVertical = axis.isVertical;
                    this.horizontalAxises.push(newAxis);
                } else {
                    throw new Error('Not supported');
                }
            }
        };
        Graph.prototype.removeAxis = function(axis) {
            var index;
            if ((index = this.horizontalAxises.indexOf(axis)) !== -1) {
                this.horizontalAxises.splice(index, 1);
                return;
            }
            if ((index = this.verticalAxises.indexOf(axis)) !== -1) {
                this.verticalAxises.splice(index, 1);
            }
        };
        Graph.prototype.mergeAxises = function() {
            var i;
            var j;
            for (i = 0; i < this.mergeAxisesQueue.length; i++) {
                var queue = this.mergeAxisesQueue[i];
                for (j = queue.length - 1; j >= 1; j--) {
                    queue[j - 1].merge(queue[j]);
                    this.removeAxis(queue[j]);
                }
            }
        };
        Graph.prototype.finalizeAxises = function() {
            var i;
            var j;
            var node;
            var connectionToLeft;
            var nodeAtLeft;
            var connectionToRight;
            var nodeAtRight;
            var axis;

            for (i = this.verticalAxises.length - 1; i >= 0; i--) {
                this.verticalAxises[i].sortNodes();
                this.verticalAxises[i].finalize();
            }
            for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
                this.horizontalAxises[i].sortNodes();
                this.horizontalAxises[i].finalize();
            }
            this.verticalAxises.sort(function(a, b) {
                return a.a.x - b.a.x;
            });
            this.horizontalAxises.sort(function(a, b) {
                return a.a.y - b.a.y;
            });
            // find connections between axises
            for (i = 0; i < this.horizontalAxises.length; i++) {
                axis = this.horizontalAxises[i];
                for (j = axis.nodes.length - 1; j >= 0; j--) {
                    node = axis.nodes[j];
                    connectionToLeft = node.connections[directions.TOP_TO_BOTTOM.id];
                    nodeAtLeft = connectionToLeft ? connectionToLeft.second(node) : null;
                    connectionToRight = node.connections[directions.BOTTOM_TO_TOP.id];
                    nodeAtRight = connectionToRight ? connectionToRight.second(node) : null;
                    if (nodeAtLeft) {
                        this.addAxisConnectionInfo(this.axisesConnectedAtLeft, axis, nodeAtLeft.hAxis);
                    }
                    if (nodeAtRight) {
                        this.addAxisConnectionInfo(this.axisesConnectedAtRight, axis, nodeAtRight.hAxis);
                    }
                }
            }
            for (i = 0; i < this.verticalAxises.length; i++) {
                axis = this.verticalAxises[i];
                for (j = axis.nodes.length - 1; j >= 0; j--) {
                    node = axis.nodes[j];
                    connectionToLeft = node.connections[directions.RIGHT_TO_LEFT.id];
                    nodeAtLeft = connectionToLeft ? connectionToLeft.second(node) : null;
                    connectionToRight = node.connections[directions.LEFT_TO_RIGHT.id];
                    nodeAtRight = connectionToRight ? connectionToRight.second(node) : null;
                    if (nodeAtLeft) {
                        this.addAxisConnectionInfo(this.axisesConnectedAtLeft, axis, nodeAtLeft.vAxis);
                    }
                    if (nodeAtRight) {
                        this.addAxisConnectionInfo(this.axisesConnectedAtRight, axis, nodeAtRight.vAxis);
                    }
                }
            }
        };
        Graph.prototype.addAxisConnectionInfo = function(keeper, main, secondary) {
            if (!keeper[main.uid]) {
                keeper[main.uid] = [];
            }
            if (keeper[main.uid].indexOf(secondary) === -1) {
                keeper[main.uid].push(secondary);
            }
        };
        Graph.prototype.hasAxis = function(axis) {
            return this.horizontalAxises.indexOf(axis) !== -1 || this.verticalAxises.indexOf(axis) !== -1;
        };
        Graph.prototype.buildCornerAxises = function() {
            for (var i = this.rectangles.length - 1; i >= 0; i--) {
                var rect = this.rectangles[i];
                var defs = [
                    {
                        vectorA: new Vector2d(rect.left, rect.top, directions.TOP_TO_BOTTOM),
                        vectorB: new Vector2d(rect.left, rect.bottom, directions.BOTTOM_TO_TOP),
                        leftConstraint: new EmptyConstraint(),
                        rightConstraint: new RightSimpleConstraint(rect.left),
                        locationDirective: new StickRightLocationDirective()
                    },
                    {
                        vectorA: new Vector2d(rect.right, rect.top, directions.TOP_TO_BOTTOM),
                        vectorB: new Vector2d(rect.right, rect.bottom, directions.BOTTOM_TO_TOP),
                        leftConstraint: new LeftSimpleConstraint(rect.right),
                        rightConstraint: new EmptyConstraint(),
                        locationDirective: new StickLeftLocationDirective()
                    },
                    {
                        vectorA: new Vector2d(rect.left, rect.top, directions.RIGHT_TO_LEFT),
                        vectorB: new Vector2d(rect.right, rect.top, directions.LEFT_TO_RIGHT),
                        leftConstraint: new EmptyConstraint(),
                        rightConstraint: new RightSimpleConstraint(rect.top),
                        locationDirective: new StickRightLocationDirective()
                    },
                    {
                        vectorA: new Vector2d(rect.left, rect.bottom, directions.RIGHT_TO_LEFT),
                        vectorB: new Vector2d(rect.right, rect.bottom, directions.LEFT_TO_RIGHT),
                        leftConstraint: new LeftSimpleConstraint(rect.bottom),
                        rightConstraint: new EmptyConstraint(),
                        locationDirective: new StickLeftLocationDirective()
                    }
                ];
                for (var j = defs.length - 1; j >= 0; j--) {
                    var def = defs[j];
                    var closestRectCrossPoint1 = this.findClosestRectCross(def.vectorA, rect);
                    var closestRectCrossPoint2 = this.findClosestRectCross(def.vectorB, rect);
                    this.baseAxises.push(
                        BaseAxis.createFromInterval(
                            new Interval2d(closestRectCrossPoint1, closestRectCrossPoint2),
                            this,
                            def.leftConstraint,
                            def.rightConstraint,
                            def.locationDirective)
                    );
                }
            }
        };
        Graph.prototype.buildCenterAxises = function() {
            for (var i = this.rectangles.length - 1; i >= 0; i--) {
                var rect = this.rectangles[i];
                var center = rect.center;
                var defs = [
                    {
                        vector: new Vector2d(center.x, center.y + 1, directions.TOP_TO_BOTTOM),
                        leftConstraint: new LeftSimpleConstraint(rect.left),
                        rightConstraint: new RightSimpleConstraint(rect.right),
                        locationDirective: new CenterLocationDirective()
                    },
                    {
                        vector: new Vector2d(center.x, center.y - 1, directions.BOTTOM_TO_TOP),
                        leftConstraint: new LeftSimpleConstraint(rect.left),
                        rightConstraint: new RightSimpleConstraint(rect.right),
                        locationDirective: new CenterLocationDirective()
                    },
                    {
                        vector: new Vector2d(center.x + 1, center.y, directions.RIGHT_TO_LEFT),
                        leftConstraint: new LeftSimpleConstraint(rect.top),
                        rightConstraint: new RightSimpleConstraint(rect.bottom),
                        locationDirective: new CenterLocationDirective()
                    },
                    {
                        vector: new Vector2d(center.x - 1, center.y, directions.LEFT_TO_RIGHT),
                        leftConstraint: new LeftSimpleConstraint(rect.top),
                        rightConstraint: new RightSimpleConstraint(rect.bottom),
                        locationDirective: new CenterLocationDirective()
                    }
                ];
                for (var j = defs.length - 1; j >= 0; j--) {
                    var def = defs[j];
                    var closestRectCrossPoint = this.findClosestRectCross(def.vector, rect);
                    var axis = new BaseAxis(def.vector.start, closestRectCrossPoint, this, 1, def.leftConstraint,
                        def.rightConstraint, def.locationDirective);
                    var secondaryAxis = new BaseAxis(def.vector.start, def.vector.start, this, 1, new EmptyConstraint(),
                        new EmptyConstraint(), new CenterLocationDirective());
                    secondaryAxis.isVertical = !axis.isVertical;
                    this.baseAxises.push(axis, secondaryAxis);
                }
            }
        };
        Graph.prototype.eachRectanglePair = function(fn) {
            for (var i = this.rectangles.length - 1; i >= 0; i--) {
                var rect1 = this.rectangles[i];
                for (var j = i - 1; j >= 0; j--) {
                    fn(rect1, this.rectangles[j]);
                }
            }
        };
        Graph.prototype.buildCenterLinesBetweenNodes = function() {
            var _this = this;
            this.eachRectanglePair(function(a, b) {
                if (a.top > b.bottom && a.top - b.bottom > _this.centerLineMinimalRequiredWidth) {
                    _this.buildSingleCenterLine(a, b, (a.top + b.bottom) / 2, a.topSide, b.bottomSide, a.top, b.bottom);
                }
                if (b.top > a.bottom && b.top - a.bottom > _this.centerLineMinimalRequiredWidth) {
                    _this.buildSingleCenterLine(a, b, (b.top + a.bottom) / 2, b.topSide, a.bottomSide, b.top, a.bottom);
                }
                if (a.left > b.right && a.left - b.right > _this.centerLineMinimalRequiredWidth) {
                    _this.buildSingleCenterLine(a, b, (a.left + b.right) / 2, a.leftSide, b.rightSide, a.left, b.right);
                }
                if (b.left > a.right && b.left - a.right > _this.centerLineMinimalRequiredWidth) {
                    _this.buildSingleCenterLine(a, b, (b.left + a.right) / 2, b.leftSide, a.rightSide, b.left, a.right);
                }
            });
        };
        Graph.prototype.buildSingleCenterLine = function(aRect, bRect, coordinate, a, b, min, max) {
            var aVector = new Vector2d(a.center.x, a.center.y, a.a.sub(a.b).rot270().unitVector);
            var bVector = new Vector2d(b.center.x, b.center.y, b.a.sub(b.b).rot90().unitVector);
            var crossRect = new Rectangle(Math.min(a.center.x, b.center.x), Math.min(a.center.y, b.center.y), 1, 1);
            var crossLine;
            crossRect.right = Math.max(a.center.x, b.center.x);
            crossRect.bottom = Math.max(a.center.y, b.center.y);
            if (this.rectangleIntersectsAnyRectangle(crossRect)) {
                return;
            }
            if (aVector.direction.x === 0) {
                crossLine = new Line2d(0, coordinate);
            } else {
                crossLine = new Line2d(Infinity, coordinate);
            }
            var intersectionA = crossLine.intersection(aVector.line);
            var intersectionB = crossLine.intersection(bVector.line);
            var vector1 = new Vector2d(intersectionA.x, intersectionA.y, aVector.direction.rot90());
            var vector2 = new Vector2d(intersectionB.x, intersectionB.y, bVector.direction.rot90());
            var closestRectCrossPoint1 = this.findClosestRectCross(vector1, null);
            var closestRectCrossPoint2 = this.findClosestRectCross(vector2, null);
            this.baseAxises.push(new BaseAxis(closestRectCrossPoint1, closestRectCrossPoint2, this,
                settings.centerAxisCostMultiplier, new LeftSimpleConstraint(min),
                new RightSimpleConstraint(max), new CenterLocationDirective()));
        };
        Graph.prototype.buildNodes = function() {
            /*
             * add all nodes at axises cross points
             */
            var node;
            var newAxis;
            var i;
            var j;
            var hAxis;
            var vAxis;
            var crossPoint;
            for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
                hAxis = this.horizontalAxises[i];
                for (j = this.verticalAxises.length - 1; j >= 0; j--) {
                    vAxis = this.verticalAxises[j];
                    crossPoint = hAxis.getCrossPoint(vAxis);
                    if (crossPoint) {
                        node = this.getNodeAt(crossPoint);
                        hAxis.addNode(node);
                        vAxis.addNode(node);
                        node.hAxis = hAxis;
                        node.vAxis = vAxis;
                        node.stale = true;
                    }
                }
            }
            /*
             * add all nodes at axises end points
             */
            var newVerticalAxises = [];
            var newHorizontalAxises = [];
            for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
                hAxis = this.horizontalAxises[i];
                node = this.getNodeAt(hAxis.a);
                if (!node.stale) {
                    newAxis = new BaseAxis(hAxis.a, hAxis.a, this, 0, new EmptyConstraint(), new EmptyConstraint(),
                        new CenterLocationDirective());
                    newAxis.isVertical = true;
                    newVerticalAxises.push(newAxis);
                    hAxis.addNode(node);
                    newAxis.addNode(node);
                    node.hAxis = hAxis;
                    node.vAxis = newAxis;
                }
                node = this.getNodeAt(hAxis.b);
                if (!node.stale) {
                    newAxis = new BaseAxis(hAxis.b, hAxis.b, this, 0, new EmptyConstraint(), new EmptyConstraint(),
                        new CenterLocationDirective());
                    newAxis.isVertical = true;
                    this.verticalAxises.push(newAxis);
                    hAxis.addNode(node);
                    newAxis.addNode(node);
                    node.hAxis = hAxis;
                    node.vAxis = newAxis;
                }
            }
            for (j = this.verticalAxises.length - 1; j >= 0; j--) {
                vAxis = this.verticalAxises[j];
                node = this.getNodeAt(vAxis.a);
                if (!node.stale) {
                    newAxis = new BaseAxis(vAxis.a, vAxis.a, this, 0, new EmptyConstraint(), new EmptyConstraint(),
                        new CenterLocationDirective());
                    newAxis.isVertical = false;
                    newHorizontalAxises.push(newAxis);
                    vAxis.addNode(node);
                    newAxis.addNode(node);
                    node.hAxis = newAxis;
                    node.vAxis = vAxis;
                }
                node = this.getNodeAt(vAxis.b);
                if (!node.stale) {
                    newAxis = new BaseAxis(vAxis.b, vAxis.b, this, 0, new EmptyConstraint(), new EmptyConstraint(),
                        new CenterLocationDirective());
                    newAxis.isVertical = false;
                    this.horizontalAxises.push(newAxis);
                    vAxis.addNode(node);
                    newAxis.addNode(node);
                    node.hAxis = newAxis;
                    node.vAxis = vAxis;
                }
            }
            this.verticalAxises.push.apply(this.verticalAxises, newVerticalAxises);
            this.horizontalAxises.push.apply(this.horizontalAxises, newHorizontalAxises);
        };
        Graph.prototype.buildMergeRequests = function() {
            for (var i = this.horizontalAxises.length - 1; i >= 0; i--) {
                var hAxis = this.horizontalAxises[i];
                for (var j = this.verticalAxises.length - 1; j >= 0; j--) {
                    var vAxis = this.verticalAxises[j];
                    var crossPoint = hAxis.getCrossPoint(vAxis);
                    if (crossPoint) {
                        var node = this.getNodeAt(crossPoint);
                        if (node.stale) {
                            if (node.hAxis !== hAxis) {
                                this.addMergeRequest(node.hAxis, hAxis);
                            }
                            if (node.vAxis !== vAxis) {
                                this.addMergeRequest(node.vAxis, vAxis);
                            }
                        }
                        node.hAxis = hAxis;
                        node.vAxis = vAxis;
                        node.stale = true;
                    }
                }
            }
        };
        Graph.prototype.addMergeRequest = function(a, b) {
            var foundAQueue;
            var foundBQueue;
            var i;
            var queue;
            for (i = this.mergeAxisesQueue.length - 1; i >= 0; i--) {
                queue = this.mergeAxisesQueue[i];
                if (queue.indexOf(a) !== -1) {
                    foundAQueue = queue;
                    break;
                }
            }
            for (i = this.mergeAxisesQueue.length - 1; i >= 0; i--) {
                queue = this.mergeAxisesQueue[i];
                if (queue.indexOf(b) !== -1) {
                    foundBQueue = queue;
                    break;
                }
            }
            if (foundAQueue !== undefined && foundAQueue === foundBQueue) {
                return;
            }
            if (!foundAQueue) {
                if (foundBQueue) {
                    foundBQueue.push(a);
                } else {
                    this.mergeAxisesQueue.push([a, b]);
                }
            } else {
                if (foundBQueue) {
                    // must merge
                    foundAQueue.push.apply(foundAQueue, foundBQueue);
                    this.mergeAxisesQueue.splice(this.mergeAxisesQueue.indexOf(foundBQueue), 1);
                } else {
                    foundAQueue.push(b);
                }
            }
        };
        Graph.prototype.getNodeAt = function(point) {
            var node = this.nodes[point.id];
            if (!node) {
                node = new NodePoint(point.x, point.y);
                this.nodes[point.id] = node;
            }
            return node;
        };
        Graph.prototype.findClosestRectCross = function(vector, ignoreRect) {
            var closestDistance = Infinity;
            var closestPoint = null;
            for (var i = this.rectangles.length - 1; i >= 0; i--) {
                var rect = this.rectangles[i];
                if (rect === ignoreRect) {
                    continue;
                }
                var crossPoint = vector.getCrossPointWithRect(rect);
                if (crossPoint && closestDistance > crossPoint.distanceTo(vector.start)) {
                    closestPoint = crossPoint;
                    closestDistance = crossPoint.distanceTo(vector.start);
                }
            }
            if (closestDistance === Infinity) {
                this.outerRect.eachSide(function(side) {
                    var crossPoint;
                    if ((crossPoint = vector.getCrossPointWithInterval(side))) {
                        if (vector.start.distanceTo(crossPoint) < closestDistance) {
                            closestPoint = crossPoint;
                            closestDistance = vector.start.distanceTo(crossPoint);
                        }
                    }
                });
            }
            if (closestDistance === Infinity) {
                return vector.start;
            }
            return closestPoint;
        };
        Graph.prototype.rectangleIntersectsAnyRectangle = function(rectangle, ignoreRect) {
            for (var i = this.rectangles.length - 1; i >= 0; i--) {
                if (this.rectangles[i] === ignoreRect) {
                    continue;
                }
                if (rectangle.intersection(this.rectangles[i]).isValid) {
                    return true;
                }
            }
            return false;
        };
        Graph.prototype.intervalIntersectsAnyRectangle = function(interval, ignoreRect) {
            for (var i = this.rectangles.length - 1; i >= 0; i--) {
                if (this.rectangles[i] === ignoreRect) {
                    continue;
                }
                if (interval.crossesRect(this.rectangles[i])) {
                    return true;
                }
            }
            return false;
        };
        Graph.prototype.updateWithPath = function(path) {
            var connections = path.allConnections;
            var axises = [];
            var i;
            for (i = 0; i < connections.length; i++) {
                var conn = connections[i];
                if (axises.indexOf(conn.axis) === -1) {
                    axises.push(conn.axis);
                    conn.axis.ensureTraversableSiblings();
                    conn.axis.used = true;
                    conn.axis.costMultiplier *= settings.usedAxisCostMultiplier;
                }
            }
            var nextNode;
            var current;
            var midNode;
            var next;
            var startNode;
            var markedNodes = [];
            for (i = 0; i < connections.length - 2; i++) {
                current = connections[i];
                next = connections[i + 1];
                startNode = current.a === next.a || current.a === next.b ? current.b : current.a;
                midNode = current.a === next.a || current.a === next.b ? current.a : current.b;
                midNode.used = true;
                startNode.used = true;
                nextNode = startNode;
                // connection can be divided before, traverse all nodes
                do {
                    nextNode = nextNode.nextNode(current.directionFrom(startNode));
                    nextNode.used = true;
                } while (nextNode !== midNode);
                // console.log(midNode.uid);
                if (current.vector.id !== next.vector.id) {
                    // corner
                    // all connections are used on corner
                    // this will avoid double corner use
                    midNode.eachConnection(function(conn) {
                        conn.traversable = false;
                    });
                }
            }
            nextNode = startNode = midNode;
            current = next;
            midNode = current.a === nextNode ? current.b : current.a;
            do {
                nextNode = nextNode.nextNode(current.directionFrom(startNode));
                nextNode.used = true;
            } while (nextNode !== midNode);
            path.toNode.used = true;
            markedNodes.push(path.toNode);
            this.relocateAxises();
        };
        Graph.prototype.selfCheck = function() {
            var i;
            for (i = this.verticalAxises.length - 1; i >= 0; i--) {
                this.verticalAxises[i].allClones.forEach(function(axis) {
                    return axis.selfCheck();
                });
            }
            for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
                this.horizontalAxises[i].allClones.forEach(function(axis) {
                    return axis.selfCheck();
                });
            }
        };
        Graph.prototype.relocateAxises = function() {
            var i;
            var j;
            var axis;
            var clones;
            var usage;
            var current;
            var clone;
            for (i = this.verticalAxises.length - 1; i >= 0; i--) {
                axis = this.verticalAxises[i];
                clones = axis.allClones;
                usage = clones.map(function(axis) {
                    return axis.used;
                });
                axis.linesIncluded = Math.floor(clones.length / 2);
                if (axis.linesIncluded > 0) {
                    current = 0;
                    for (j = 0; j < clones.length; j++) {
                        clone = clones[j];
                        clone.recommendedPosition = axis.locationDirective.getRecommendedPosition(current);
                        current += 0.5;
                    }
                }
            }
            for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
                axis = this.horizontalAxises[i];
                clones = axis.allClones;
                usage = clones.map(function(axis) {
                    return axis.used;
                });
                axis.linesIncluded = Math.floor(clones.length / 2);
                if (axis.linesIncluded > 0) {
                    current = 0;
                    for (j = 0; j < clones.length; j++) {
                        clone = clones[j];
                        clone.recommendedPosition = axis.locationDirective.getRecommendedPosition(current);
                        current += 0.5;
                    }
                }
            }
            /*
             var leftConstraints: number[] = [];
             var rightConstraints: number[] = [];

             // sum left constraints
             for (i = 0; i < this.verticalAxises.length; i++) {
             var axis = this.verticalAxises[i];
             var atLeft = this.axisesConnectedAtLeft[axis.uid];
             var minConstraint = axis.leftConstraint.recommendedStart;
             if (atLeft) {
             for (j = 0; j < atLeft.length; j++) {
             var leftAxis = atLeft[j];
             if (!minConstraint || leftAxis.leftConstraint.recommendedEnd > minConstraint) {
             minConstraint = leftAxis.leftConstraint.recommendedEnd;
             }
             }
             }
             leftConstraints[axis.uid] = minConstraint;
             }
             console.log(leftConstraints);
             // sum right constraints
             for (i = this.verticalAxises.length - 1; i >= 0; i--) {
             var axis = this.verticalAxises[i];
             var atRight = this.axisesConnectedAtLeft[axis.uid];
             var minConstraint = axis.rightConstraint.recommendedStart;
             if (atRight) {
             for (j = 0; j < atRight.length; j++) {
             var rightAxis = atRight[j];
             if (!minConstraint || rightAxis.rightConstraint.recommendedEnd > minConstraint) {
             minConstraint = rightAxis.rightConstraint.recommendedEnd;
             }
             }
             }
             rightConstraints[axis.uid] = minConstraint;
             }

             // find intersected constraints
             for (i = this.verticalAxises.length - 1; i >= 0; i--) {
             //if ()
             }
             */
        };
        Graph.prototype.isConnectionUnderRect = function(interval) {
            for (var i = this.rectangles.length - 1; i >= 0; i--) {
                var rect = this.rectangles[i];
                if (rect.containsPoint(interval.a) || rect.containsPoint(interval.b)) {
                    return true;
                }
            }
            return false;
        };
        return Graph;
    });
