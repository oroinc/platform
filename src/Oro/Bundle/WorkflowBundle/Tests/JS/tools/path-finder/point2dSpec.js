define(function(require) {
    'use strict';

    require('jasmine-jquery');
    var $ = require('jquery');
    var Point2d = require('oroworkflow/js/tools/path-finder/point2d');

    describe('oroworkflow/js/tools/path-finder/point2d', function() {
        afterEach(function() {
            $('svg').remove();
        });

        it('point initialization', function() {
            var a = new Point2d(5, 7);
            expect(a.x).toEqual(5);
            expect(a.y).toEqual(7);
            expect(a.id).toEqual(1000007);
            expect(a.uid).toBeDefined();
        });

        it('check distance calculation', function() {
            var a = new Point2d(1, -3);
            var b = new Point2d(-2, 1);
            expect(a.simpleDistanceTo(b)).toEqual(7);
            expect(a.distanceTo(b)).toEqual(5);
        });

        it('check point inversion', function() {
            var a = new Point2d(1, -3);
            var b = a.opposite();
            expect(b.x).toEqual(-1);
            expect(b.y).toEqual(3);
        });

        it('check point addition', function() {
            var a = new Point2d(1, -3);
            var b = new Point2d(-2, 1);
            var c = a.add(b);
            expect(c.x).toEqual(-1);
            expect(c.y).toEqual(-2);
        });

        it('check point subtraction', function() {
            var a = new Point2d(1, -3);
            var b = new Point2d(-2, 1);
            var c = a.sub(b);
            expect(c.x).toEqual(3);
            expect(c.y).toEqual(-4);
        });

        it('check point multiplication', function() {
            var a = new Point2d(1, -3);
            var c = a.mul(2);
            expect(c.x).toEqual(2);
            expect(c.y).toEqual(-6);
        });

        it('check point length', function() {
            var a = new Point2d(3, -4);
            expect(a.length).toEqual(5);
        });

        it('check point unitVector', function() {
            var a = new Point2d(3, -4);
            var b = a.unitVector;
            expect(b.x).toEqual(0.6);
            expect(b.y).toEqual(-0.8);
        });

        it('check point rotation', function() {
            var a = new Point2d(3, -4);
            var b = a.rot90();
            expect(b.x).toEqual(-4);
            expect(b.y).toEqual(-3);

            b = a.rot180();
            expect(b.x).toEqual(-3);
            expect(b.y).toEqual(4);

            b = a.rot270();
            expect(b.x).toEqual(4);
            expect(b.y).toEqual(3);
        });

        it('check point draw', function() {
            var a = new Point2d(2, 7);
            a.draw();
            expect(document.body).toContainElement('circle[fill=red][r=2][cx=2][cy=7]');

            a.draw('blue', 7);
            expect(document.body).toContainElement('circle[fill=blue][r=7][cx=2][cy=7]');

            a.drawText('test');
            expect(document.body).toContainElement('text[x=7][y=2][fill=black]');
            expect($('text[x=7][y=2][fill=black]')).toContainText('test');

            a.drawText('test1', 'green');
            expect(document.body).toContainElement('text[x=7][y=2][fill=green]');
            expect($('text[x=7][y=2][fill=green]')).toContainText('test1');
        });

        it('check point abs', function() {
            var a = new Point2d(3, -4);
            var b = a.abs();
            expect(b.x).toEqual(3);
            expect(b.y).toEqual(4);
        });

        it('check point clone', function() {
            var a = new Point2d(3, -4);
            var b = a.clone();
            expect(b).not.toBe(a);
            expect(b.x).toEqual(a.x);
            expect(b.y).toEqual(a.y);
            expect(b.uid).not.toEqual(a.uid);
        });
    });
});
