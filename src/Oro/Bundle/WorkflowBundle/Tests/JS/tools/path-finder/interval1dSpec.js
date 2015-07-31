define(function(require) {
    'use strict';

    var Interval1d = require('oroworkflow/js/tools/path-finder/interval1d');

    describe('oroworkflow/js/tools/path-finder/interval1d', function() {
        it('interval initialization', function() {
            var a = new Interval1d(5, 7);
            expect(a.min).toEqual(5);
            expect(a.max).toEqual(7);
        });

        it('check width calculation', function() {
            var a = new Interval1d(5, 7);
            expect(a.width).toEqual(2);
        });

        it('check validation', function() {
            var a = new Interval1d(10, 4);
            var b = new Interval1d(4, 10);
            expect(a.isValid).toEqual(false);
            expect(b.isValid).toEqual(true);
        });

        it('check intersections', function() {
            var a = new Interval1d(5, 15);
            var b = new Interval1d(2, 8);
            var c = new Interval1d(30, 40);
            var intersection = a.intersection(b);
            expect(intersection.min).toEqual(5);
            expect(intersection.max).toEqual(8);
            expect(a.intersection(c).isValid).toEqual(false);
        });

        it('check union', function() {
            var a = new Interval1d(5, 15);
            var b = new Interval1d(2, 8);
            var c = new Interval1d(30, 40);
            var union = a.union(b);
            expect(union.min).toEqual(2);
            expect(union.max).toEqual(15);
            expect(union.isValid).toEqual(true);
        });

        it('check contains', function() {
            var a = new Interval1d(2, 12);
            expect(a.contains(1)).toEqual(false);
            expect(a.contains(2)).toEqual(true);
            expect(a.contains(6)).toEqual(true);
            expect(a.contains(12)).toEqual(true);
            expect(a.contains(17)).toEqual(false);
        });

        it('check containsNonInclusive', function() {
            var a = new Interval1d(2, 12);
            expect(a.containsNonInclusive(1)).toEqual(false);
            expect(a.containsNonInclusive(2)).toEqual(false);
            expect(a.containsNonInclusive(6)).toEqual(true);
            expect(a.containsNonInclusive(12)).toEqual(false);
            expect(a.containsNonInclusive(17)).toEqual(false);
        });

        it('check calculate distance', function() {
            var a = new Interval1d(9, 12);
            expect(a.distanceTo(3)).toEqual(6);
            expect(a.distanceTo(10)).toEqual(0);
            expect(a.distanceTo(23)).toEqual(11);
        });
    });
});

