import DatetimeFilterTranslatorFromExpression
    from 'oroquerydesigner/js/query-type-converter/from-expression/datetime-filter-translator';
import FieldIdTranslatorFromExpression
    from 'oroquerydesigner/js/query-type-converter/from-expression/field-id-translator';
import {ArrayNode, BinaryNode, ConstantNode, tools} from 'oroexpressionlanguage/js/expression-language-library';
import 'lib/jasmine-oro';

const {createFunctionNode, createGetAttrNode} = tools;

describe('oroquerydesigner/js/query-type-converter/from-expression/datetime-filter-translator', () => {
    let translator;
    let entityStructureDataProviderMock;
    let filterConfigProviderMock;

    beforeEach(() => {
        entityStructureDataProviderMock = jasmine.combineSpyObj('entityStructureDataProvider', [
            jasmine.createSpy('getPathByRelativePropertyPath').and.returnValue('bar'),
            jasmine.createSpy('getFieldSignatureSafely'),
            jasmine.combineSpyObj('rootEntity', [
                jasmine.createSpy('get').and.returnValue('foo')
            ])
        ]);

        filterConfigProviderMock = jasmine.createSpyObj('filterConfigProvider', ['getApplicableFilterConfig']);

        translator = new DatetimeFilterTranslatorFromExpression(
            new FieldIdTranslatorFromExpression(entityStructureDataProviderMock),
            filterConfigProviderMock
        );
    });

    describe('rejects node structure because', () => {
        const cases = {
            'improper AST': [
                new ConstantNode('test')
            ],
            'unsupported operation': [
                new BinaryNode('in', createGetAttrNode('foo.bar'), new ConstantNode('2018-04-03 13:45'))
            ],
            'improper left operand AST': [
                new BinaryNode('=', new ConstantNode('test'), new ConstantNode('2018-04-03 13:45'))
            ],
            'unsupported function call in left operand (unsupported date part)': [
                new BinaryNode('=',
                    createFunctionNode('dateISO', [createGetAttrNode('foo.bar')]),
                    new ConstantNode('2018-04-03 13:45')
                )
            ],
            'improper right operand AST': [
                new BinaryNode('=', createGetAttrNode('foo.bar'), new ArrayNode())
            ],
            'improper value of right operand': [
                new BinaryNode('=', createGetAttrNode('foo.bar'), new ConstantNode('Apr 3, 2018 13:45'))
            ],
            'unsupported function call in right operand (unsupported variable)': [
                new BinaryNode('=', createGetAttrNode('foo.bar'), createFunctionNode('rightNow'))
            ],
            'different fields in the between operation': [
                new BinaryNode('and',
                    new BinaryNode('>=', createGetAttrNode('foo.bar'), new ConstantNode('2018-04-03 13:45')),
                    new BinaryNode('<=', createGetAttrNode('foo.qux'), new ConstantNode('2018-04-07 13:45'))
                )
            ],
            'invalid pair operators in the not between operation': [
                new BinaryNode('and',
                    new BinaryNode('<', createGetAttrNode('foo.bar'), new ConstantNode('2018-04-03 13:45')),
                    new BinaryNode('>=', createGetAttrNode('foo.bar'), new ConstantNode('2018-04-07 13:45'))
                )
            ]
        };

        jasmine.itEachCase(cases, ast => {
            expect(translator.tryToTranslate(ast)).toBe(null);
            expect(entityStructureDataProviderMock.getFieldSignatureSafely).not.toHaveBeenCalled();
        });
    });

    describe('valid node structure', () => {
        beforeEach(() => {
            filterConfigProviderMock.getApplicableFilterConfig.and.returnValue({
                type: 'datetime',
                name: 'datetime',
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
            });
        });

        const cases = {
            'value part between start and end datetime': [
                // expected condition filter data
                {
                    type: '1',
                    value: {start: '2018-03-01 00:00', end: '2018-03-31 23:59'},
                    part: 'value'
                },
                // provided AST
                new BinaryNode(
                    'and',
                    new BinaryNode('>=', createGetAttrNode('foo.bar'), new ConstantNode('2018-03-01 00:00')),
                    new BinaryNode('<=', createGetAttrNode('foo.bar'), new ConstantNode('2018-03-31 23:59'))
                )
            ],
            'value part not between start and end datetime': [
                {
                    type: '2',
                    value: {start: '2018-03-01 00:00', end: '2018-03-31 23:59'},
                    part: 'value'
                },
                new BinaryNode(
                    'and',
                    new BinaryNode('<', createGetAttrNode('foo.bar'), new ConstantNode('2018-03-01 00:00')),
                    new BinaryNode('>', createGetAttrNode('foo.bar'), new ConstantNode('2018-03-31 23:59'))
                )
            ],
            'value part later than the datetime': [
                {
                    type: '3',
                    value: {start: '2018-03-01 13:45', end: ''},
                    part: 'value'
                },
                new BinaryNode('>=', createGetAttrNode('foo.bar'), new ConstantNode('2018-03-01 13:45'))
            ],
            'value part earlier than the datetime': [
                {
                    type: '4',
                    value: {start: '', end: '2018-03-31 13:45'},
                    part: 'value'
                },
                new BinaryNode('<=', createGetAttrNode('foo.bar'), new ConstantNode('2018-03-31 13:45'))
            ],
            'value part equals to the datetime': [
                {
                    type: '5',
                    value: {start: '2018-03-01 13:45', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createGetAttrNode('foo.bar'), new ConstantNode('2018-03-01 13:45'))
            ],
            'value part not equals to the datetime': [
                {
                    type: '6',
                    value: {start: '', end: '2018-03-31 13:45'},
                    part: 'value'
                },
                new BinaryNode('!=', createGetAttrNode('foo.bar'), new ConstantNode('2018-03-31 13:45'))
            ],
            'value part equals to now': [
                {
                    type: '5',
                    value: {start: '{{1}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createGetAttrNode('foo.bar'), createFunctionNode('now'))
            ],
            'value part equals to today': [
                {
                    type: '5',
                    value: {start: '{{2}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createGetAttrNode('foo.bar'), createFunctionNode('today'))
            ],
            'value part equals to start of the week': [
                {
                    type: '5',
                    value: {start: '{{3}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createGetAttrNode('foo.bar'), createFunctionNode('startOfTheWeek'))
            ],
            'value part equals to start of the month': [
                {
                    type: '5',
                    value: {start: '{{4}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createGetAttrNode('foo.bar'), createFunctionNode('startOfTheMonth'))
            ],
            'value part equals to start of the quarter': [
                {
                    type: '5',
                    value: {start: '{{5}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createGetAttrNode('foo.bar'), createFunctionNode('startOfTheQuarter'))
            ],
            'value part equals to start of the year': [
                {
                    type: '5',
                    value: {start: '{{6}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createGetAttrNode('foo.bar'), createFunctionNode('startOfTheYear'))
            ],
            'value part equals to current month without year': [
                {
                    type: '5',
                    value: {start: '{{17}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createGetAttrNode('foo.bar'), createFunctionNode('currentMonthWithoutYear'))
            ],
            'value part equals to this day without year': [
                {
                    type: '5',
                    value: {start: '{{29}}', end: ''},
                    part: 'value'
                },
                new BinaryNode('=', createGetAttrNode('foo.bar'), createFunctionNode('thisDayWithoutYear'))
            ],
            'day of week part equals to value': [
                {
                    type: '5',
                    value: {start: '7', end: ''},
                    part: 'dayofweek'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfWeek', [createGetAttrNode('foo.bar')]),
                    new ConstantNode('7')
                )
            ],
            'day of week part equals to current day of week': [
                {
                    type: '5',
                    value: {start: '{{10}}', end: ''},
                    part: 'dayofweek'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfWeek', [createGetAttrNode('foo.bar')]),
                    createFunctionNode('currentDayOfWeek')
                )
            ],
            'week part equals to value': [
                {
                    type: '5',
                    value: {start: '5', end: ''},
                    part: 'week'
                },
                new BinaryNode('=',
                    createFunctionNode('week', [createGetAttrNode('foo.bar')]),
                    new ConstantNode('5')
                )
            ],
            'week part equals to current week': [
                {
                    type: '5',
                    value: {start: '{{11}}', end: ''},
                    part: 'week'
                },
                new BinaryNode('=',
                    createFunctionNode('week', [createGetAttrNode('foo.bar')]),
                    createFunctionNode('currentWeek')
                )
            ],
            'day of month part equals to value': [
                {
                    type: '5',
                    value: {start: '1', end: ''},
                    part: 'day'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfMonth', [createGetAttrNode('foo.bar')]),
                    new ConstantNode('1')
                )
            ],
            'day of month part equals to current day of month': [
                {
                    type: '5',
                    value: {start: '{{10}}', end: ''},
                    part: 'day'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfMonth', [createGetAttrNode('foo.bar')]),
                    createFunctionNode('currentDayOfMonth')
                )
            ],
            'month part equals to value': [
                {
                    type: '5',
                    value: {start: '2', end: ''},
                    part: 'month'
                },
                new BinaryNode('=',
                    createFunctionNode('month', [createGetAttrNode('foo.bar')]),
                    new ConstantNode('2')
                )
            ],
            'month part equals to current month': [
                {
                    type: '5',
                    value: {start: '{{12}}', end: ''},
                    part: 'month'
                },
                new BinaryNode('=',
                    createFunctionNode('month', [createGetAttrNode('foo.bar')]),
                    createFunctionNode('currentMonth')
                )
            ],
            'month part equals to first month of current quarter': [
                {
                    type: '5',
                    value: {start: '{{16}}', end: ''},
                    part: 'month'
                },
                new BinaryNode('=',
                    createFunctionNode('month', [createGetAttrNode('foo.bar')]),
                    createFunctionNode('firstMonthOfCurrentQuarter')
                )
            ],
            'quarter part equals to value': [
                {
                    type: '5',
                    value: {start: '1', end: ''},
                    part: 'quarter'
                },
                new BinaryNode('=',
                    createFunctionNode('quarter', [createGetAttrNode('foo.bar')]),
                    new ConstantNode('1')
                )
            ],
            'quarter part equals to current quarter': [
                {
                    type: '5',
                    value: {start: '{{13}}', end: ''},
                    part: 'quarter'
                },
                new BinaryNode('=',
                    createFunctionNode('quarter', [createGetAttrNode('foo.bar')]),
                    createFunctionNode('currentQuarter')
                )
            ],
            'day of year part equals to value': [
                {
                    type: '5',
                    value: {start: '32', end: ''},
                    part: 'dayofyear'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfYear', [createGetAttrNode('foo.bar')]),
                    new ConstantNode('32')
                )
            ],
            'day of year part equals to current day of year': [
                {
                    type: '5',
                    value: {start: '{{10}}', end: ''},
                    part: 'dayofyear'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfYear', [createGetAttrNode('foo.bar')]),
                    createFunctionNode('currentDayOfYear')
                )
            ],
            'day of year part equals to first day of current quarter': [
                {
                    type: '5',
                    value: {start: '{{15}}', end: ''},
                    part: 'dayofyear'
                },
                new BinaryNode('=',
                    createFunctionNode('dayOfYear', [createGetAttrNode('foo.bar')]),
                    createFunctionNode('firstDayOfCurrentQuarter')
                )
            ],
            'year part equals to value': [
                {
                    type: '5',
                    value: {start: '1981', end: ''},
                    part: 'year'
                },
                new BinaryNode('=',
                    createFunctionNode('year', [createGetAttrNode('foo.bar')]),
                    new ConstantNode('1981')
                )
            ],
            'year part equals to current year': [
                {
                    type: '5',
                    value: {start: '{{14}}', end: ''},
                    part: 'year'
                },
                new BinaryNode('=',
                    createFunctionNode('year', [createGetAttrNode('foo.bar')]),
                    createFunctionNode('currentYear')
                )
            ],
            'year part between some year and current year': [
                {
                    type: '1',
                    value: {start: '1981', end: '{{14}}'},
                    part: 'year'
                },
                new BinaryNode(
                    'and',
                    new BinaryNode('>=',
                        createFunctionNode('year', [createGetAttrNode('foo.bar')]),
                        new ConstantNode('1981')
                    ),
                    new BinaryNode('<=',
                        createFunctionNode('year', [createGetAttrNode('foo.bar')]),
                        createFunctionNode('currentYear')
                    )
                )
            ]
        };

        jasmine.itEachCase(cases, (filterValue, ast) => {
            const expectedCondition = {
                columnName: 'bar',
                criterion: {
                    filter: 'datetime',
                    data: filterValue
                }
            };
            expect(translator.tryToTranslate(ast)).toEqual(expectedCondition);
        });
    });

    describe('filter config is taken in account', () => {
        it('unavailable filter criteria', () => {
            filterConfigProviderMock.getApplicableFilterConfig.and.returnValue({
                type: 'datetime',
                name: 'datetime',
                choices: [
                    {value: '1'}
                ],
                dateParts: {
                    value: 'value'
                }
            });
            const ast = new BinaryNode('=', createGetAttrNode('foo.bar'), new ConstantNode('2018-03-01 13:45'));
            expect(translator.tryToTranslate(ast)).toEqual(null);
        });

        it('unavailable date part', () => {
            filterConfigProviderMock.getApplicableFilterConfig.and.returnValue({
                type: 'datetime',
                name: 'datetime',
                choices: [
                    {value: '5'}
                ],
                dateParts: {
                    value: 'value'
                }
            });
            const ast = new BinaryNode('=',
                createFunctionNode('month', [createGetAttrNode('foo.bar')]),
                new ConstantNode('1')
            );
            expect(translator.tryToTranslate(ast)).toEqual(null);
        });

        it('variables are not allowed', () => {
            filterConfigProviderMock.getApplicableFilterConfig.and.returnValue({
                type: 'datetime',
                name: 'datetime',
                choices: [
                    {value: '5'}
                ],
                dateParts: {
                    value: 'value',
                    month: 'month'
                }
            });
            const ast = new BinaryNode('=',
                createFunctionNode('month', [createGetAttrNode('foo.bar')]),
                createFunctionNode('firstMonthOfCurrentQuarter')
            );
            expect(translator.tryToTranslate(ast)).toEqual(null);
        });

        it('unavailable date variable', () => {
            filterConfigProviderMock.getApplicableFilterConfig.and.returnValue({
                type: 'datetime',
                name: 'datetime',
                choices: [
                    {value: '5'}
                ],
                dateParts: {
                    value: 'value',
                    month: 'month'
                },
                externalWidgetOptions: {
                    dateVars: {
                        month: {
                            12: 'current month'
                        }
                    }
                }
            });
            const ast = new BinaryNode('=',
                createFunctionNode('month', [createGetAttrNode('foo.bar')]),
                createFunctionNode('firstMonthOfCurrentQuarter')
            );
            expect(translator.tryToTranslate(ast)).toEqual(null);
        });

        it('available date part and variable', () => {
            filterConfigProviderMock.getApplicableFilterConfig.and.returnValue({
                type: 'datetime',
                name: 'datetime',
                choices: [
                    {value: '5'}
                ],
                dateParts: {
                    value: 'value',
                    month: 'month'
                },
                externalWidgetOptions: {
                    dateVars: {
                        month: {
                            12: 'current month',
                            16: 'first month of quarter'
                        }
                    }
                }
            });
            const ast = new BinaryNode('=',
                createFunctionNode('month', [createGetAttrNode('foo.bar')]),
                createFunctionNode('firstMonthOfCurrentQuarter')
            );
            expect(translator.tryToTranslate(ast)).toEqual({
                columnName: 'bar',
                criterion: {
                    filter: 'datetime',
                    data: {
                        type: '5',
                        part: 'month',
                        value: {
                            start: '{{16}}',
                            end: ''
                        }
                    }
                }
            });
        });
    });
});
