define(function(require) {
    'use strict';

    var Graph = require('oroworkflow/js/tools/path-finder/graph');
    var Rectangle = require('oroworkflow/js/tools/path-finder/rectangle');

    describe('oroworkflow/js/tools/path-finder/graph', function() {
        function prepareGraph() {
            var graph = new Graph();
            graph.outerRect = new Rectangle (0, 0, 500, 500);
            graph.rectangles.push(new Rectangle(100, 100, 100, 100));
            return graph;
        }
        it('should add axises around block', function () {
            var graph = prepareGraph();
            graph.buildCornerAxises();
            expect(graph.baseAxises.length).toBe(4);
        });
        it('should add axises out from block center', function () {
            var graph = prepareGraph();
            graph.buildCenterAxises();
            expect(graph.baseAxises.length).toBe(8);
        });
        it('should add axises at center between block pairs', function () {
            var graph = prepareGraph();
            graph.rectangles.push(new Rectangle(300, 300, 100, 100));
            graph.buildCenterLinesBetweenNodes();
            expect(graph.baseAxises.length).toBe(2);
        });
        it('should finalize (setup connections) correctly', function () {
            var graph = prepareGraph();
            graph.rectangles.push(new Rectangle(300, 300, 100, 100));
            graph.build();
            expect(graph.verticalAxises.length).toBe(graph.horizontalAxises.length);
        });
        it('should keep graph traversable after updating with path', function () {

        });
        it('should setup valid non traversable marks', function () {

        });
        it('should update axises costs', function () {

        });
    });
});
