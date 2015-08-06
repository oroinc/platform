define(function(require) {
    'use strict';

    var Axis = require('oroworkflow/js/tools/path-finder/axis');
    var Graph = require('oroworkflow/js/tools/path-finder/graph');
    var Point2d = require('oroworkflow/js/tools/path-finder/point2d');
    var NodePoint = require('oroworkflow/js/tools/path-finder/node-point');
    var directions = require('oroworkflow/js/tools/path-finder/directions');

    describe('oroworkflow/js/tools/path-finder/axis', function() {
        function createAxisWithThreeNodes() {
            var graph = new Graph();
            var axis = new Axis(new Point2d(0, 0), new Point2d(0, 100), graph, 1);
            var a = new NodePoint(0, 0);
            var b = new NodePoint(0, 50);
            var c = new NodePoint(0, 100);
            a.hAxis = new Axis(a.clone(), a.clone(), graph, 1);
            a.hAxis.addNode(a);
            a.hAxis.isVertical = false;
            b.hAxis = new Axis(b.clone(), b.clone(), graph, 1);
            b.hAxis.addNode(b);
            b.hAxis.isVertical = false;
            c.hAxis = new Axis(c.clone(), c.clone(), graph, 1);
            c.hAxis.addNode(c);
            c.hAxis.isVertical = false;
            axis.addNode(a);
            axis.addNode(b);
            axis.addNode(c);
            axis.sortNodes();
            axis.finalize();
            return axis;
        }

        it('should construct', function() {
            var axis1 = new Axis(new Point2d(0, 0), new Point2d(0, 100), null, 1);
            expect(axis1.costMultiplier).toBe(1);
            expect(axis1.isVertical).toBe(true);
            expect(axis1.used).toBe(false);
            expect(axis1.graph).toBe(null);

            var axis2 = new Axis(new Point2d(0, 0), new Point2d(100, 0), null, 1);
            expect(axis2.isVertical).toBe(false);
            expect(axis2.used).toBe(false);
            expect(axis2.graph).toBe(null);

            expect(axis2.uid).not.toBe(axis1.uid);
        });

        it('should add nodes and finalize correctly', function() {
            var graph = new Graph();
            var axis = new Axis(new Point2d(0, 0), new Point2d(0, 100), graph, 1);

            axis.addNode(new NodePoint(0, 0));
            expect(axis.nodes[0].x).toBe(0);
            expect(axis.nodes[0].y).toBe(0);

            axis.addNode(new NodePoint(0, 100));
            expect(axis.nodes[1].x).toBe(0);
            expect(axis.nodes[1].y).toBe(100);

            axis.addNode(new NodePoint(0, 50));
            expect(axis.nodes[2].x).toBe(0);
            expect(axis.nodes[2].y).toBe(50);

            expect(axis.nodes.length).toBe(3);

            axis.sortNodes();

            expect(axis.nodes[0].y).toBe(0);
            expect(axis.nodes[1].y).toBe(50);
            expect(axis.nodes[2].y).toBe(100);

            axis.finalize();

            expect(axis.nodes[0].connections[directions.BOTTOM_TO_TOP.id]).not.toBeDefined();
            expect(axis.nodes[0].connections[directions.TOP_TO_BOTTOM.id]).toBeDefined();
            expect(axis.nodes[1].connections[directions.BOTTOM_TO_TOP.id]).toBeDefined();
            expect(axis.nodes[1].connections[directions.TOP_TO_BOTTOM.id]).toBeDefined();
            expect(axis.nodes[2].connections[directions.BOTTOM_TO_TOP.id]).toBeDefined();
            expect(axis.nodes[2].connections[directions.TOP_TO_BOTTOM.id]).not.toBeDefined();
        });

        it('should add final nodes correctly', function() {
            var graph = new Graph();
            var axis = new Axis(new Point2d(0, 0), new Point2d(0, 100), graph, 1);
            var topNode = new NodePoint(0, 0);
            var bottomNode = new NodePoint(0, 100);
            axis.addNode(topNode);
            axis.addNode(bottomNode);
            axis.sortNodes();
            axis.finalize();

            var centerNode = new NodePoint(0, 50);
            centerNode.vAxis = axis;
            centerNode.connect(directions.BOTTOM_TO_TOP, topNode);
            centerNode.connect(directions.TOP_TO_BOTTOM, bottomNode);

            axis.addFinalNode(centerNode);

            expect(axis.nodes.length).toBe(3);
            expect(axis.nodes[0]).toEqual(topNode);
            expect(axis.nodes[1]).toEqual(centerNode);
            expect(axis.nodes[2]).toEqual(bottomNode);

            var topClone = topNode.clone();
            topClone.connect(directions.TOP_TO_BOTTOM, topNode);
            axis.addFinalNode(topClone);
            expect(axis.nodes[0]).toEqual(topClone);
            expect(axis.nodes[1]).toEqual(topNode);

            var bottomClone = bottomNode.clone();
            bottomClone.connect(directions.BOTTOM_TO_TOP, bottomNode);
            axis.addFinalNode(bottomClone);
            expect(axis.nodes[axis.nodes.length - 2]).toEqual(bottomNode);
            expect(axis.nodes[axis.nodes.length - 1]).toEqual(bottomClone);
        });

        it('should have valid connection vectors', function() {
            var axisV = createAxisWithThreeNodes();
            expect(axisV.nextNodeConnVector.id).toBe(directions.TOP_TO_BOTTOM.id);
            expect(axisV.prevNodeConnVector.id).toBe(directions.BOTTOM_TO_TOP.id);

            var axisH = new Axis(new Point2d(0, 0), new Point2d(100, 0), null, 1);
            expect(axisH.nextNodeConnVector.id).toBe(directions.LEFT_TO_RIGHT.id);
            expect(axisH.prevNodeConnVector.id).toBe(directions.RIGHT_TO_LEFT.id);

            axisH = new Axis(new Point2d(100, 0), new Point2d(0, 0), null, 1);
            expect(axisH.nextNodeConnVector.id).toBe(directions.LEFT_TO_RIGHT.id);
            expect(axisH.prevNodeConnVector.id).toBe(directions.RIGHT_TO_LEFT.id);
        });

        it('should have valid connection list', function() {
            var axis = createAxisWithThreeNodes();
            expect(axis.connections.length).toBe(2);
            expect(axis.connections[0]).toEqual(axis.nodes[1].connections[directions.TOP_TO_BOTTOM.id]);
            expect(axis.connections[0]).toEqual(axis.nodes[2].connections[directions.BOTTOM_TO_TOP.id]);
            expect(axis.connections[1]).toEqual(axis.nodes[0].connections[directions.TOP_TO_BOTTOM.id]);
            expect(axis.connections[1]).toEqual(axis.nodes[1].connections[directions.BOTTOM_TO_TOP.id]);
        });

        it('should ensure traversable siblings', function() {
            var axis = createAxisWithThreeNodes();

            expect(axis.allClones.length).toBe(1);
            axis.ensureTraversableSiblings();
            expect(axis.closestLeftClone).toBeDefined();
            expect(axis.closestRightClone).toBeDefined();
            expect(axis.allClones.length).toBe(3);

            var leftClone = axis.closestLeftClone;
            var rightClone = axis.closestRightClone;
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
