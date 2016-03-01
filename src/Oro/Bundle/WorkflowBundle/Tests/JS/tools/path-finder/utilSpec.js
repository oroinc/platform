define(function(require) {
    'use strict';

    var util = require('oroworkflow/js/tools/path-finder/util');

    describe('oroworkflow/js/tools/path-finder/interval1d', function() {
        it('should correct check between position', function() {
            expect(util.between(5, 10, 20)).toBe(false);
            expect(util.between(10, 10, 20)).toBe(true);
            expect(util.between(15, 10, 20)).toBe(true);
            expect(util.between(20, 10, 20)).toBe(true);
            expect(util.between(25, 10, 20)).toBe(false);
        });

        it('should correct check between non inclusive position', function() {
            expect(util.betweenNonInclusive(5, 10, 20)).toBe(false);
            expect(util.betweenNonInclusive(10, 10, 20)).toBe(false);
            expect(util.betweenNonInclusive(15, 10, 20)).toBe(true);
            expect(util.betweenNonInclusive(20, 10, 20)).toBe(false);
            expect(util.betweenNonInclusive(25, 10, 20)).toBe(false);
        });
    });
});

