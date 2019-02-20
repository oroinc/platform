define(function(require) {
    'use strict';

    var _ = require('underscore');
    var Path = require('oroworkflow/js/tools/path-finder/path');
    var Graph = require('oroworkflow/js/tools/path-finder/graph');
    var Point2d = require('oroworkflow/js/tools/path-finder/point2d');
    var Rectangle = require('oroworkflow/js/tools/path-finder/rectangle');
    var directions = require('oroworkflow/js/tools/path-finder/directions');

    describe('oroworkflow/js/tools/path-finder/path', function() {
        beforeEach(function prepareGraph() {
            window.setFixtures('<div class="workflow-flowchart-editor"></div>');
            var graph = new Graph();
            graph.rectangles.push(new Rectangle(100, 100, 100, 100));
            graph.rectangles.push(new Rectangle(300, 300, 100, 100));
            graph.build();
            this.graph = graph;
            this.node100100 = this.graph.getNodeAt(new Point2d(100, 100));
            this.node150100 = this.graph.getNodeAt(new Point2d(150, 100));
            this.node150250 = this.graph.getNodeAt(new Point2d(150, 250));
            this.node250250 = this.graph.getNodeAt(new Point2d(250, 250));
        });

        it('should construct', function() {
            var path = new Path(this.node150250.connections[directions.LEFT_TO_RIGHT.id], this.node150250, null);

            expect(path.connection).toBe(this.node150250.connections[directions.LEFT_TO_RIGHT.id]);
            expect(path.fromNode).toBe(this.node150250);
            expect(path.toNode).toBe(this.node250250);
            expect(path.cost).toBe(98);
        });

        it('should return uid', function() {
            var path1 = new Path(this.node250250.connections[directions.LEFT_TO_RIGHT.id], this.node250250, null);
            var path2 = new Path(this.node250250.connections[directions.RIGHT_TO_LEFT.id], this.node250250, null);
            var path3 = new Path(this.node250250.connections[directions.TOP_TO_BOTTOM.id], this.node250250, null);
            var path4 = new Path(this.node250250.connections[directions.BOTTOM_TO_TOP.id], this.node250250, null);
            var uids = [path1.uid, path2.uid, path3.uid, path4.uid];
            expect(uids.length).toBe(_.uniq(uids).length);

            var path1s = new Path(this.node250250.connections[directions.LEFT_TO_RIGHT.id], this.node250250, null);
            var path2s = new Path(this.node250250.connections[directions.RIGHT_TO_LEFT.id], this.node250250, null);
            var path3s = new Path(this.node250250.connections[directions.TOP_TO_BOTTOM.id], this.node250250, null);
            var path4s = new Path(this.node250250.connections[directions.BOTTOM_TO_TOP.id], this.node250250, null);
            var uidsS = [path1s.uid, path2s.uid, path3s.uid, path4s.uid];

            expect(uids).toEqual(uidsS);
        });

        it('should calculate if it can be joined with another path', function() {
            var path1 = new Path(this.node250250.connections[directions.LEFT_TO_RIGHT.id], this.node250250, null);
            var path2 = new Path(this.node250250.connections[directions.RIGHT_TO_LEFT.id], this.node250250, null);
            expect(path1.canJoinWith(path2)).toBeFalsy();
            expect(path2.canJoinWith(path1)).toBeFalsy();

            var path3 = new Path(this.node150250.connections[directions.LEFT_TO_RIGHT.id], this.node150250, null);
            expect(path1.canJoinWith(path3)).toBeFalsy();
            expect(path2.canJoinWith(path3)).toBeTruthy();
            expect(path3.canJoinWith(path2)).toBeTruthy();
        });

        it('should calculate allConnections', function() {
            var path = new Path(this.node100100.connections[directions.LEFT_TO_RIGHT.id], this.node100100, null);
            expect(path.allConnections.length).toBe(1);
            expect(path.allConnections[0] === this.node100100.connections[directions.LEFT_TO_RIGHT.id]).toBeTruthy();

            var nextPath = new Path(this.node150100.connections[directions.LEFT_TO_RIGHT.id], this.node150100, path);
            expect(nextPath.allConnections.length).toBe(2);
            expect(nextPath.allConnections[0] === this.node100100.connections[directions.LEFT_TO_RIGHT.id])
                .toBeTruthy();
            expect(nextPath.allConnections[1] === this.node150100.connections[directions.LEFT_TO_RIGHT.id])
                .toBeTruthy();
        });

        it('should calculate includedConnections', function() {
            var path = new Path(this.node100100.connections[directions.LEFT_TO_RIGHT.id], this.node100100, null);
            var nextPath = new Path(this.node150100.connections[directions.LEFT_TO_RIGHT.id], this.node150100, path);
            this.node150100.vAxis.ensureTraversableSiblings();
            expect(nextPath.allConnections.length).toBe(4);
            expect(nextPath.includedConnections.length).toBe(2);
        });

        it('should return all nodes included into it', function() {
            var path = new Path(this.node100100.connections[directions.LEFT_TO_RIGHT.id], this.node100100, null);
            expect(path.allNodes).toEqual([
                this.node100100,
                this.node150100
            ]);
            var nextPath = new Path(this.node150100.connections[directions.LEFT_TO_RIGHT.id], this.node150100, path);
            expect(nextPath.allNodes).toEqual([
                this.node100100,
                this.node150100,
                this.graph.getNodeAt(new Point2d(250, 100))
            ]);
        });

        it('should return sibling paths', function() {
            var path = new Path(this.node100100.connections[directions.LEFT_TO_RIGHT.id], this.node100100, null);
            expect(path.getSiblings().length).toBe(1);
            expect(path.getSiblings()[0]).toBe(path);

            this.node100100.vAxis.ensureTraversableSiblings();
            expect(path.getSiblings().length).toBe(1);
            expect(path.getSiblings()[0]).toBe(path);

            this.node100100.hAxis.ensureTraversableSiblings();
            expect(path.getSiblings().length).toBe(3);
            expect(path.getSiblings()[1]).toBe(path);
        });
    });
});
