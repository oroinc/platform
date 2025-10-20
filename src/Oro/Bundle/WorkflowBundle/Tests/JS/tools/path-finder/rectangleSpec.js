import 'jasmine-jquery';
import Point2d from 'oroworkflow/js/tools/path-finder/point2d';
import Interval2d from 'oroworkflow/js/tools/path-finder/interval2d';
import Rectangle from 'oroworkflow/js/tools/path-finder/rectangle';
import Interval1d from 'oroworkflow/js/tools/path-finder/interval1d';

describe('oroworkflow/js/tools/path-finder/rectangle', function() {
    beforeEach(function() {
        window.setFixtures('<div class="workflow-flowchart-editor" />');
    });

    it('should correct initialize', function() {
        const rectangle1 = new Rectangle(10, 20, 30, 40);
        const rectangle2 = new Rectangle(new Interval1d(10, 40), new Interval1d(20, 60));
        expect(rectangle1.horizontalInterval instanceof Interval1d).toBe(true);
        expect(rectangle1.verticalInterval instanceof Interval1d).toBe(true);
        expect(rectangle2.horizontalInterval instanceof Interval1d).toBe(true);
        expect(rectangle2.verticalInterval instanceof Interval1d).toBe(true);
    });

    it('should store correct dimensions', function() {
        const rectangle = new Rectangle(10, 20, 30, 40);
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
        const rectangle1 = new Rectangle(10, 20, 30, 40);
        const rectangle2 = rectangle1.clone();
        expect(rectangle1).not.toBe(rectangle2);
        expect(rectangle1.left).toBe(rectangle2.left);
        expect(rectangle1.right).toBe(rectangle2.right);
        expect(rectangle1.top).toBe(rectangle2.top);
        expect(rectangle1.bottom).toBe(rectangle2.bottom);
    });

    it('should calculate correct intersection', function() {
        const rectangle1 = new Rectangle(10, 20, 30, 40);
        const rectangle2 = new Rectangle(20, 30, 30, 40);
        const rectangle3 = rectangle1.intersection(rectangle2);
        const rectangle4 = new Rectangle(120, 130, 30, 40);
        expect(rectangle3.left).toBe(20);
        expect(rectangle3.right).toBe(40);
        expect(rectangle3.top).toBe(30);
        expect(rectangle3.bottom).toBe(60);
        expect(rectangle1.intersection(rectangle4)).toBe(null);
    });

    it('should calculate correct uion', function() {
        const rectangle1 = new Rectangle(10, 20, 30, 40);
        const rectangle2 = new Rectangle(20, 30, 30, 40);
        const rectangle3 = rectangle1.union(rectangle2);
        expect(rectangle3.left).toBe(10);
        expect(rectangle3.right).toBe(50);
        expect(rectangle3.top).toBe(20);
        expect(rectangle3.bottom).toBe(70);
    });

    it('should correct validate itself', function() {
        const rectangle1 = new Rectangle(10, 20, 30, 40);
        expect(rectangle1 instanceof Rectangle).toBe(true);
        expect(function() {
            return new Rectangle(20, 30, -30, 40);
        }).toThrow(jasmine.any(RangeError));
        expect(function() {
            return new Rectangle(20, 30, 30, -40);
        }).toThrow(jasmine.any(RangeError));
        expect(function() {
            return new Rectangle(20, 30, -30, -40);
        }).toThrow(jasmine.any(RangeError));
    });

    it('should correct calculate its sides', function() {
        const rectangle = new Rectangle(10, 20, 30, 40);
        const topSide = rectangle.topSide;
        const bottomSide = rectangle.bottomSide;
        const leftSide = rectangle.leftSide;
        const rightSide = rectangle.rightSide;
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
        const rectangle = new Rectangle(10, 20, 30, 40);
        const point1 = new Point2d(5, 5);
        const point2 = new Point2d(15, 55);
        expect(rectangle.containsPoint(point1)).toBe(false);
        expect(rectangle.containsPoint(point2)).toBe(true);
    });

    it('should correct iterate through its sides', function() {
        const rectangle = new Rectangle(10, 20, 30, 40);
        const callback = jasmine.createSpy('callback');
        rectangle.eachSide(callback);
        expect(callback.calls.count()).toBe(4);
        expect(callback).toHaveBeenCalledWith(jasmine.any(Interval2d));
    });

    it('check point draw', function() {
        const rectangle = new Rectangle(10, 20, 30, 40);
        rectangle.draw('blue');
        expect(document.body)
            .toContainElement('svg[style^="top: 20px; left: 10px;"]>path[d="M 0 40 L 0 0"][stroke=blue]');
        expect(document.body)
            .toContainElement('svg[style^="top: 60px; left: 10px;"]>path[d="M 30 0 L 0 0"][stroke=blue]');
        expect(document.body)
            .toContainElement('svg[style^="top: 20px; left: 40px;"]>path[d="M 0 40 L 0 0"][stroke=blue]');
        expect(document.body)
            .toContainElement('svg[style^="top: 20px; left: 10px;"]>path[d="M 30 0 L 0 0"][stroke=blue]');
    });
});
