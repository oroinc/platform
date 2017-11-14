define(function(require) {
    'use strict';

    require('jasmine-jquery');
    var Point2d = require('oroworkflow/js/tools/path-finder/point2d');
    var NodePoint = require('oroworkflow/js/tools/path-finder/node-point');
    var Interval2d = require('oroworkflow/js/tools/path-finder/interval2d');
    var Connection = require('oroworkflow/js/tools/path-finder/connection');
    var settings = require('oroworkflow/js/tools/path-finder/settings');

    describe('oroworkflow/js/tools/path-finder/connection', function() {
        beforeEach(function() {
            window.setFixtures('<div class="workflow-flowchart-editor" />');

            this.graph = jasmine.createSpyObj('graph', ['isConnectionUnderRect']);
            this.graph.isConnectionUnderRect.and.returnValue(false);
            this.direction = new Point2d(1, 0);

            var axis = {
                costMultiplier: 0.5,
                graph: this.graph
            };

            this.a = new NodePoint(10, 20);
            this.a.vAxis = axis;

            this.b = new NodePoint(40, 20);
            this.b.vAxis = axis;

            this.connection = new Connection(this.a, this.b, this.direction);
        });

        it('check base connection creation', function() {
            expect(this.connection instanceof Interval2d).toBe(true);
            expect(this.connection.costMultiplier).toBe(1);
            expect(this.connection.traversable).toBe(true);
            expect(this.connection.vector).toBe(this.direction);
            expect(this.connection.uid).toBeDefined();
            expect(this.a.connections[this.direction.id]).toBe(this.connection);
            expect(this.b.connections[this.direction.rot180().id]).toBe(this.connection);
        });

        it('check cross step connection creation', function() {
            this.graph.isConnectionUnderRect.and.returnValue(true);
            var connection = new Connection(this.a, this.b, this.direction);
            expect(connection.costMultiplier).toBe(settings.overBlockLineCostMultiplier);
        });

        it('check connection creation with no defined direction', function() {
            var connection = new Connection(this.a, this.b);
            var vector = this.b.sub(this.a).unitVector;
            expect(connection.vector.id).toBe(vector.id);
        });

        describe('check connection cost calculation', function() {
            it('base cost', function() {
                expect(this.connection.cost).toBe(15);
            });

            it('cross path cost', function() {
                this.a.used = true;
                expect(this.connection.cost).toBe(15 + settings.crossPathCost);
            });

            it('cross block cost', function() {
                this.graph.isConnectionUnderRect.and.returnValue(true);
                var connection = new Connection(this.a, this.b, this.direction);
                expect(connection.cost).toBe(15 * settings.overBlockLineCostMultiplier);
            });

            it('cross block and cross path cost', function() {
                this.a.used = true;
                this.graph.isConnectionUnderRect.and.returnValue(true);
                var connection = new Connection(this.a, this.b, this.direction);
                expect(connection.cost).toBe(15 * settings.overBlockLineCostMultiplier + settings.crossPathCost);
            });
        });

        it('check connection axis', function() {
            expect(this.connection.axis).toBe(this.a.vAxis);
            this.a.vAxis = null;
            expect(this.connection.axis).not.toBeDefined();
        });

        it('check connection remove method', function() {
            var aConnections = {};
            var bConnections = {};
            aConnections[this.direction.id] = null;
            bConnections[this.direction.rot180().id] = null;

            this.connection.remove();

            expect(this.a.connections).toEqual(aConnections);
            expect(this.b.connections).toEqual(bConnections);
        });

        it('check connection second method', function() {
            expect(this.connection.second(this.a)).toBe(this.b);
            expect(this.connection.second(this.b)).toBe(this.a);
            expect(this.connection.second(null)).not.toBeDefined();
        });

        it('check connection directionFrom method', function() {
            expect(this.connection.directionFrom(this.a)).toBe(this.direction);
            expect(this.connection.directionFrom(this.b).id).toBe(this.direction.rot180().id);
            expect(this.connection.directionFrom(null)).not.toBeDefined();
        });

        it('check connection draw method', function() {
            this.connection.draw();
            expect(document.body)
                .toContainElement('svg[style^="top: 20px; left: 10px;"]>path[stroke=green][d="M 30 0 L 0 0"]');

            this.connection.draw('blue');
            expect(document.body)
                .toContainElement('svg[style^="top: 20px; left: 10px;"]>path[stroke=blue][d="M 30 0 L 0 0"]');
        });
    });
});
