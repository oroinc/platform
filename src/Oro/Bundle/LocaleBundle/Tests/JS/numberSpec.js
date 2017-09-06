define(function(require) {
    'use strict';

    var NumberFormatter = require('orolocale/js/formatter/number');

    describe('orolocale/js/formatter/number', function() {
        describe('check custom options', function() {
            it('change "grouping_used" option', function() {
                expect(NumberFormatter.formatDecimal(123456.789)).toEqual('123,456.789');
                expect(NumberFormatter.formatDecimal(123456.789, {
                    grouping_used: false
                })).toEqual('123456.789');
            });

            it('change "min_fraction_digits" option', function() {
                expect(NumberFormatter.formatDecimal(123456)).toEqual('123,456');
                expect(NumberFormatter.formatDecimal(123456, {
                    min_fraction_digits: 2
                })).toEqual('123,456.00');
                expect(NumberFormatter.formatDecimal(123456.7, {
                    min_fraction_digits: 2
                })).toEqual('123,456.70');
            });

            it('change "max_fraction_digits" option', function() {
                expect(NumberFormatter.formatDecimal(123456)).toEqual('123,456');
                expect(NumberFormatter.formatDecimal(123456, {
                    max_fraction_digits: 3
                })).toEqual('123,456');
                expect(NumberFormatter.formatDecimal(123456.0789, {
                    max_fraction_digits: 3
                })).toEqual('123,456.079');
            });
        });
    });
});
