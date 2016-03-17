define([
    'orofilter/js/date-variable-helper'
], function(DateVariableHelper) {
    'use strict';

    var dateVariableHelper = new DateVariableHelper({
        'value': {
            '10': 'current day'
        },
        'dayofweek': {
            '10': 'current day',
            '15': 'first day of quarter'
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
            rawValue: ''
        }
    ];

    describe('orofilter/js/date-variable-helper', function() {
        it('should', function() {
            data.forEach(function(item) {
                expect(dateVariableHelper.isDateVariable(item.value)).toBe(item.isDateVariable);
                if (item.isDateVariable) {
                    expect(dateVariableHelper.formatRawValue(item.value)).toEqual(item.rawValue);
                }
            });
        });
    });
});
