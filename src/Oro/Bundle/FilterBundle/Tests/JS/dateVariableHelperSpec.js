define([
    'orofilter/js/date-variable-helper'
], function(DateVariableHelper) {
    'use strict';

    var dateVariableHelper = new DateVariableHelper({
        value: {
            10: 'current day',
            11: 'current day without year'
        },
        dayofweek: {
            10: 'current day',
            15: 'first day of quarter'
        }
    });

    var data = [
        {
            value: 'current day-1',
            isDateVariable: true,
            rawValue: '{{10}}-1'
        },
        {
            value: 'current day - 1',
            isDateVariable: true,
            rawValue: '{{10}} - 1'
        },
        {
            value: 'not variable-1',
            isDateVariable: false,
            rawValue: 'not variable-1'
        },
        {
            value: 'current day without year',
            isDateVariable: true,
            rawValue: '{{11}}'
        }
    ];

    describe('orofilter/js/date-variable-helper', function() {
        it('should work as expected', function() {
            data.forEach(function(item) {
                expect(dateVariableHelper.isDateVariable(item.value)).toBe(item.isDateVariable);
                expect(dateVariableHelper.formatDisplayValue(item.rawValue)).toEqual(item.value);
                expect(dateVariableHelper.formatRawValue(item.value)).toEqual(item.rawValue);
            });
        });
    });
});
