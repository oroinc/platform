define(function(require) {
    'use strict';

    const Point2d = require('oroworkflow/js/tools/path-finder/point2d');
    const Interval2d = require('oroworkflow/js/tools/path-finder/interval2d');
    const Line2d = require('oroworkflow/js/tools/path-finder/line2d');
    const Rectangle = require('oroworkflow/js/tools/path-finder/rectangle');

    describe('oroworkflow/js/tools/path-finder/interval2d', function() {
        it('interval initialization', function() {
            const a = new Point2d(10, 15);
            const b = new Point2d(40, 45);
            const interval = new Interval2d(a, b);
            expect(interval.a).toEqual(a);
            expect(interval.b).toEqual(b);
        });

        it('check length calculation', function() {
            const a = new Point2d(10, 15);
            const b = new Point2d(40, 45);
            const interval1 = new Interval2d(a, b);
            const c = new Point2d(30, 15);
            const d = new Point2d(10, 20);
            const interval2 = new Interval2d(c, d);
            expect(Math.round(interval1.length)).toBe(42);
            expect(Math.round(interval2.length)).toBe(21);
        });

        it('check of simple path length calculation', function() {
            const a = new Point2d(10, 15);
            const b = new Point2d(40, 45);
            const interval1 = new Interval2d(a, b);
            const c = new Point2d(30, 15);
            const d = new Point2d(10, 20);
            const interval2 = new Interval2d(c, d);
            expect(Math.round(interval1.simpleLength)).toBe(60);
            expect(Math.round(interval2.simpleLength)).toBe(25);
        });

        it('check of crossing of 2 intervals', function() {
            const a = new Point2d(20, 10);
            const b = new Point2d(90, 10);
            const interval1 = new Interval2d(a, b);
            const c = new Point2d(60, 0);
            const d = new Point2d(60, 80);
            const interval2 = new Interval2d(c, d);
            const e = new Point2d(50, 15);
            const f = new Point2d(50, 170);
            const interval3 = new Interval2d(e, f);
            expect(interval1.crosses(interval2)).toBe(true);
            expect(interval1.crosses(interval3)).toBe(false);
        });

        it('check of crossing point of 2 intervals', function() {
            const a = new Point2d(20, 10);
            const b = new Point2d(90, 10);
            const interval1 = new Interval2d(a, b);
            const c = new Point2d(60, 0);
            const d = new Point2d(60, 80);
            const interval2 = new Interval2d(c, d);
            const e = new Point2d(50, 15);
            const f = new Point2d(50, 170);
            const interval3 = new Interval2d(e, f);
            const crossPoint = interval1.getCrossPoint(interval2);
            expect(crossPoint.x).toBe(60);
            expect(crossPoint.y).toBe(10);
            expect(interval1.getCrossPoint(interval3)).toBe(null);
        });

        it('check if point belong to the interval', function() {
            const a = new Point2d(20, 20);
            const b = new Point2d(90, 90);
            const c = new Point2d(30, 30);
            const d = new Point2d(42, 46);
            const interval = new Interval2d(a, b);
            expect(interval.includesPoint(c)).toBe(true);
            expect(interval.includesPoint(d)).toBe(false);
        });

        it('check of crossing interval of rect', function() {
            const a = new Point2d(20, 50);
            const b = new Point2d(90, 50);
            const interval = new Interval2d(a, b);
            const rect1 = new Rectangle(15, 40, 20, 20);
            const rect2 = new Rectangle(30, 80, 100, 25);
            expect(interval.crossesRect(rect1)).toBe(true);
            expect(interval.crossesRect(rect2)).toBe(false);
        });

        it('check line calculate', function() {
            const a = new Point2d(30, 30);
            const b = new Point2d(70, 60);
            const interval = new Interval2d(a, b);
            const line1 = interval.line;
            const line2 = new Line2d(Infinity, 110);
            const point = line1.intersection(line2);
            expect(point instanceof Point2d).toBe(true);
            expect(point.x).toBe(110);
            expect(point.y).toBe(90);
        });
    });
});

