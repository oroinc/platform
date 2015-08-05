define(function(require) {
    'use strict';

    var Axis = require('oroworkflow/js/tools/path-finder/axis');
    var Graph = require('oroworkflow/js/tools/path-finder/graph');
    var Point2d = require('oroworkflow/js/tools/path-finder/point2d');
    var NodePoint = require('oroworkflow/js/tools/path-finder/node-point');
    var Connection = require('oroworkflow/js/tools/path-finder/connection');
    var directions = require('oroworkflow/js/tools/path-finder/directions');

    describe('oroworkflow/js/tools/path-finder/axis', function() {
        it('should construct', function () {
            var axis1 = new Axis(new Point2d(0,0), new Point2d(0, 100), null, 1);
            expect(axis1.costMultiplier).toBe(1);
            expect(axis1.isVertical).toBe(true);
            expect(axis1.used).toBe(false);
            expect(axis1.graph).toBe(null);

            var axis2 = new Axis(new Point2d(0,0), new Point2d(100, 0), null, 1);
            expect(axis2.isVertical).toBe(false);
            expect(axis2.used).toBe(false);
            expect(axis2.graph).toBe(null);

            expect(axis2.uid).not.toBe(axis1.uid);
        });
        it('should add nodes and finalize correctly', function() {
            var graph = new Graph();
            var axis = new Axis(new Point2d(0,0), new Point2d(0, 100), graph, 1);

            axis.addNode(new NodePoint(0,0));
            expect(axis.nodes[0].x).toBe(0);
            expect(axis.nodes[0].y).toBe(0);

            axis.addNode(new NodePoint(0,100));
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
            var axis = new Axis(new Point2d(0,0), new Point2d(0, 100), graph, 1);
            var topNode = new NodePoint(0,0);
            var bottomNode = new NodePoint(0,100);
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
        });
        it('should ensure traversable siblings', function() {
            // ensureTraversableSiblings
        });
        it('should have valid connection vectors', function() {
            // nextNodeConnVector
        });
        it('should have valid connection list', function() {

        });
        it('should be cloneable', function() {
            // clone
            // closest left/right clones
            // all clones
        });
        it('should draw', function() {
            // nextNodeConnVector
        });
    });
});
