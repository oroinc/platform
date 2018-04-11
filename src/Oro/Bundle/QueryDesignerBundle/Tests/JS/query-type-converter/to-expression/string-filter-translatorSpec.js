define(function(require) {
    'use strict';

    var _ = require('underscore');
    var StringFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/string-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var createArrayNode = ExpressionLanguageLibrary.tools.createArrayNode;
    var createGetAttrNode = ExpressionLanguageLibrary.tools.createGetAttrNode;
    var createFunctionNode = ExpressionLanguageLibrary.tools.createFunctionNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/string-filter-translator', function() {
        var translator;
        var filterConfig;

        beforeEach(function() {
            translator = new StringFilterTranslator();

            filterConfig = {
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
            };
        });

        it('can\'t translate condition because of unknown criterion type', function() {
            expect(translator.test({type: 'qux', value: ''}, filterConfig)).toBe(false);
        });

        it('can\'t translate condition because of missing value', function() {
            expect(translator.test({type: '1'}, filterConfig)).toBe(false);
        });

        describe('translates valid filterValue', function() {
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
                    createFunctionNode('containsRegExp', ['baz'])
                ],
                [
                    'not contains',
                    {
                        type: '2',
                        value: 'baz'
                    },
                    'not matches',
                    createFunctionNode('containsRegExp', ['baz'])
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
                    createFunctionNode('startWithRegExp', ['baz'])
                ],
                [
                    'ends with',
                    {
                        type: '5',
                        value: 'baz'
                    },
                    'matches',
                    createFunctionNode('endWithRegExp', ['baz'])
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
                    var filterValue = testCase[1];
                    var leftOperand = createGetAttrNode('foo.bar');
                    var expectedAST = new BinaryNode(testCase[2], leftOperand, testCase[3]);

                    expect(translator.test(filterValue, filterConfig)).toBe(true);
                    expect(translator.translate(leftOperand, filterValue)).toEqual(expectedAST);
                });
            });
        });
    });
});
