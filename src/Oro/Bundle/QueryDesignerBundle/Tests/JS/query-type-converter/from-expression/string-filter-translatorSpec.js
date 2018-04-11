define(function(require) {
    'use strict';

    var _ = require('underscore');
    var StringFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/from-expression/string-filter-translator');
    var FieldIdTranslator = require('oroquerydesigner/js/query-type-converter/from-expression/field-id-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;
    var createArrayNode = ExpressionLanguageLibrary.tools.createArrayNode;
    var createFunctionNode = ExpressionLanguageLibrary.tools.createFunctionNode;
    var createGetAttrNode = ExpressionLanguageLibrary.tools.createGetAttrNode;

    describe('oroquerydesigner/js/query-type-converter/from-expression/string-filter-translator', function() {
        var translator;
        var entityStructureDataProviderMock;
        var filterConfigProviderMock;

        beforeEach(function() {
            entityStructureDataProviderMock = jasmine.combineSpyObj('entityStructureDataProvider', [
                jasmine.createSpy('getPathByRelativePropertyPath').and.returnValue('bar'),
                jasmine.createSpy('getFieldSignatureSafely'),
                jasmine.combineSpyObj('rootEntity', [
                    jasmine.createSpy('get').and.returnValue('foo')
                ])
            ]);

            filterConfigProviderMock = jasmine.combineSpyObj('filterConfigProvider', [
                jasmine.createSpy('getApplicableFilterConfig').and.returnValue({
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
                })
            ]);
            translator = new StringFilterTranslator(
                new FieldIdTranslator(entityStructureDataProviderMock),
                filterConfigProviderMock
            );
        });

        describe('rejects node structure because', function() {
            var cases = {
                'unsupported operation': [
                    '>=',
                    new ConstantNode('baz')
                ],
                'improper right operand AST': [
                    '=',
                    new NameNode('baz')
                ],
                'operation `in` expects ArrayNode as right operand': [
                    'in',
                    new ConstantNode('baz')
                ],
                'operation `=` expects ConstantNode as right operand': [
                    '=',
                    createArrayNode(['baz'])
                ],
                'improper value in ArrayNode of right operand': [
                    'in',
                    createArrayNode([new NameNode('foo'), 2])
                ],
                'improper type of ArrayNode of right operand': [
                    'in',
                    createArrayNode({foo: 1})
                ],
                'operation `matches` expects FunctionNode as right operand': [
                    'matches',
                    new ConstantNode('baz')
                ],
                'improper value modifier for `matches` operator': [
                    'matches',
                    createFunctionNode('foo')
                ],
                'value modifier expects to have an argument': [
                    'matches',
                    createFunctionNode('containsRegExp')
                ],
                'value modifier expects to have only argument': [
                    'matches',
                    createFunctionNode('containsRegExp', ['foo', 'bar'])
                ],
                'value modifier expects ConstantNode as argument': [
                    'matches',
                    createFunctionNode('containsRegExp', [createArrayNode(['foo'])])
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it(caseName, function() {
                    var node = new BinaryNode(testCase[0], createGetAttrNode('foo.bar'), testCase[1]);
                    expect(translator.tryToTranslate(node)).toBe(null);
                    expect(entityStructureDataProviderMock.getFieldSignatureSafely).not.toHaveBeenCalled();
                });
            });

            it('improper AST node type', function() {
                expect(translator.tryToTranslate(new ConstantNode('foo'))).toBe(null);
                expect(entityStructureDataProviderMock.getFieldSignatureSafely).not.toHaveBeenCalled();
            });
        });

        describe('can translate AST binary node', function() {
            var cases = {
                'having `matches` operator with `containsRegExp` value modifier': [
                    // BinaryNode operator
                    'matches',

                    // BinaryNode right operand
                    createFunctionNode('containsRegExp', ['baz']),

                    // expected condition filter data
                    {
                        type: '1',
                        value: 'baz'
                    }
                ],
                'having `not matches` operator with `containsRegExp` value modifier': [
                    'not matches',
                    createFunctionNode('containsRegExp', ['baz']),
                    {
                        type: '2',
                        value: 'baz'
                    }
                ],
                'having `=` operator with string value': [
                    '=',
                    new ConstantNode('baz'),
                    {
                        type: '3',
                        value: 'baz'
                    }
                ],
                'having `matches` operator with `startWithRegExp` value modifier': [
                    'matches',
                    createFunctionNode('startWithRegExp', ['baz']),
                    {
                        type: '4',
                        value: 'baz'
                    }
                ],
                'having `matches` operator with `endWithRegExp` value modifier': [
                    'matches',
                    createFunctionNode('endWithRegExp', ['baz']),
                    {
                        type: '5',
                        value: 'baz'
                    }
                ],
                'having `in` operator with array of string': [
                    'in',
                    createArrayNode(['baz', 'qux']),
                    {
                        type: '6',
                        value: 'baz, qux'
                    }
                ],
                'having `not in` operator with array of string': [
                    'not in',
                    createArrayNode(['baz', 'qux']),
                    {
                        type: '7',
                        value: 'baz, qux'
                    }
                ],
                'having `=` operator with empty string': [
                    '=',
                    new ConstantNode(''),
                    {
                        type: 'filter_empty_option',
                        value: ''
                    }
                ],
                'having `!=` operator with empty string': [
                    '!=',
                    new ConstantNode(''),
                    {
                        type: 'filter_not_empty_option',
                        value: ''
                    }
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it(caseName, function() {
                    var node = new BinaryNode(testCase[0], createGetAttrNode('foo.bar'), testCase[1]);
                    var expectedCondition = {
                        columnName: 'bar',
                        criterion: {
                            filter: 'string',
                            data: testCase[2]
                        }
                    };

                    expect(translator.tryToTranslate(node)).toEqual(expectedCondition);
                });
            });
        });
    });
});
