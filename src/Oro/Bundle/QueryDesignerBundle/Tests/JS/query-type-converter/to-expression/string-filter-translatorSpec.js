define(function(require) {
    'use strict';

    var _ = require('underscore');
    var StringFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/string-filter-translator');
    var FieldIdTranslator = require('oroquerydesigner/js/query-type-converter/to-expression/field-id-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var Node = ExpressionLanguageLibrary.Node;
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;
    var FunctionNode = ExpressionLanguageLibrary.FunctionNode;
    var createArrayNode = ExpressionLanguageLibrary.tools.createArrayNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/string-filter-translator', function() {
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
                        type: 'string',
                        name: 'string',
                        choices: [
                            {value: '1'},
                            {value: '2'},
                            {value: '3'},
                            {value: '4'},
                            {value: '5'},
                            {value: '6'},
                            {value: '7'},
                            {value: 'filter_empty_option'},
                            {value: 'filter_not_empty_option'}
                        ]
                    }
                ])
            ]);

            translator = new StringFilterTranslator(
                new FieldIdTranslator(entityStructureDataProviderMock),
                filterConfigProviderMock
            );
        });

        it('calls filter provider\'s method `getFilterConfigsByType` with correct filter type', function() {
            translator.tryToTranslate({
                columnName: 'bar',
                criterion: {
                    filter: 'string',
                    data: {
                        type: '3',
                        value: 'baz'
                    }
                }
            });
            expect(filterConfigProviderMock.getFilterConfigsByType).toHaveBeenCalledWith('string');
        });

        describe('translates valid condition', function() {
            var cases = [
                [
                    // filter type
                    'contains',

                    // condition filter data
                    {
                        type: '1',
                        value: 'baz'
                    },

                    // expected operator
                    'matches',

                    // expected right operand
                    new FunctionNode('containsRegExp', new Node([new ConstantNode('baz')]))
                ],
                [
                    'not contains',
                    {
                        type: '2',
                        value: 'baz'
                    },
                    'not matches',
                    new FunctionNode('containsRegExp', new Node([new ConstantNode('baz')]))
                ],
                [
                    'is equal to',
                    {
                        type: '3',
                        value: 'baz'
                    },
                    '=',
                    new ConstantNode('baz')
                ],
                [
                    'starts with',
                    {
                        type: '4',
                        value: 'baz'
                    },
                    'matches',
                    new FunctionNode('startWithRegExp', new Node([new ConstantNode('baz')]))
                ],
                [
                    'ends with',
                    {
                        type: '5',
                        value: 'baz'
                    },
                    'matches',
                    new FunctionNode('endWithRegExp', new Node([new ConstantNode('baz')]))
                ],
                [
                    'is any of',
                    {
                        type: '6',
                        value: 'baz, qux'
                    },
                    'in',
                    createArrayNode(['baz', 'qux'])
                ],
                [
                    'is not any of',
                    {
                        type: '7',
                        value: 'baz, qux'
                    },
                    'not in',
                    createArrayNode(['baz', 'qux'])
                ],
                [
                    'is empty',
                    {
                        type: 'filter_empty_option',
                        value: 'qux'
                    },
                    '=',
                    new ConstantNode('')
                ],
                [
                    'is not empty',
                    {
                        type: 'filter_not_empty_option',
                        value: 'qux'
                    },
                    '!=',
                    new ConstantNode('')
                ]
            ];

            _.each(cases, function(testCase) {
                it('when filter has `' + testCase[0] + '` type', function() {
                    var condition = {
                        columnName: 'bar',
                        criterion: {
                            filter: 'string',
                            data: testCase[1]
                        }
                    };
                    var expectedAST = new BinaryNode(
                        testCase[2],
                        new GetAttrNode(
                            new NameNode('foo'),
                            new ConstantNode('bar'),
                            new ArgumentsNode(),
                            GetAttrNode.PROPERTY_CALL
                        ),
                        testCase[3]
                    );

                    expect(translator.tryToTranslate(condition)).toEqual(expectedAST);
                });
            });
        });

        describe('can\'t translate condition because of', function() {
            var cases = {
                'unknown filter': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'qux',
                        data: {
                            type: '1',
                            value: 'baz'
                        }
                    }
                },
                'unknown criterion type': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'string',
                        data: {
                            type: 'qux',
                            value: ''
                        }
                    }
                },
                'missing column name': {
                    criterion: {
                        filter: 'string',
                        data: {
                            type: '1',
                            value: 'baz'
                        }
                    }
                },
                'missing value': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'string',
                        data: {
                            type: '1'
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
