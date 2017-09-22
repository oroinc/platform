define(function(require) {
    'use strict';

    var NumberFormatter = require('orofilter/js/formatter/number-formatter');

    describe('orofilter/js/formatter/number-formatter', function() {
        describe('check formatter options', function() {
            it('check default formatter', function() {
                var formatter = new NumberFormatter();
                expect(formatter.fromRaw(123456.789)).toEqual('123456.79');
                expect(formatter.toRaw('123456.789')).toEqual(123456.79);
            });

            it('check custom formatter', function() {
                var formatter = new NumberFormatter({
                    orderSeparator: ','
                });
                expect(formatter.fromRaw(123456.789)).toEqual('123,456.79');
                expect(formatter.toRaw('123,456.789')).toEqual(123456.79);
            });
        });
    });
});
