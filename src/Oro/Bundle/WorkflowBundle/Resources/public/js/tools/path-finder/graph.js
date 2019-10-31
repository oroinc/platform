define(['./settings', './directions', './vector2d', './constraint/simple/empty-constraint',
    './constraint/simple/left-simple-constraint', './constraint/simple/right-simple-constraint',
    './location-directive/stick-left-location-directive', './location-directive/stick-right-location-directive',
    './location-directive/center-location-directive', './rectangle', './base-axis', './point2d', './path',
    './interval2d', './line2d', './node-point'
], function(settings, directions, Vector2d, EmptyConstraint,
    LeftSimpleConstraint, RightSimpleConstraint,
    StickLeftLocationDirective, StickRightLocationDirective,
    CenterLocationDirective, Rectangle, BaseAxis, Point2d, Path,
    Interval2d, Line2d, NodePoint) {
    'use strict';

    /**
     * Graph embeds logic about it's build and update procedures
     *
     * @constructor
     */
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

    /**
     * Main build function
     */
    Graph.prototype.build = function() {
        this.outerRect = this.rectangles.slice(1).reduce(function(prev, current) {
            return current.union(prev);
        }, this.rectangles[0].clone());
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
        // turned off building corner axises to simplify graph
        // this.buildCornerAxises();
        this.buildCenterAxises();
        this.buildCenterLinesBetweenNodes();
        this.mergeExtraCenterAxises();
        this.createAxises();
        this.buildMergeRequests();
        this.mergeAxises();
        this.buildNodes();
        this.finalizeAxises();
    };

    /**
     * Returns outgoing path from initial rectangle found by cid
     *
     * @param {string} cid content id of initial rectangles passed into graph
     * @param {Point2d} direction of outgoing connection
     * @returns {Path}
     */
    Graph.prototype.getPathFromCid = function(cid, direction) {
        return this.getPathFrom(this.getRectByCid(cid), direction);
    };

    /**
     * Returns outgoing path from rectangle
     *
     * @param {Rectangle} rect
     * @param {Point2d} direction  direction of outgoing connection
     * @returns {Path}
     */
    Graph.prototype.getPathFrom = function(rect, direction) {
        const center = rect.center;
        let node;
        switch (direction.id) {
            case directions.BOTTOM_TO_TOP.id:
                node = this.getNodeAt(new Point2d(center.x, center.y - 1));
                break;
            case directions.TOP_TO_BOTTOM.id:
                node = this.getNodeAt(new Point2d(center.x, center.y + 1));
                break;
            case directions.LEFT_TO_RIGHT.id:
                node = this.getNodeAt(new Point2d(center.x + 1, center.y));
                break;
            case directions.RIGHT_TO_LEFT.id:
                node = this.getNodeAt(new Point2d(center.x - 1, center.y));
                break;
            default:
                throw new Error('Not supported direction');
        }
        if (node.connections[direction.id] === void 0 || node.connections[direction.id] === null) {
            throw new Error('Path not found. Node at (' + node.x + ', ' + node.y +
                    '), direction(' + direction.x + ', ' + direction.y + ')');
        }
        return new Path(node.connections[direction.id], node, null);
    };

    /**
     * Finds and recturns one of initial rectangle by cid
     *
     * @param {string} cid
     * @returns {Rectangle}
     */
    Graph.prototype.getRectByCid = function(cid) {
        for (let i = 0; i < this.rectangles.length; i++) {
            const rect = this.rectangles[i];
            if (rect.cid === cid) {
                return rect;
            }
        }
        return null;
    };

    /**
     * Draws graph
     */
    Graph.prototype.draw = function() {
        let i;
        this.outerRect.draw('red');
        function drawFn(item) {
            return item.draw('cyan');
        }
        for (i = this.horizontalAxises.length - 1; i >= 0; i--) {
            this.horizontalAxises[i].allClones.forEach(drawFn);
        }
        for (i = this.verticalAxises.length - 1; i >= 0; i--) {
            this.verticalAxises[i].allClones.forEach(drawFn);
        }
        for (i = this.rectangles.length - 1; i >= 0; i--) {
            this.rectangles[i].draw('black');
        }
        for (const key in this.nodes) {
            if (this.nodes.hasOwnProperty(key)) {
                this.nodes[key].draw('black');
            }
        }
    };

    /**
     * Divides all axises into horizontal and vertical ones
     */
    Graph.prototype.createAxises = function() {
        for (let i = 0; i < this.baseAxises.length; i++) {
            const axis = this.baseAxises[i];
            if (axis.isVertical) {
                this.verticalAxises.push(axis);
            } else if (axis.a.y === axis.b.y) {
                this.horizontalAxises.push(axis);
            }
        }
    };

    /**
     * Removes axis
     *
     * @param {Axis} axis
     */
    Graph.prototype.removeAxis = function(axis) {
        let index;
        if ((index = this.horizontalAxises.indexOf(axis)) !== -1) {
            this.horizontalAxises.splice(index, 1);
            return;
        }
        if ((index = this.verticalAxises.indexOf(axis)) !== -1) {
            this.verticalAxises.splice(index, 1);
        }
    };

    /**
     * Processes merge axises queue
     */
    Graph.prototype.mergeAxises = function() {
        let i;
        let j;
        for (i = 0; i < this.mergeAxisesQueue.length; i++) {
            const queue = this.mergeAxisesQueue[i];
            for (j = queue.length - 1; j >= 1; j--) {
                queue[j - 1].merge(queue[j]);
                this.removeAxis(queue[j]);
            }
        }
    };

    /**
     * Performs final operations on graph for future usage.
     * - connects and sorts nodes
     * - sort axises
     */
    Graph.prototype.finalizeAxises = function() {
        let i;
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
    };

    /**
     * Prepares information about axis connection between each other
     */
    Graph.prototype.buildAxisConnectionInfo = function() {
        let i;
        let j;
        let node;
        let connectionToLeft;
        let nodeAtLeft;
        let connectionToRight;
        let nodeAtRight;
        let axis;

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

    /**
     * Adds into keeper information about axis connections
     *
     * @private
     * @param {Object} keeper
     * @param {Axis} main
     * @param {Axis} secondary
     */
    Graph.prototype.addAxisConnectionInfo = function(keeper, main, secondary) {
        if (!keeper[main.uid]) {
            keeper[main.uid] = [];
        }
        if (keeper[main.uid].indexOf(secondary) === -1) {
            keeper[main.uid].push(secondary);
        }
    };

    /**
     * Adds axises around initial rectangles
     */
    Graph.prototype.buildCornerAxises = function() {
        for (let i = this.rectangles.length - 1; i >= 0; i--) {
            const rect = this.rectangles[i];
            const defs = [
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
            for (let j = defs.length - 1; j >= 0; j--) {
                const def = defs[j];
                const closestRectCrossPoint1 = this.findClosestRectCross(def.vectorA, rect);
                const closestRectCrossPoint2 = this.findClosestRectCross(def.vectorB, rect);
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

    /**
     * Adds axises which go through center of initial rectangle
     */
    Graph.prototype.buildCenterAxises = function() {
        for (let i = this.rectangles.length - 1; i >= 0; i--) {
            const rect = this.rectangles[i];
            const center = rect.center;
            const defs = [
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
                    vector: new Vector2d(center.x + 1, center.y, directions.LEFT_TO_RIGHT),
                    leftConstraint: new LeftSimpleConstraint(rect.top),
                    rightConstraint: new RightSimpleConstraint(rect.bottom),
                    locationDirective: new CenterLocationDirective()
                },
                {
                    vector: new Vector2d(center.x - 1, center.y, directions.RIGHT_TO_LEFT),
                    leftConstraint: new LeftSimpleConstraint(rect.top),
                    rightConstraint: new RightSimpleConstraint(rect.bottom),
                    locationDirective: new CenterLocationDirective()
                }
            ];
            for (let j = defs.length - 1; j >= 0; j--) {
                const def = defs[j];
                const closestRectCrossPoint = this.findClosestRectCross(def.vector, rect);
                const axis = new BaseAxis(def.vector.start, closestRectCrossPoint, this, 1, def.leftConstraint,
                    def.rightConstraint, def.locationDirective);
                // removed "one-pixel axis"
                // const secondaryAxis = new BaseAxis(def.vector.start, def.vector.start, this, 1, new EmptyConstraint(),
                //     new EmptyConstraint(), new CenterLocationDirective());
                // secondaryAxis.isVertical = !axis.isVertical;
                // this.baseAxises.push(axis, secondaryAxis);
                this.baseAxises.push(axis);
            }
        }
    };

    /**
     * Iterator for all rectangle pairs
     * @param {Function} fn
     */
    Graph.prototype.eachRectanglePair = function(fn) {
        for (let i = this.rectangles.length - 1; i > 0; i--) {
            const rect1 = this.rectangles[i];
            for (let j = i - 1; j >= 0; j--) {
                fn(rect1, this.rectangles[j]);
            }
        }
    };

    /**
     * Adds axises between rectangles
     */
    Graph.prototype.buildCenterLinesBetweenNodes = function() {
        this.eachRectanglePair(function(a, b) {
            if (a.top > b.bottom && a.top - b.bottom > this.centerLineMinimalRequiredWidth) {
                this.buildSingleCenterLine(a, b, (a.top + b.bottom) / 2, a.topSide, b.bottomSide, a.top, b.bottom);
            }
            if (b.top > a.bottom && b.top - a.bottom > this.centerLineMinimalRequiredWidth) {
                this.buildSingleCenterLine(a, b, (b.top + a.bottom) / 2, b.topSide, a.bottomSide, b.top, a.bottom);
            }
            if (a.left > b.right && a.left - b.right > this.centerLineMinimalRequiredWidth) {
                this.buildSingleCenterLine(a, b, (a.left + b.right) / 2, a.leftSide, b.rightSide, a.left, b.right);
            }
            if (b.left > a.right && b.left - a.right > this.centerLineMinimalRequiredWidth) {
                this.buildSingleCenterLine(a, b, (b.left + a.right) / 2, b.leftSide, a.rightSide, b.left, a.right);
            }
        }.bind(this));
    };

    /**
     * Groups central axises within same area and replaces them with single axis
     */
    Graph.prototype.mergeExtraCenterAxises = function() {
        const maxDelta = this.centerLineMinimalRequiredWidth;

        this.baseAxises
            .filter(function(axis) {
                // filter only axises that are build between nodes
                return axis.leftConstraint instanceof LeftSimpleConstraint &&
                    axis.rightConstraint instanceof RightSimpleConstraint &&
                    axis.locationDirective instanceof CenterLocationDirective;
            })
            .reduce(function(groups, axis, i, axises) {
                // groups compatible central axises
                let group;
                let compatibleAxis;
                const even = axis.isVertical ? 'y' : 'x';
                const varying = axis.isVertical ? 'x' : 'y';

                // looks for compatible axis
                for (let j = axises.length - 1; j > i && !compatibleAxis; j--) {
                    if (
                        axises[j].a[even] === axis.a[even] &&
                        axises[j].b[even] === axis.b[even] &&
                        (
                            Math.max(axises[j].a[varying], axis.a[varying]) -
                            Math.min(axises[j].a[varying], axis.a[varying])
                        ) <= maxDelta
                    ) {
                        compatibleAxis = axises[j];
                    }
                }

                if (compatibleAxis) {
                    // check if group already exists
                    for (let n = 0; n < groups.length; n++) {
                        if (groups[n].indexOf(compatibleAxis) !== -1) {
                            group = groups[n];
                            break;
                        }
                    }

                    if (group) {
                        // add the axis to existing group
                        group.push(axis);
                    } else {
                        // create new group
                        groups.push([axis, compatibleAxis]);
                    }
                }

                return groups;
            }, [])
            .forEach(function(group) {
                const axis = group[0];
                // defines limits for common constraint
                const constraint = group.slice(1).reduce(function(constraint, axis) {
                    return {
                        left: Math.min(axis.leftConstraint.recomendedStart, constraint.left),
                        right: Math.max(axis.rightConstraint.recomendedStart, constraint.right)
                    };
                }, {
                    left: axis.leftConstraint.recomendedStart,
                    right: axis.rightConstraint.recomendedStart
                });

                // removes axises of group
                group.forEach(function(axis) {
                    this.baseAxises.splice(this.baseAxises.indexOf(axis), 1);
                }.bind(this));

                // create replacement axis
                const pointA = {};
                const pointB = {};
                const even = axis.isVertical ? 'y' : 'x';
                const varying = axis.isVertical ? 'x' : 'y';

                pointA[even] = axis.a[even];
                pointB[even] = axis.b[even];
                pointA[varying] = pointB[varying] = constraint.left + (constraint.right - constraint.left) / 2;

                this.baseAxises.push(new BaseAxis(
                    new Point2d(pointA.x, pointA.y),
                    new Point2d(pointB.x, pointB.y),
                    this,
                    settings.centerAxisCostMultiplier,
                    new LeftSimpleConstraint(constraint.left),
                    new RightSimpleConstraint(constraint.right),
                    new CenterLocationDirective()
                ));
            }.bind(this));
    };

    /**
     * Adds single axis between rectangles
     */
    Graph.prototype.buildSingleCenterLine = function(aRect, bRect, coordinate, a, b, min, max) {
        const aVector = new Vector2d(a.center.x, a.center.y, a.a.sub(a.b).rot270().unitVector);
        const bVector = new Vector2d(b.center.x, b.center.y, b.a.sub(b.b).rot90().unitVector);
        const crossRect = new Rectangle(Math.min(a.center.x, b.center.x), Math.min(a.center.y, b.center.y), 1, 1);
        let crossLine;
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
        const intersectionA = crossLine.intersection(aVector.line);
        const intersectionB = crossLine.intersection(bVector.line);
        const vector1 = new Vector2d(intersectionA.x, intersectionA.y, aVector.direction.rot90());
        const vector2 = new Vector2d(intersectionB.x, intersectionB.y, bVector.direction.rot90());
        const closestRectCrossPoint1 = this.findClosestRectCross(vector1, null);
        const closestRectCrossPoint2 = this.findClosestRectCross(vector2, null);
        this.baseAxises.push(new BaseAxis(closestRectCrossPoint1, closestRectCrossPoint2, this,
            settings.centerAxisCostMultiplier, new LeftSimpleConstraint(min),
            new RightSimpleConstraint(max), new CenterLocationDirective()));
    };
    Graph.prototype.buildNodes = function() {
        /*
         * add all nodes at axises cross points
         */
        let node;
        let i;
        let j;
        let hAxis;
        let vAxis;
        let crossPoint;
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
        this.buildNodesAtEndPoints();
    };

    /**
     * Build nodes at endpoints
     */
    Graph.prototype.buildNodesAtEndPoints = function() {
        const newVerticalAxises = this.buildNodesAtEndPointsVertical();
        const newHorizontalAxises = this.buildNodesAtEndPointsHorizontal();
        this.verticalAxises.push(...newVerticalAxises);
        this.horizontalAxises.push(...newHorizontalAxises);
    };

    /**
     * Build nodes at endpoints on horizontal axises
     */
    Graph.prototype.buildNodesAtEndPointsHorizontal = function() {
        let node;
        let newAxis;
        let i;
        let hAxis;
        const newVerticalAxises = [];
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
                newVerticalAxises.push(newAxis);
                hAxis.addNode(node);
                newAxis.addNode(node);
                node.hAxis = hAxis;
                node.vAxis = newAxis;
            }
        }
        return newVerticalAxises;
    };

    /**
     * Build nodes at endpoints on vertical axises
     */
    Graph.prototype.buildNodesAtEndPointsVertical = function() {
        let node;
        let newAxis;
        let i;
        let vAxis;
        const newHorizontalAxises = [];
        for (i = this.verticalAxises.length - 1; i >= 0; i--) {
            vAxis = this.verticalAxises[i];
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
                newHorizontalAxises.push(newAxis);
                vAxis.addNode(node);
                newAxis.addNode(node);
                node.hAxis = newAxis;
                node.vAxis = vAxis;
            }
        }
        return newHorizontalAxises;
    };

    /**
     * Prepares merge axises requests
     */
    Graph.prototype.buildMergeRequests = function() {
        for (let i = this.horizontalAxises.length - 1; i >= 0; i--) {
            const hAxis = this.horizontalAxises[i];
            for (let j = this.verticalAxises.length - 1; j >= 0; j--) {
                const vAxis = this.verticalAxises[j];
                const crossPoint = hAxis.getCrossPoint(vAxis);
                if (crossPoint) {
                    const node = this.getNodeAt(crossPoint);
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

    /**
     * Adds single merge axis request. Takes care about queue structure
     */
    Graph.prototype.addMergeRequest = function(a, b) {
        let foundAQueue;
        let foundBQueue;
        let i;
        let queue;
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
                foundAQueue.push(...foundBQueue);
                this.mergeAxisesQueue.splice(this.mergeAxisesQueue.indexOf(foundBQueue), 1);
            } else {
                foundAQueue.push(b);
            }
        }
    };

    /**
     * Returns node at certain point at graph
     * @param {Point2d} point
     * @returns {NodePoint}
     */
    Graph.prototype.getNodeAt = function(point) {
        let node = this.nodes[point.id];
        if (!node) {
            node = new NodePoint(point.x, point.y);
            this.nodes[point.id] = node;
        }
        return node;
    };

    /**
     * Finds closes initial rectangle (or outer rectangle) cross point by specified vector
     *
     * @param {Vector2d} vector
     * @param {Rectangle} ignoreRect
     * @returns {*}
     */
    Graph.prototype.findClosestRectCross = function(vector, ignoreRect) {
        let closestDistance = Infinity;
        let closestPoint = null;
        for (let i = this.rectangles.length - 1; i >= 0; i--) {
            const rect = this.rectangles[i];
            if (rect === ignoreRect) {
                continue;
            }
            const crossPoint = vector.getCrossPointWithRect(rect);
            if (crossPoint && closestDistance > crossPoint.distanceTo(vector.start)) {
                closestPoint = crossPoint;
                closestDistance = crossPoint.distanceTo(vector.start);
            }
        }
        if (closestDistance === Infinity) {
            this.outerRect.eachSide(function(side) {
                let crossPoint;
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

    /**
     * Returns true if specified rectangle intersects any initial rectangle on graph
     *
     * @param {Rectangle} rectangle
     * @param {Rectangle} ignoreRect
     * @returns {boolean}
     */
    Graph.prototype.rectangleIntersectsAnyRectangle = function(rectangle, ignoreRect) {
        for (let i = this.rectangles.length - 1; i >= 0; i--) {
            if (this.rectangles[i] === ignoreRect) {
                continue;
            }
            const intersection = rectangle.intersection(this.rectangles[i]);
            // non-inclusive
            if (intersection !== null && intersection.width !== 0 && intersection.height !== 0) {
                return true;
            }
        }
        return false;
    };

    /**
     * Updates graph with path. Ensures its traversability.
     *
     * @param {Path} path
     */
    Graph.prototype.updateWithPath = function(path) {
        let connections = path.allConnections;
        const axises = [];
        let i;
        let conn;
        let prev;
        for (i = 0; i < connections.length; i++) {
            conn = connections[i];
            if (axises.indexOf(conn.axis) === -1) {
                axises.push(conn.axis);
                conn.axis.ensureTraversableSiblings();
                conn.axis.used = true;
                conn.axis.costMultiplier *= settings.usedAxisCostMultiplier;
            }
        }
        connections = path.allConnections;
        for (i = 0; i < connections.length; i++) {
            conn = connections[i];
            conn.traversable = false;
            conn.a.used = true;
            conn.b.used = true;

            if (prev && conn.vector.id !== prev.vector.id) {
                // corner
                // all connections are used on corner
                // this will avoid double corner use
                const midNode = conn.a === prev.a || conn.a === prev.b ? conn.a : conn.b;
                midNode.eachConnection(function(conn) {
                    conn.traversable = false;
                });
            }
            prev = conn;
        }
    };

    /**
     * Locates axises to be able get valid path points
     */
    Graph.prototype.locateAxises = function() {
        let i;
        let j;
        let axis;
        let clones;
        let current;
        let clone;
        for (i = this.verticalAxises.length - 1; i >= 0; i--) {
            axis = this.verticalAxises[i];
            clones = axis.allClones;
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
    };

    /**
     * Returns if this connection is under any rectangle
     *
     * @TODO optimize this!
     *
     * @param interval
     * @returns {boolean}
     */
    Graph.prototype.isConnectionUnderRect = function(interval) {
        for (let i = this.rectangles.length - 1; i >= 0; i--) {
            const rect = this.rectangles[i];
            if (rect.containsPoint(interval.a) || rect.containsPoint(interval.b)) {
                return true;
            }
        }
        return false;
    };
    return Graph;
});
