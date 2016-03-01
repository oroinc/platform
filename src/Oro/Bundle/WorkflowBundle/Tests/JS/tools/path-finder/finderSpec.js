define(function(require) {
    'use strict';

    var Graph = require('oroworkflow/js/tools/path-finder/graph');
    var Rectangle = require('oroworkflow/js/tools/path-finder/rectangle');
    var Finder = require('oroworkflow/js/tools/path-finder/finder');
    var directions = require('oroworkflow/js/tools/path-finder/directions');

    describe('oroworkflow/js/tools/path-finder/finder', function() {

        beforeEach(function prepareGraph() {
            var graph = new Graph();
            var rect1 = new Rectangle(100, 100, 100, 100);
            var rect2 = new Rectangle(300, 300, 100, 100);
            rect1.cid = 'rect1';
            rect2.cid = 'rect2';
            graph.rectangles.push(rect1);
            graph.rectangles.push(rect2);
            graph.build();
            this.graph = graph;
            this.finder = new Finder();
        });

        it('should add `to` path specs', function() {
            var graph = this.graph;
            var finder = this.finder;

            finder.addTo(graph.getPathFromCid('rect1', directions.BOTTOM_TO_TOP));
            expect(finder.to.length).toBe(1);
            expect(finder.to[0].connection ===
                graph.getPathFromCid('rect1', directions.BOTTOM_TO_TOP).connection).toBeTruthy();
            expect(finder.to[0].fromNode ===
                graph.getPathFromCid('rect1', directions.BOTTOM_TO_TOP).fromNode).toBeTruthy();
        });

        it('should add `to` path siblings', function() {
            var graph = this.graph;
            var finder = this.finder;
            graph.updateWithPath(graph.getPathFromCid('rect1', directions.BOTTOM_TO_TOP));
            finder.addTo(graph.getPathFromCid('rect1', directions.BOTTOM_TO_TOP));
            expect(finder.to.length).toBe(3);
        });

        it('should add `from` path specs', function() {
            var graph = this.graph;
            var finder = this.finder;
            expect(function() {
                finder.addFrom(graph.getPathFromCid('rect1', directions.BOTTOM_TO_TOP));
            }).toThrow();
            finder.addTo(graph.getPathFromCid('rect1', directions.BOTTOM_TO_TOP));

            finder.addFrom(graph.getPathFromCid('rect2', directions.TOP_TO_BOTTOM));

            expect(finder.from.length).toBe(1);
            expect(finder.from[0].connection ===
                graph.getPathFromCid('rect2', directions.TOP_TO_BOTTOM).connection).toBeTruthy();
            expect(finder.from[0].addFrom ===
                graph.getPathFromCid('rect2', directions.TOP_TO_BOTTOM).addFrom).toBeTruthy();
        });

        it('should add `from` path siblings', function() {
            var graph = this.graph;
            var finder = this.finder;
            graph.updateWithPath(graph.getPathFromCid('rect2', directions.TOP_TO_BOTTOM));
            expect(function() {
                finder.addFrom(graph.getPathFromCid('rect1', directions.BOTTOM_TO_TOP));
            }).toThrow();
            finder.addTo(graph.getPathFromCid('rect1', directions.BOTTOM_TO_TOP));

            finder.addFrom(graph.getPathFromCid('rect2', directions.TOP_TO_BOTTOM));

            expect(finder.from.length).toBe(3);
        });

        it('should find pathes', function() {
            var graph = this.graph;
            for (var firstDirection in directions) {
                if (directions.hasOwnProperty(firstDirection)) {
                    for (var secondDirection in directions) {
                        if (directions.hasOwnProperty(secondDirection)) {
                            var finder = new Finder();
                            finder.addTo(graph.getPathFromCid('rect1', directions[firstDirection]));
                            finder.addFrom(graph.getPathFromCid('rect2', directions[secondDirection]));
                            expect(finder.find()).toBeDefined();

                            // opposite direction
                            finder = new Finder();
                            finder.addTo(graph.getPathFromCid('rect2', directions[firstDirection]));
                            finder.addFrom(graph.getPathFromCid('rect1', directions[secondDirection]));
                            expect(finder.find()).toBeDefined();
                        }
                    }
                }
            }
        });

        it('should select center axis', function() {
            var graph = this.graph;
            var finder = this.finder;
            finder.addTo(graph.getPathFromCid('rect1', directions.TOP_TO_BOTTOM));
            finder.addFrom(graph.getPathFromCid('rect2', directions.BOTTOM_TO_TOP));
            expect(finder.find().allConnections[2].a.y).toBe(250);

            finder = new Finder();
            finder.addTo(graph.getPathFromCid('rect1', directions.LEFT_TO_RIGHT));
            finder.addFrom(graph.getPathFromCid('rect2', directions.RIGHT_TO_LEFT));
            expect(finder.find().allConnections[2].a.x).toBe(250);
        });
    });
});
