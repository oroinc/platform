define(function(require) {
    'use strict';

    require('jasmine-jquery');
    var Point2d = require('oroworkflow/js/tools/path-finder/point2d');
    var NodePoint = require('oroworkflow/js/tools/path-finder/node-point');
    var Connection = require('oroworkflow/js/tools/path-finder/connection');

    describe('oroworkflow/js/tools/path-finder/node-point', function() {
        beforeEach(function() {
            window.setFixtures('<div class="workflow-flowchart-editor" />');

            this.a = new NodePoint(5.5, 7.5);
            var graph = jasmine.createSpyObj('graph', ['isConnectionUnderRect']);
            this.a.vAxis = {
                recommendedPosition: 5,
                graph: graph
            };
            this.a.hAxis = {
                recommendedPosition: 7,
                graph: graph
            };
        });

        it('check node-point creation', function() {
            var a = new NodePoint(5, 7);
            expect(a.connections).toEqual({});
            expect(a.stale).toBe(false);
            expect(a.used).toBe(false);
            expect(a instanceof Point2d).toBe(true);
        });

        it('check recommendedX', function() {
            expect(this.a.recommendedX).toBe(5);

            this.a.vAxis = {};
            expect(this.a.recommendedX).toBe(5.5);
        });

        it('check recommendedY', function() {
            expect(this.a.recommendedY).toBe(7);

            this.a.hAxis = {};
            expect(this.a.recommendedY).toBe(7.5);
        });

        it('check recommendedPoint', function() {
            var a1 = this.a.recommendedPoint;
            expect(a1.x).toBe(5);
            expect(a1.y).toBe(7);
        });

        describe('connections', function() {
            beforeEach(function() {
                this.b = new NodePoint(40, 7);
                this.direction = new Point2d(1, 0);
                this.a.connect(this.direction, this.b);
                this.connection = this.a.connections[this.direction.id];
            });

            it('check connect method', function() {
                var connection = this.a.connections[this.direction.id];
                expect(connection).toBeDefined();
                expect(connection instanceof Connection).toBe(true);
            });

            it('check eachConnection method', function() {
                var callback = jasmine.createSpy('spy');
                var c = new NodePoint(5, 70);
                var direction = new Point2d(0, 1);
                this.a.connect(direction, c);

                this.a.eachConnection(callback);

                expect(callback.calls.count()).toBe(2);
                expect(callback).toHaveBeenCalledWith(jasmine.any(Connection));
            });

            it('check eachTraversableConnection method', function() {
                var direction = new Point2d(0, -1);
                var c = new NodePoint(5, 70);
                c.vAxis = c.hAxis = {
                    graph: jasmine.createSpyObj('graph', ['isConnectionUnderRect'])
                };
                c.connect(direction, this.a);

                var connectionFrom = c.connections[direction.id];
                var callback = jasmine.createSpy('spy');
                this.a.eachTraversableConnection(connectionFrom, callback);

                expect(callback.calls.count()).toBe(1);
                expect(callback).toHaveBeenCalledWith(this.b, jasmine.any(Connection));
            });

            it('check nextNode method', function() {
                var b = this.a.nextNode(this.direction);
                expect(b).toBe(this.b);

                b = this.a.nextNode(new Point2d(-1, -1));
                expect(b).toBe(null);
            });
        });

        it('check clone method', function() {
            var b = this.a.clone();
            expect(b).not.toBe(this.a);
            expect(b instanceof NodePoint).toBe(true);
            expect(b.vAxis).toEqual(this.a.vAxis);
            expect(b.hAxis).toEqual(this.a.hAxis);
        });

        it('check draw method', function() {
            this.a.draw('yellow', 3);
            expect(document.body)
                .toContainElement('svg[style^="top: 4px; left: 2px;"]>circle[fill=yellow][r=3][cx=3][cy=3]');
        });
    });
});
