define(function(require) {
    'use strict';

    require('jasmine-jquery');
    var $ = require('jquery');
    var Point2d = require('oroworkflow/js/tools/path-finder/point2d');
    var Interval2d = require('oroworkflow/js/tools/path-finder/interval2d');
    var Rectangle = require('oroworkflow/js/tools/path-finder/rectangle');
    var Interval1d = require('oroworkflow/js/tools/path-finder/interval1d');

    describe('oroworkflow/js/tools/path-finder/rectangle', function() {
        afterEach(function() {
            $('svg').remove();
        });

        it('should correct initialize', function() {
            var rectangle1 = new Rectangle(10, 20, 30, 40);
            var rectangle2 = new Rectangle(new Interval1d(10, 40), new Interval1d(20, 60));
            expect(rectangle1.horizontalInterval instanceof Interval1d).toBe(true);
            expect(rectangle1.verticalInterval instanceof Interval1d).toBe(true);
            expect(rectangle2.horizontalInterval instanceof Interval1d).toBe(true);
            expect(rectangle2.verticalInterval instanceof Interval1d).toBe(true);
        });

        it('should store correct dimensions', function() {
            var rectangle = new Rectangle(10, 20, 30, 40);
            expect(rectangle.left).toBe(10);
            expect(rectangle.right).toBe(40);
            expect(rectangle.top).toBe(20);
            expect(rectangle.bottom).toBe(60);
            expect(rectangle.width).toBe(30);
            expect(rectangle.height).toBe(40);
            expect(rectangle.center.x).toBe(25);
            expect(rectangle.center.y).toBe(40);
            rectangle.left = 15;
            rectangle.width = 100;
            expect(rectangle.right).toBe(115);
            rectangle.right = 20;
            expect(rectangle.width).toBe(5);
            rectangle.top = 35;
            rectangle.height = 45;
            expect(rectangle.bottom).toBe(80);
            rectangle.bottom = 40;
            expect(rectangle.height).toBe(5);
        });

        it('should correct clone', function() {
            var rectangle1 = new Rectangle(10, 20, 30, 40);
            var rectangle2 = rectangle1.clone();
            expect(rectangle1).not.toBe(rectangle2);
            expect(rectangle1.left).toBe(rectangle2.left);
            expect(rectangle1.right).toBe(rectangle2.right);
            expect(rectangle1.top).toBe(rectangle2.top);
            expect(rectangle1.bottom).toBe(rectangle2.bottom);
        });

        it('should calculate correct intersection', function() {
            var rectangle1 = new Rectangle(10, 20, 30, 40);
            var rectangle2 = new Rectangle(20, 30, 30, 40);
            var rectangle3 = rectangle1.intersection(rectangle2);
            var rectangle4 = new Rectangle(120, 130, 30, 40);
            expect(rectangle3.left).toBe(20);
            expect(rectangle3.right).toBe(40);
            expect(rectangle3.top).toBe(30);
            expect(rectangle3.bottom).toBe(60);
            expect(rectangle1.intersection(rectangle4)).toBe(null);
        });

        it('should calculate correct uion', function() {
            var rectangle1 = new Rectangle(10, 20, 30, 40);
            var rectangle2 = new Rectangle(20, 30, 30, 40);
            var rectangle3 = rectangle1.union(rectangle2);
            expect(rectangle3.left).toBe(10);
            expect(rectangle3.right).toBe(50);
            expect(rectangle3.top).toBe(20);
            expect(rectangle3.bottom).toBe(70);
        });

        it('should correct validate itself', function() {
            var rectangle1 = new Rectangle(10, 20, 30, 40);
            expect(rectangle1 instanceof Rectangle).toBe(true);
            expect(function() { return new Rectangle(20, 30, -30, 40); }).toThrow(jasmine.any(RangeError));
            expect(function() { return new Rectangle(20, 30, 30, -40); }).toThrow(jasmine.any(RangeError));
            expect(function() { return new Rectangle(20, 30, -30, -40); }).toThrow(jasmine.any(RangeError));
        });

        it('should correct calculate its sides', function() {
            var rectangle = new Rectangle(10, 20, 30, 40);
            var topSide = rectangle.topSide;
            var bottomSide = rectangle.bottomSide;
            var leftSide = rectangle.leftSide;
            var rightSide = rectangle.rightSide;
            expect(topSide.a.x).toBe(10);
            expect(topSide.a.y).toBe(20);
            expect(topSide.b.x).toBe(40);
            expect(topSide.b.y).toBe(20);
            expect(bottomSide.a.x).toBe(10);
            expect(bottomSide.a.y).toBe(60);
            expect(bottomSide.b.x).toBe(40);
            expect(bottomSide.b.y).toBe(60);
            expect(leftSide.a.x).toBe(10);
            expect(leftSide.a.y).toBe(20);
            expect(leftSide.b.x).toBe(10);
            expect(leftSide.b.y).toBe(60);
            expect(rightSide.a.x).toBe(40);
            expect(rightSide.a.y).toBe(20);
            expect(rightSide.b.x).toBe(40);
            expect(rightSide.b.y).toBe(60);
        });

        it('should correct detect contained point', function() {
            var rectangle = new Rectangle(10, 20, 30, 40);
            var point1 = new Point2d(5, 5);
            var point2 = new Point2d(15, 55);
            expect(rectangle.containsPoint(point1)).toBe(false);
            expect(rectangle.containsPoint(point2)).toBe(true);
        });

        it('should correct iterate through its sides', function() {
            var rectangle = new Rectangle(10, 20, 30, 40);
            var callback = jasmine.createSpy('callback');
            rectangle.eachSide(callback);
            expect(callback.calls.count()).toBe(4);
            expect(callback).toHaveBeenCalledWith(jasmine.any(Interval2d));
        });

        it('check point draw', function() {
            var rectangle = new Rectangle(10, 20, 30, 40);
            rectangle.draw('blue');
            expect(document.body).toContainElement('path[d="M 10 20 L 40 20"][stroke=blue]');
            expect(document.body).toContainElement('path[d="M 10 20 L 10 60"][stroke=blue]');
            expect(document.body).toContainElement('path[d="M 10 60 L 40 60"][stroke=blue]');
            expect(document.body).toContainElement('path[d="M 40 20 L 40 60"][stroke=blue]');
        });
    });
});

