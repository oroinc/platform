import DateFilterTranslatorToExpression
    from 'oroquerydesigner/js/query-type-converter/to-expression/date-filter-translator';
import {BinaryNode, ConstantNode} from 'oroexpressionlanguage/js/expression-language-library';
import {createFunctionNode, createGetAttrNode} from 'oroexpressionlanguage/js/expression-language-tools';
import 'lib/jasmine-oro';

describe('oroquerydesigner/js/query-type-converter/to-expression/date-filter-translator', () => {
    let translator;
    const filterConfig = {
        type: 'date',
        name: 'date',
        choices: [
            {value: '1'},
            {value: '2'},
            {value: '3'},
            {value: '4'},
            {value: '5'},
            {value: '6'}
        ],
        dateParts: {
            value: 'value',
            dayofweek: 'day of week',
            week: 'week',
            day: 'day of month',
            month: 'month',
            quarter: 'quarter',
            dayofyear: 'day of year',
            year: 'year'
        },
        externalWidgetOptions: {
            dateVars: {
                value: {
                    1: 'now',
                    2: 'today',
                    3: 'start of the week',
                    4: 'start of the month',
                    5: 'start of the quarter',
                    6: 'start of the year',
                    17: 'current month without year',
                    29: 'this day without year'
                },
                dayofweek: {
                    10: 'current day'
                },
                week: {
                    11: 'current week'
                },
                day: {
                    10: 'current day'
                },
                month: {
                    12: 'current month',
                    16: 'first month of quarter'
                },
                quarter: {
                    13: 'current quarter'
                },
                dayofyear: {
                    10: 'current day',
                    15: 'first day of quarter'
                },
                year: {
                    14: 'current year'
                }
            }
        }
    };

    beforeEach(() => {
        translator = new DateFilterTranslatorToExpression(filterConfig);
    });

    describe('can not translate filter value', () => {
        const cases = {
            'when unknown criterion type': [{
                type: 'qux',
                value: {start: '2018-03-28', end: ''},
                part: 'value'
            }],
            'when missing value': [{
                type: '3',
                part: 'value'
            }],
            'when missing end value': [{
                type: '3',
                value: {start: '2018-03-28'},
                part: 'value'
            }],
            'when incorrect date value': [{
                type: '3',
                value: {start: '2018-03', end: ''},
                part: 'value'
            }],
            'when missing part': [{
                type: '3',
                value: {start: '2018-03-28', end: ''}
            }],
            'when unknown part': [{
                type: '3',
                value: {start: '2018-03-28', end: ''},
                part: 'era'
            }],
            'when incorrect day of week part value': [{
                type: '3',
                value: {start: '8', end: ''},
                part: 'dayofweek'
            }],
            'when incorrect month part value': [{
                type: '3',
                value: {start: '13', end: ''},
                part: 'month'
            }],
            'when incorrect year part value': [{
                type: '3',
                value: {start: '02018', end: ''},
                part: 'year'
            }]
        };

        jasmine.itEachCase(cases, filterValue => {
            expect(translator.test(filterValue)).toBe(false);
        });
    });

    describe('translate filter value', () => {
        const createLeftOperand = createGetAttrNode.bind(null, 'foo.bar');
        const cases = {
            'when value part between start and end dates': [
                // filterValue
                {
                    type: '1',
                    value: {start: '2018-03-01', end: '2018-03-31'},
                    part: 'value'
                },
                // expectedAST
                new BinaryNode(
                    'and',
                    new BinaryNode('>=', createLeftOperand(), new ConstantNode('2018-03-01')),
                    new BinaryNode('<=', createLeftOperand(), new ConstantNode('2018-03-31'))
                )
            ],
            'when value part between with empty start date and valuable end date': [
                {
                    type: '1',
                    value: {start: '', end: '2018-03-31'},
                    part: 'value'
                },
                new BinaryNode('<=', createLeftOperand(), new ConstantNode('2018-03-31'))
            ],
            'when value part between with valuable start date and empty end date': [
                {
                    type: '1',
                    value: {start: '2018-03-01', end: ''},
                    part: 'value'
                },
                new BinaryNode('>=', createLeftOperand(), new ConstantNode('2018-03-01'))
            ],
            'when value part not between start and end dates': [
                {
                    type: '2',
                    value: {start: '2018-03-01', end: '2018-03-31'},
                    part: 'value'
                },
                new BinaryNode(
                    'and',
                    new BinaryNode('<', createLeftOperand(), new ConstantNode('2018-03-01')),
                    new BinaryNode('>', createLeftOperand(), new ConstantNode('2018-03-31'))
                )
            ],
            'when value part not between empty start date and valuable end date': [
                {
                    type: '2',
                    value: {start: '', end: '2018-03-31'},
                    part: 'value'
                },
                new BinaryNode('>=', createLeftOperand(), new ConstantNode('2018-03-31'))
            ],
            'when value part not between valuable start date and empty end date': [
                {
                    type: '2',
                    value: {start: '2018-03-01', end: ''},
                    part: 'value'
                },
                new BinaryNode('<=', createLeftOperand(), new ConstantNode('2018-03-01'))
            ],
            'when value part later than the date': [
                {
                    type: '3',
                    value: {start: '2018-03-01', end: ''},
                    part: 'value'
                },
                new BinaryNode('>=', createLeftOperand(), new ConstantNode('2018-03-01'))
            ],
            'when value part earlier than the date': [
                {
                    type: '4',
                    value: {start: '', end: '2018-03-31'},
                    part: 'value'
                },
                new BinaryNode('<=', createLeftOperand(), new ConstantNode('2018-03-31'))
            ],
            'when value part equals to the date': [
                {
                    type: '5',
                    value: {start: '2018-03-01', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createLeftOperand(), new ConstantNode('2018-03-01'))
            ],
            'when value part not equals to the date': [
                {
                    type: '6',
                    value: {start: '', end: '2018-03-31'},
                    part: 'value'
                },
                new BinaryNode('!=', createLeftOperand(), new ConstantNode('2018-03-31'))
            ],
            'when value part equals to now': [
                {
                    type: '5',
                    value: {start: '{{1}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createLeftOperand(), createFunctionNode('now'))
            ],
            'when value part equals to today': [
                {
                    type: '5',
                    value: {start: '{{2}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createLeftOperand(), createFunctionNode('today'))
            ],
            'when value part equals to start of the week': [
                {
                    type: '5',
                    value: {start: '{{3}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createLeftOperand(), createFunctionNode('startOfTheWeek'))
            ],
            'when value part equals to start of the month': [
                {
                    type: '5',
                    value: {start: '{{4}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createLeftOperand(), createFunctionNode('startOfTheMonth'))
            ],
            'when value part equals to start of the quarter': [
                {
                    type: '5',
                    value: {start: '{{5}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createLeftOperand(), createFunctionNode('startOfTheQuarter'))
            ],
            'when value part equals to start of the year': [
                {
                    type: '5',
                    value: {start: '{{6}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createLeftOperand(), createFunctionNode('startOfTheYear'))
            ],
            'when value part equals to current month without year': [
                {
                    type: '5',
                    value: {start: '{{17}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createLeftOperand(), createFunctionNode('currentMonthWithoutYear'))
            ],
            'when value part equals to this day without year': [
                {
                    type: '5',
                    value: {start: '{{29}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createLeftOperand(), createFunctionNode('thisDayWithoutYear'))
            ],
            'when day of week part equals to value': [
                {
                    type: '5',
                    value: {start: '7', end: ''},
                    part: 'dayofweek'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfWeek', [createLeftOperand()]),
                    new ConstantNode('7')
                )
            ],
            'when day of week part equals to current day of week': [
                {
                    type: '5',
                    value: {start: '{{10}}', end: ''},
                    part: 'dayofweek'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfWeek', [createLeftOperand()]),
                    createFunctionNode('currentDayOfWeek')
                )
            ],
            'when week part equals to value': [
                {
                    type: '5',
                    value: {start: '5', end: ''},
                    part: 'week'
                },
                new BinaryNode('=',
                    createFunctionNode('week', [createLeftOperand()]),
                    new ConstantNode('5')
                )
            ],
            'when week part equals to current week': [
                {
                    type: '5',
                    value: {start: '{{11}}', end: ''},
                    part: 'week'
                },
                new BinaryNode('=',
                    createFunctionNode('week', [createLeftOperand()]),
                    createFunctionNode('currentWeek')
                )
            ],
            'when day of month part equals to value': [
                {
                    type: '5',
                    value: {start: '1', end: ''},
                    part: 'day'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfMonth', [createLeftOperand()]),
                    new ConstantNode('1')
                )
            ],
            'when day of month part equals to current day of month': [
                {
                    type: '5',
                    value: {start: '{{10}}', end: ''},
                    part: 'day'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfMonth', [createLeftOperand()]),
                    createFunctionNode('currentDayOfMonth')
                )
            ],
            'when month part equals to value': [
                {
                    type: '5',
                    value: {start: '2', end: ''},
                    part: 'month'
                },
                new BinaryNode('=',
                    createFunctionNode('month', [createLeftOperand()]),
                    new ConstantNode('2')
                )
            ],
            'when month part equals to current month': [
                {
                    type: '5',
                    value: {start: '{{12}}', end: ''},
                    part: 'month'
                },
                new BinaryNode('=',
                    createFunctionNode('month', [createLeftOperand()]),
                    createFunctionNode('currentMonth')
                )
            ],
            'when month part equals to first month of current quarter': [
                {
                    type: '5',
                    value: {start: '{{16}}', end: ''},
                    part: 'month'
                },
                new BinaryNode('=',
                    createFunctionNode('month', [createLeftOperand()]),
                    createFunctionNode('firstMonthOfCurrentQuarter')
                )
            ],
            'when quarter part equals to value': [
                {
                    type: '5',
                    value: {start: '1', end: ''},
                    part: 'quarter'
                },
                new BinaryNode('=',
                    createFunctionNode('quarter', [createLeftOperand()]),
                    new ConstantNode('1')
                )
            ],
            'when quarter part equals to current quarter': [
                {
                    type: '5',
                    value: {start: '{{13}}', end: ''},
                    part: 'quarter'
                },
                new BinaryNode('=',
                    createFunctionNode('quarter', [createLeftOperand()]),
                    createFunctionNode('currentQuarter')
                )
            ],
            'when day of year part equals to value': [
                {
                    type: '5',
                    value: {start: '32', end: ''},
                    part: 'dayofyear'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfYear', [createLeftOperand()]),
                    new ConstantNode('32')
                )
            ],
            'when day of year part equals to current day of year': [
                {
                    type: '5',
                    value: {start: '{{10}}', end: ''},
                    part: 'dayofyear'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfYear', [createLeftOperand()]),
                    createFunctionNode('currentDayOfYear')
                )
            ],
            'when day of year part equals to first day of current quarter': [
                {
                    type: '5',
                    value: {start: '{{15}}', end: ''},
                    part: 'dayofyear'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfYear', [createLeftOperand()]),
                    createFunctionNode('firstDayOfCurrentQuarter')
                )
            ],
            'when year part equals to value': [
                {
                    type: '5',
                    value: {start: '1981', end: ''},
                    part: 'year'
                },
                new BinaryNode('=',
                    createFunctionNode('year', [createLeftOperand()]),
                    new ConstantNode('1981')
                )
            ],
            'when year part equals to current year': [
                {
                    type: '5',
                    value: {start: '{{14}}', end: ''},
                    part: 'year'
                },
                new BinaryNode('=',
                    createFunctionNode('year', [createLeftOperand()]),
                    createFunctionNode('currentYear')
                )
            ],
            'when year part between some year and current year': [
                {
                    type: '1',
                    value: {start: '1981', end: '{{14}}'},
                    part: 'year'
                },
                new BinaryNode(
                    'and',
                    new BinaryNode('>=',
                        createFunctionNode('year', [createLeftOperand()]),
                        new ConstantNode('1981')
                    ),
                    new BinaryNode('<=',
                        createFunctionNode('year', [createLeftOperand()]),
                        createFunctionNode('currentYear')
                    )
                )
            ]
        };

        jasmine.itEachCase(cases, (filterValue, expectedAST) => {
            const leftOperand = createLeftOperand();

            expect(translator.test(filterValue)).toBe(true);
            expect(translator.translate(leftOperand, filterValue)).toEqual(expectedAST);
        });
    });
});
