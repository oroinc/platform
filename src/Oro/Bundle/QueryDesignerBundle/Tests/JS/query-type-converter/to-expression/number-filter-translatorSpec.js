define(function(require) {
    'use strict';

    var _ = require('underscore');
    var NumberFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/number-filter-translator');
    var FieldIdTranslator = require('oroquerydesigner/js/query-type-converter/to-expression/field-id-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;
    var createArrayNode = ExpressionLanguageLibrary.tools.createArrayNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/number-filter-translator', function() {
        var translator;
        var filterConfigProviderMock;

        beforeEach(function() {
            var entityStructureDataProviderMock = jasmine.combineSpyObj('entityStructureDataProvider', [
                jasmine.createSpy('getRelativePropertyPathByPath').and.returnValue('bar'),
                jasmine.combineSpyObj('rootEntity', [
                    jasmine.createSpy('get').and.returnValue('foo')
                ])
            ]);

            filterConfigProviderMock = jasmine.combineSpyObj('filterConfigProvider', [
                jasmine.createSpy('getFilterConfigsByType').and.returnValue([
                    {
                        type: 'number',
                        name: 'number',
                        choices: [
                            {value: '1'},
                            {value: '2'},
                            {value: '3'},
                            {value: '4'},
                            {value: '5'},
                            {value: '6'},
                            {value: '7'},
                            {value: '8'},
                            {value: '9'},
                            {value: '10'},
                            {value: 'filter_empty_option'},
                            {value: 'filter_not_empty_option'}
                        ]
                    }
                ])
            ]);

            translator = new NumberFilterTranslator(
                new FieldIdTranslator(entityStructureDataProviderMock),
                filterConfigProviderMock
            );
        });

        it('calls filter provider\'s method `getFilterConfigsByType` with correct filter type', function() {
            translator.tryToTranslate({
                columnName: 'bar',
                criterion: {
                    filter: 'number',
                    data: {
                        type: '3',
                        value: 10
                    }
                }
            });
            expect(filterConfigProviderMock.getFilterConfigsByType).toHaveBeenCalledWith('number');
        });

        describe('translates valid condition', function() {
            var createLeftOperandAST = function() {
                return new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('bar'),
                    new ArgumentsNode(),
                    GetAttrNode.PROPERTY_CALL
                );
            };

            var cases = {
                'equals or greater than': [
                    {
                        type: '1',
                        value: 10
                    },
                    new BinaryNode(
                        '>=',
                        createLeftOperandAST(),
                        new ConstantNode(10)
                    )
                ],
                'greater than': [
                    {
                        type: '2',
                        value: '10'
                    },
                    new BinaryNode(
                        '>',
                        createLeftOperandAST(),
                        new ConstantNode(10)
                    )
                ],
                'is equal to': [
                    {
                        type: '3',
                        value: 10.5
                    },
                    new BinaryNode(
                        '=',
                        createLeftOperandAST(),
                        new ConstantNode(10.5)
                    )
                ],
                'is not equals to': [
                    {
                        type: '4',
                        value: 10
                    },
                    new BinaryNode(
                        '!=',
                        createLeftOperandAST(),
                        new ConstantNode(10)
                    )
                ],
                'equals or less than': [
                    {
                        type: '5',
                        value: '10'
                    },
                    new BinaryNode(
                        '<=',
                        createLeftOperandAST(),
                        new ConstantNode(10)
                    )
                ],
                'less than': [
                    {
                        type: '6',
                        value: 10
                    },
                    new BinaryNode(
                        '<',
                        createLeftOperandAST(),
                        new ConstantNode(10)
                    )
                ],
                'between': [
                    {
                        type: '7',
                        value: 10,
                        value_end: 0
                    },
                    new BinaryNode(
                        'and',
                        new BinaryNode('>=', createLeftOperandAST(), new ConstantNode(0)),
                        new BinaryNode('<=', createLeftOperandAST(), new ConstantNode(10))
                    )
                ],
                'not between': [
                    {
                        type: '8',
                        value: '0',
                        value_end: 10
                    },
                    new BinaryNode(
                        'and',
                        new BinaryNode('<', createLeftOperandAST(), new ConstantNode(0)),
                        new BinaryNode('>', createLeftOperandAST(), new ConstantNode(10))
                    )
                ],
                'range and value_end less them value': [
                    {
                        type: '7',
                        value: 10,
                        value_end: 1
                    },
                    new BinaryNode(
                        'and',
                        new BinaryNode('>=', createLeftOperandAST(), new ConstantNode(1)),
                        new BinaryNode('<=', createLeftOperandAST(), new ConstantNode(10))
                    )
                ],
                'range and values are string': [
                    {
                        type: '7',
                        value: '10',
                        value_end: '1'
                    },
                    new BinaryNode(
                        'and',
                        new BinaryNode('>=', createLeftOperandAST(), new ConstantNode(1)),
                        new BinaryNode('<=', createLeftOperandAST(), new ConstantNode(10))
                    )
                ],
                'range and value is empty': [
                    {
                        type: '8',
                        value: '',
                        value_end: 10
                    },
                    new BinaryNode(
                        '<',
                        createLeftOperandAST(),
                        new ConstantNode(10)
                    )
                ],
                'range and value_end is empty': [
                    {
                        type: '8',
                        value: 10,
                        value_end: ''
                    },
                    new BinaryNode(
                        '>',
                        createLeftOperandAST(),
                        new ConstantNode(10)
                    )
                ],
                'is any of': [
                    {
                        type: '9',
                        value: '1, 2'
                    },
                    new BinaryNode(
                        'in',
                        createLeftOperandAST(),
                        createArrayNode([1, 2])
                    )
                ],
                'is not any of': [
                    {
                        type: '10',
                        value: '1, 2'
                    },
                    new BinaryNode(
                        'not in',
                        createLeftOperandAST(),
                        createArrayNode([1, 2])
                    )
                ],
                'is empty': [
                    {
                        type: 'filter_empty_option'
                    },
                    new BinaryNode(
                        '=',
                        createLeftOperandAST(),
                        new ConstantNode(0)
                    )
                ],
                'is not empty': [
                    {
                        type: 'filter_not_empty_option'
                    },
                    new BinaryNode(
                        '!=',
                        createLeftOperandAST(),
                        new ConstantNode(0)
                    )
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it('when filter has `' + caseName + '` type', function() {
                    var condition = {
                        columnName: 'bar',
                        criterion: {
                            filter: 'number',
                            data: testCase[0]
                        }
                    };

                    expect(translator.tryToTranslate(condition)).toEqual(testCase[1]);
                });
            });
        });

        describe('can\'t translate condition because of', function() {
            var cases = {
                'unknown filter': {
                    columnName: 'foo',
                    criterion: {
                        filter: 'mynumber',
                        data: {
                            type: '1',
                            value: 1
                        }
                    }
                },
                'unknown criterion type': {
                    columnName: 'foo',
                    criterion: {
                        filter: 'number',
                        data: {
                            type: 'mynumber',
                            value: 1
                        }
                    }
                },
                'missing column name': {
                    criterion: {
                        filter: 'number',
                        data: {
                            type: '1',
                            value: 1
                        }
                    }
                }
            };

            _.each(cases, function(condition, caseName) {
                it(caseName, function() {
                    expect(translator.tryToTranslate(condition)).toBe(null);
                });
            });
        });
    });
});
