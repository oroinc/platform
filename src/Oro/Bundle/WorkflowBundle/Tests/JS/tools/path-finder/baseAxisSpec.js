define(function(require) {
    'use strict';

    var BaseAxis = require('oroworkflow/js/tools/path-finder/base-axis');
    var Point2d = require('oroworkflow/js/tools/path-finder/point2d');

    describe('oroworkflow/js/tools/path-finder/base-axis', function() {
        it('should construct', function () {
            var leftConstraint = {};
            var rightConstraint = {};
            var locationDirective = {};
            var axis = new BaseAxis(new Point2d(0,0), new Point2d(0, 100), null, 1, leftConstraint, rightConstraint,
                locationDirective);
            expect(axis.linesIncluded).toBe(1);
            expect(leftConstraint.axis).toBe(axis);
            expect(rightConstraint.axis).toBe(axis);
            expect(locationDirective.axis).toBe(axis);
            expect(axis.used).toBe(false);
            expect(axis.graph).toBe(null);

            expect(axis.leftConstraint).toBe(leftConstraint);
            expect(axis.rightConstraint).toBe(rightConstraint);
            expect(axis.locationDirective).toBe(locationDirective);
        });
    });
});
