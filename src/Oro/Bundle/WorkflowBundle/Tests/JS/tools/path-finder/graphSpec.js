define(function(require) {
    'use strict';

    const Graph = require('oroworkflow/js/tools/path-finder/graph');
    const Rectangle = require('oroworkflow/js/tools/path-finder/rectangle');
    const directions = require('oroworkflow/js/tools/path-finder/directions');
    const _ = require('underscore');

    describe('oroworkflow/js/tools/path-finder/graph', function() {
        beforeEach(function prepareGraph() {
            window.setFixtures('<div class="workflow-flowchart-editor"></div>');
            const graph = new Graph();
            graph.outerRect = new Rectangle(0, 0, 500, 500);
            graph.rectangles.push(new Rectangle(100, 100, 100, 100));
            this.graph = graph;
        });

        function isConnected(node) {
            return node.connections[directions.TOP_TO_BOTTOM.id] ||
                node.connections[directions.BOTTOM_TO_TOP.id] ||
                node.connections[directions.LEFT_TO_RIGHT.id] ||
                node.connections[directions.RIGHT_TO_LEFT.id];
        }

        it('should add axises around block', function() {
            const graph = this.graph;
            const firstRect = graph.rectangles[0];
            graph.buildCornerAxises();
            expect(graph.baseAxises.length).toBe(4);
            expect(_.any(graph.baseAxises, function(axis) {
                return axis.a.x === firstRect.top;
            })).toBeTruthy();
            expect(_.any(graph.baseAxises, function(axis) {
                return axis.a.x === firstRect.bottom;
            })).toBeTruthy();
            expect(_.any(graph.baseAxises, function(axis) {
                return axis.a.y === firstRect.left;
            })).toBeTruthy();
            expect(_.any(graph.baseAxises, function(axis) {
                return axis.a.y === firstRect.right;
            })).toBeTruthy();
        });

        it('should add axises out from block center', function() {
            const graph = this.graph;
            const firstRect = graph.rectangles[0];
            const rectCenter = firstRect.center;
            graph.buildCenterAxises();

            const vX = _.countBy(graph.baseAxises, function(axis) {
                return axis.a.x;
            });
            const vY = _.countBy(graph.baseAxises, function(axis) {
                return axis.a.y;
            });

            expect(graph.baseAxises.length).toBe(4);

            expect(vX[rectCenter.x - 1]).toBe(1);
            expect(vX[rectCenter.x]).toBe(2);
            expect(vX[rectCenter.x + 1]).toBe(1);

            expect(vY[rectCenter.y - 1]).toBe(1);
            expect(vY[rectCenter.y]).toBe(2);
            expect(vY[rectCenter.y + 1]).toBe(1);
        });

        it('should add axises at center between block pairs', function() {
            const graph = this.graph;
            graph.rectangles.push(new Rectangle(300, 300, 100, 100));
            graph.buildCenterLinesBetweenNodes();
            expect(graph.baseAxises.length).toBe(2);
            expect(_.every(graph.baseAxises, function(ax) {
                return ax.a.x === 250 || ax.a.y === 250;
            })).toBeTruthy();
        });

        it('should add merge compatible center axis between block pairs', function() {
            const graph = this.graph;
            graph.rectangles.push(new Rectangle(300, 300, 100, 100));
            graph.rectangles.push(new Rectangle(120, 320, 100, 100));
            graph.buildCenterLinesBetweenNodes();

            const objContains = jasmine.objectContaining.bind(jasmine);
            expect(graph.baseAxises.length).toBe(3);
            graph.mergeExtraCenterAxises();
            expect(graph.baseAxises).toEqual([
                objContains({_a: objContains({x: 260, y: 0}), _b: objContains({x: 260, y: 500})}),
                objContains({_a: objContains({x: 0, y: 250}), _b: objContains({x: 500, y: 250})})
            ]);
        });

        it('should finalize (setup connections) correctly', function() {
            const graph = this.graph;
            graph.rectangles.push(new Rectangle(300, 300, 100, 100));
            graph.build();
            expect(graph.verticalAxises.length).toBe(graph.horizontalAxises.length);
            expect(graph.verticalAxises.length).toBe(11);
            for (const id in graph.nodes) {
                if (graph.nodes.hasOwnProperty(id)) {
                    expect(isConnected(graph.nodes[id])).toBeTruthy();
                    expect(graph.nodes[id].vAxis).not.toBe(graph.nodes[id].hAxis);
                }
            }
        });
    });
});
