define(function(require) {
    'use strict';

    require('jasmine-jquery');
    const Vector2d = require('oroworkflow/js/tools/path-finder/vector2d');
    const Point2d = require('oroworkflow/js/tools/path-finder/point2d');
    const Line2d = require('oroworkflow/js/tools/path-finder/line2d');
    const Rectangle = require('oroworkflow/js/tools/path-finder/rectangle');
    const Interval2d = require('oroworkflow/js/tools/path-finder/interval2d');

    describe('oroworkflow/js/tools/path-finder/vector2d', function() {
        beforeEach(function() {
            window.setFixtures('<div class="workflow-flowchart-editor" />');
        });

        it('check vector creation', function() {
            const direction = new Point2d(-1, 0);
            const vector = new Vector2d(3, 8, direction);
            expect(vector.start instanceof Point2d).toBe(true);
            expect(vector.start.x).toBe(3);
            expect(vector.start.y).toBe(8);
            expect(vector.direction).toBe(direction);
        });

        it('check vector line', function() {
            let direction = new Point2d(-1, 0);
            let vector = new Vector2d(3, 8, direction);
            let line = vector.line;
            expect(line instanceof Line2d).toBe(true);
            expect(line.slope).toBe(0);
            expect(line.intercept).toBe(8);

            direction = new Point2d(0, 1);
            vector = new Vector2d(3, 8, direction);
            line = vector.line;
            expect(line.slope).toEqual(Infinity);
            expect(line.intercept).toBe(3);
        });

        it('check vector crosses', function() {
            const direction = new Point2d(1, 0);
            const vector = new Vector2d(3, 8, direction);
            let rectangle = new Rectangle(15, 15, 30, 30);
            expect(vector.crosses(rectangle)).toBe(false);

            rectangle = new Rectangle(15, 5, 30, 30);
            expect(vector.crosses(rectangle)).toBe(true);
        });

        it('check vector getCrossPointWithRect', function() {
            const rectangle = new Rectangle(15, 15, 30, 30);
            let direction = new Point2d(1, 0);
            let vector = new Vector2d(10, 30, direction);
            let point = vector.getCrossPointWithRect(rectangle);
            expect(point.x).toBe(15);
            expect(point.y).toBe(30);

            direction = new Point2d(-1, 0);
            vector = new Vector2d(50, 30, direction);
            point = vector.getCrossPointWithRect(rectangle);
            expect(point.x).toBe(45);
            expect(point.y).toBe(30);

            direction = new Point2d(0, 1);
            vector = new Vector2d(30, 10, direction);
            point = vector.getCrossPointWithRect(rectangle);
            expect(point.x).toBe(30);
            expect(point.y).toBe(15);

            direction = new Point2d(0, -1);
            vector = new Vector2d(30, 50, direction);
            point = vector.getCrossPointWithRect(rectangle);
            expect(point.x).toBe(30);
            expect(point.y).toBe(45);

            direction = new Point2d(1, 0);
            vector = new Vector2d(10, 10, direction);
            point = vector.getCrossPointWithRect(rectangle);
            expect(point).toBe(null);
        });

        it('check vector getCrossPointWithInterval', function() {
            let interval = new Interval2d(new Point2d(15, 15), new Point2d(15, 45));
            let direction = new Point2d(1, 0);
            let vector = new Vector2d(10, 10, direction);
            let point = vector.getCrossPointWithInterval(interval);
            expect(point).toBe(null);

            vector = new Vector2d(10, 30, direction);
            point = vector.getCrossPointWithInterval(interval);
            expect(point.x).toBe(15);
            expect(point.y).toBe(30);

            // sloping interval
            interval = new Interval2d(new Point2d(15, 15), new Point2d(45, 45));
            point = vector.getCrossPointWithInterval(interval);
            expect(point.x).toBe(30);
            expect(point.y).toBe(30);

            // sloping vector
            direction = new Point2d(1, -1);
            vector = new Vector2d(15, 45, direction);
            point = vector.getCrossPointWithInterval(interval);
            expect(point.x).toBe(30);
            expect(point.y).toBe(30);
        });

        it('check vector draw', function() {
            const direction = new Point2d(0, -1);
            const vector = new Vector2d(56, 67, direction);
            vector.draw('green');
            expect(document.body)
                .toContainElement('svg[style^="top: -99933px; left: 56px;"]>path[stroke=green][d="M 0 0 L 0 100000"]');
        });
    });
});
