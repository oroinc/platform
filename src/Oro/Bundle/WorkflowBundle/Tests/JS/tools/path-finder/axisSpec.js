define(function(require) {
    'use strict';

    const Axis = require('oroworkflow/js/tools/path-finder/axis');
    const Graph = require('oroworkflow/js/tools/path-finder/graph');
    const Point2d = require('oroworkflow/js/tools/path-finder/point2d');
    const NodePoint = require('oroworkflow/js/tools/path-finder/node-point');
    const directions = require('oroworkflow/js/tools/path-finder/directions');

    describe('oroworkflow/js/tools/path-finder/axis', function() {
        beforeEach(function() {
            this.graph = new Graph();
            this.axis = new Axis(new Point2d(0, 0), new Point2d(0, 100), this.graph, 1);
        });

        function mapNodesToCoordinates(node) {
            return [node.x, node.y];
        }

        function axisLocation(axis) {
            return [axis.a.x, axis.a.y, axis.b.x, axis.b.x];
        }

        it('should construct', function() {
            const graph = this.graph;
            const axis1 = this.axis;
            expect(axis1.costMultiplier).toBe(1);
            expect(axis1.isVertical).toBe(true);
            expect(axis1.used).toBe(false);
            expect(axis1.graph).toBe(graph);

            const axis2 = new Axis(new Point2d(0, 0), new Point2d(100, 0), graph, 1);
            expect(axis2.isVertical).toBe(false);
            expect(axis2.used).toBe(false);
            expect(axis2.graph).toBe(graph);

            expect(axis2.uid).not.toBe(axis1.uid);
        });

        it('should save axis into node during addition', function() {
            const verticalAxis = this.axis;
            const horizontalAxis = new Axis(new Point2d(0, 0), new Point2d(100, 0), this.graph, 1);
            const node = new NodePoint(0, 0);
            verticalAxis.addNode(node);
            horizontalAxis.addNode(node);

            expect(node.hAxis).toBe(horizontalAxis);
            expect(node.vAxis).toBe(verticalAxis);
        });
        it('should add nodes and finalize correctly', function() {
            const axis = this.axis;
            const node1 = new NodePoint(0, 0);
            const node2 = new NodePoint(0, 100);
            const node3 = new NodePoint(0, 50);
            axis.addNode(node1);
            axis.addNode(node2);
            axis.addNode(node3);

            expect(axis.nodes.length).toBe(3);

            axis.sortNodes();
            expect(axis.nodes).toEqual([node1, node3, node2]);

            axis.finalize();

            expect(axis.nodes[0].connections[directions.BOTTOM_TO_TOP.id]).not.toBeDefined();
            expect(axis.nodes[0].connections[directions.TOP_TO_BOTTOM.id]).toBeDefined();
            expect(axis.nodes[1].connections[directions.BOTTOM_TO_TOP.id]).toBeDefined();
            expect(axis.nodes[1].connections[directions.TOP_TO_BOTTOM.id]).toBeDefined();
            expect(axis.nodes[2].connections[directions.BOTTOM_TO_TOP.id]).toBeDefined();
            expect(axis.nodes[2].connections[directions.TOP_TO_BOTTOM.id]).not.toBeDefined();
        });

        it('should add final nodes correctly', function() {
            const axis = this.axis;
            const topNode = new NodePoint(0, 0);
            const bottomNode = new NodePoint(0, 100);
            axis.addNode(topNode);
            axis.addNode(bottomNode);
            axis.sortNodes();
            axis.finalize();

            const centerNode = new NodePoint(0, 50);
            centerNode.vAxis = axis;
            centerNode.connect(directions.BOTTOM_TO_TOP, topNode);
            centerNode.connect(directions.TOP_TO_BOTTOM, bottomNode);

            axis.addFinalNode(centerNode);

            expect(axis.nodes.length).toBe(3);
            expect(axis.nodes[0]).toEqual(topNode);
            expect(axis.nodes[1]).toEqual(centerNode);
            expect(axis.nodes[2]).toEqual(bottomNode);

            const topClone = topNode.clone();
            topClone.connect(directions.TOP_TO_BOTTOM, topNode);
            axis.addFinalNode(topClone);
            expect(axis.nodes[0]).toEqual(topClone);
            expect(axis.nodes[1]).toEqual(topNode);

            const bottomClone = bottomNode.clone();
            bottomClone.connect(directions.BOTTOM_TO_TOP, bottomNode);
            axis.addFinalNode(bottomClone);
            expect(axis.nodes[axis.nodes.length - 2]).toEqual(bottomNode);
            expect(axis.nodes[axis.nodes.length - 1]).toEqual(bottomClone);
        });

        describe('axis connections', function() {
            beforeEach(function() {
                const graph = this.graph;
                const axis = this.axis;
                const a = new NodePoint(0, 0);
                const b = new NodePoint(0, 50);
                const c = new NodePoint(0, 100);
                a.hAxis = new Axis(a.clone(), a.clone(), graph, 1);
                a.hAxis.addNode(a);
                a.hAxis.finalize();
                a.hAxis.isVertical = false;
                b.hAxis = new Axis(b.clone(), b.clone(), graph, 1);
                b.hAxis.addNode(b);
                b.hAxis.finalize();
                b.hAxis.isVertical = false;
                c.hAxis = new Axis(c.clone(), c.clone(), graph, 1);
                c.hAxis.addNode(c);
                c.hAxis.finalize();
                c.hAxis.isVertical = false;
                axis.addNode(a);
                axis.addNode(b);
                axis.addNode(c);
                axis.sortNodes();
                axis.finalize();
            });

            it('should clone itself at direction', function() {
                const axisV = this.axis;

                const leftClone = axisV.cloneAtDirection(directions.LEFT_TO_RIGHT);
                expect(leftClone.nodes.map(mapNodesToCoordinates))
                    .toEqual(axisV.nodes.map(mapNodesToCoordinates));

                const rightClone = axisV.cloneAtDirection(directions.RIGHT_TO_LEFT);
                expect(rightClone.nodes.map(mapNodesToCoordinates))
                    .toEqual(axisV.nodes.map(mapNodesToCoordinates));
            });

            it('should merge another axis into itself', function() {
                const biggerAxis = this.axis;
                const initialLocation = axisLocation(biggerAxis);
                const node1 = new NodePoint(0, 25);
                const node2 = new NodePoint(0, 75);
                const smallerAxis = new Axis(node1, node2, this.graph);
                biggerAxis.merge(smallerAxis);
                expect(axisLocation(biggerAxis)).toEqual(initialLocation);
                smallerAxis.merge(biggerAxis);
                expect(axisLocation(smallerAxis)).toEqual(axisLocation(biggerAxis));
            });

            it('should have valid connection vectors', function() {
                const axisV = this.axis;
                expect(axisV.nextNodeConnVector.id).toBe(directions.TOP_TO_BOTTOM.id);
                expect(axisV.prevNodeConnVector.id).toBe(directions.BOTTOM_TO_TOP.id);

                let axisH = new Axis(new Point2d(0, 0), new Point2d(100, 0), null, 1);
                expect(axisH.nextNodeConnVector.id).toBe(directions.LEFT_TO_RIGHT.id);
                expect(axisH.prevNodeConnVector.id).toBe(directions.RIGHT_TO_LEFT.id);

                axisH = new Axis(new Point2d(100, 0), new Point2d(0, 0), null, 1);
                expect(axisH.nextNodeConnVector.id).toBe(directions.LEFT_TO_RIGHT.id);
                expect(axisH.prevNodeConnVector.id).toBe(directions.RIGHT_TO_LEFT.id);
            });

            it('should have valid connection list', function() {
                const axis = this.axis;
                expect(axis.connections.length).toBe(2);
                expect(axis.connections[0]).toEqual(axis.nodes[1].connections[directions.TOP_TO_BOTTOM.id]);
                expect(axis.connections[0]).toEqual(axis.nodes[2].connections[directions.BOTTOM_TO_TOP.id]);
                expect(axis.connections[1]).toEqual(axis.nodes[0].connections[directions.TOP_TO_BOTTOM.id]);
                expect(axis.connections[1]).toEqual(axis.nodes[1].connections[directions.BOTTOM_TO_TOP.id]);
            });

            it('should ensure traversable siblings', function() {
                const axis = this.axis;

                expect(axis.allClones.length).toBe(1);
                axis.ensureTraversableSiblings();
                expect(axis.closestLeftClone).toBeDefined();
                expect(axis.closestRightClone).toBeDefined();
                expect(axis.allClones.length).toBe(3);

                const leftClone = axis.closestLeftClone;
                const rightClone = axis.closestRightClone;
                axis.ensureTraversableSiblings();
                expect(axis.closestLeftClone).toBe(leftClone);
                expect(axis.closestRightClone).toBe(rightClone);
                expect(axis.allClones.length).toBe(3);

                leftClone.isUsed = true;
                axis.ensureTraversableSiblings();
                expect(axis.closestLeftClone).not.toBe(leftClone);
                expect(axis.closestRightClone).toBe(rightClone);
                expect(axis.allClones.length).toBe(4);

                rightClone.isUsed = true;
                axis.ensureTraversableSiblings();
                expect(axis.closestLeftClone).not.toBe(leftClone);
                expect(axis.closestRightClone).not.toBe(rightClone);
                expect(axis.allClones.length).toBe(5);
            });
        });
    });
});
