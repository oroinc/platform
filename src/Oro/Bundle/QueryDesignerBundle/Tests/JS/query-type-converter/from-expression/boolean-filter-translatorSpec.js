define(function(require) {
    'use strict';
    var BooleanFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/from-expression/boolean-filter-translator');
    var FieldIdTranslator = require('oroquerydesigner/js/query-type-converter/from-expression/field-id-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;
    var UnaryNode = ExpressionLanguageLibrary.UnaryNode;
    var _ = require('underscore');

    describe('oroquerydesigner/js/query-type-converter/from-expression/boolean-filter-translator', function() {
        var translator;
        var entityStructureDataProviderMock;
        var filterConfigProviderMock;
        var createGetAttrNode = function() {
            return new GetAttrNode(
                new NameNode('foo'),
                new ConstantNode('bar'),
                new ArgumentsNode(),
                GetAttrNode.PROPERTY_CALL
            );
        };
        var createBinaryNode = function(operator, value) {
            return new BinaryNode(
                operator,
                createGetAttrNode(),
                new ConstantNode(value)
            );
        };
        var createUnaryNode = function(operator) {
            return new UnaryNode(
                operator,
                createGetAttrNode()
            );
        };

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
                    type: 'boolean',
                    name: 'boolean',
                    choices: [
                        {value: '1'},
                        {value: '2'}
                    ]
                })
            ]);

            translator = new BooleanFilterTranslator(
                new FieldIdTranslator(entityStructureDataProviderMock),
                filterConfigProviderMock
            );
        });

        describe('Check filterConfig', function() {
            var cases = {
                'incorrect filter type': {
                    type: 'meboolean',
                    name: 'meboolean',
                    choices: [{value: '1'}, {value: '2'}]
                },
                'unavailable operation in filter config': {
                    type: 'boolean',
                    name: 'boolean',
                    choices: [{value: '0'}, {value: '3'}]
                }
            };

            _.each(cases, function(caseItem, caseName) {
                it(caseName, function() {
                    var node = createBinaryNode('==', false);

                    filterConfigProviderMock.getApplicableFilterConfig.and.returnValue(caseItem);
                    expect(translator.tryToTranslate(node)).toEqual(null);
                });
            });
        });

        describe('check invalid structure', function() {
            var cases = {
                'short expression with incorrect operator': createUnaryNode('+'),
                'long expression with incorrect operator': createBinaryNode('!=', false),
                'long expression with incorrect value type': createBinaryNode('==', 'false')
            };

            _.each(cases, function(testCase, caseName) {
                it(caseName, function() {
                    expect(translator.tryToTranslate(testCase)).toBe(null);
                    expect(entityStructureDataProviderMock.getFieldSignatureSafely).not.toHaveBeenCalled();
                });
            });
        });

        describe('check valid structure', function() {
            var cases = {
                'expression with binary operator `==` and value is `false`': [
                    createBinaryNode('==', false),
                    '2'
                ],
                'expression with binary operator `==` and value is `true`': [
                    createBinaryNode('==', true),
                    '1'
                ],
                'expression with binary operator `=` and value is `false`': [
                    createBinaryNode('=', false),
                    '2'
                ],
                'expression with binary operator `=` and value is `true`': [
                    createBinaryNode('=', true),
                    '1'
                ],
                'expression with unary operator `!`': [
                    createUnaryNode('!'),
                    '2'
                ],
                'expression with unary operator `not`': [
                    createUnaryNode('not'),
                    '2'
                ],
                'expression without operator': [
                    createGetAttrNode(),
                    '1'
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it(caseName, function() {
                    var expectedCondition = {
                        columnName: 'bar',
                        criterion: {
                            filter: 'boolean',
                            data: {
                                value: testCase[1]
                            }
                        }
                    };
                    expect(translator.tryToTranslate(testCase[0])).toEqual(expectedCondition);
                });
            });
        });
    });
});
