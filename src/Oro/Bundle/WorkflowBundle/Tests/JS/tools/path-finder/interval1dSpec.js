define(function(require) {
    'use strict';

    var Interval1d = require('oroworkflow/js/tools/path-finder/interval1d');

    describe('oroworkflow/js/tools/path-finder/interval1d', function() {
        it('interval initialization', function() {
            var a = new Interval1d(5, 7);
            expect(a.min).toBe(5);
            expect(a.max).toBe(7);
            expect(function() {
                return new Interval1d(10, 7);
            }).toThrow(jasmine.any(RangeError));
        });

        it('check width calculation', function() {
            var a = new Interval1d(5, 7);
            expect(a.width).toBe(2);
            a.width = 10;
            expect(a.max).toBe(15);
        });

        it('check intersections', function() {
            var a = new Interval1d(5, 15);
            var b = new Interval1d(2, 8);
            var c = new Interval1d(30, 40);
            var intersection = a.intersection(b);
            expect(intersection instanceof Interval1d).toBe(true);
            expect(intersection.min).toBe(5);
            expect(intersection.max).toBe(8);
            expect(a.intersection(c)).toBe(null);
        });

        it('check union', function() {
            var a = new Interval1d(5, 15);
            var b = new Interval1d(2, 8);
            var union = a.union(b);
            expect(union instanceof Interval1d).toBe(true);
            expect(union.min).toBe(2);
            expect(union.max).toBe(15);
        });

        it('check contains', function() {
            var a = new Interval1d(2, 12);
            expect(a.contains(1)).toBe(false);
            expect(a.contains(2)).toBe(true);
            expect(a.contains(6)).toBe(true);
            expect(a.contains(12)).toBe(true);
            expect(a.contains(17)).toBe(false);
        });

        it('check containsNonInclusive', function() {
            var a = new Interval1d(2, 12);
            expect(a.containsNonInclusive(1)).toBe(false);
            expect(a.containsNonInclusive(2)).toBe(false);
            expect(a.containsNonInclusive(6)).toBe(true);
            expect(a.containsNonInclusive(12)).toBe(false);
            expect(a.containsNonInclusive(17)).toBe(false);
        });

        it('check calculate distance', function() {
            var a = new Interval1d(9, 12);
            expect(a.distanceTo(3)).toBe(6);
            expect(a.distanceTo(10)).toBe(0);
            expect(a.distanceTo(23)).toBe(11);
        });
    });
});

