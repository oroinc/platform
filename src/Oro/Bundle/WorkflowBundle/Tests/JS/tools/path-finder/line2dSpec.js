define(function(require) {
    'use strict';

    var Line2d = require('oroworkflow/js/tools/path-finder/line2d');
    var Point2d = require('oroworkflow/js/tools/path-finder/point2d');

    describe('oroworkflow/js/tools/path-finder/line2d', function() {
        it('check line creation', function() {
            var a = new Line2d(3, 8);
            expect(a.slope).toBe(3);
            expect(a.intercept).toBe(8);
        });

        describe('check line intersection', function() {
            it('with finite slope', function() {
                var a = new Line2d(4, 8);
                var b = new Line2d(9, 4);
                var point = a.intersection(b);
                expect(point instanceof Point2d).toBe(true);
                expect(point.x).toBe(0.8);
                expect(point.y).toBe(11.2);
            });

            it('with infinite slope', function() {
                var a = new Line2d(Infinity, 8);
                var b = new Line2d(Infinity, 4);
                var point = a.intersection(b);
                expect(point.x).toEqual(NaN);
                expect(point.y).toEqual(NaN);

                a = new Line2d(Infinity, 8);
                b = new Line2d(5, 4);
                point = a.intersection(b);
                expect(point.x).toBe(8);
                expect(point.y).toBe(44);

                a = new Line2d(2, 8);
                b = new Line2d(Infinity, 4);
                point = a.intersection(b);
                expect(point.x).toBe(4);
                expect(point.y).toBe(16);
            });
        });
    });
});
