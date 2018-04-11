define(function(require) {
    'use strict';

    var _ = require('underscore');
    var NumberFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/number-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var createArrayNode = ExpressionLanguageLibrary.tools.createArrayNode;
    var createGetAttrNode = ExpressionLanguageLibrary.tools.createGetAttrNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/number-filter-translator', function() {
        var translator;
        var filterConfig;

        beforeEach(function() {
            filterConfig = {
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
            };

            translator = new NumberFilterTranslator();
        });

        it('can\'t translate condition because of unknown criterion type', function() {
            expect(translator.test({type: 'qux', value: 1}, filterConfig)).toBe(false);
        });

        it('can\'t translate condition because of missing criterion type', function() {
            expect(translator.test({value: 1}, filterConfig)).toBe(false);
        });

        describe('translates valid condition', function() {
            var cases = {
                'equals or greater than': [
                    {
                        type: '1',
                        value: 10
                    },
                    new BinaryNode('>=', createGetAttrNode('foo.bar'), new ConstantNode(10))
                ],
                'greater than': [
                    {
                        type: '2',
                        value: '10'
                    },
                    new BinaryNode('>', createGetAttrNode('foo.bar'), new ConstantNode(10))
                ],
                'is equal to': [
                    {
                        type: '3',
                        value: 10.5
                    },
                    new BinaryNode('=', createGetAttrNode('foo.bar'), new ConstantNode(10.5))
                ],
                'is not equals to': [
                    {
                        type: '4',
                        value: 10
                    },
                    new BinaryNode('!=', createGetAttrNode('foo.bar'), new ConstantNode(10))
                ],
                'equals or less than': [
                    {
                        type: '5',
                        value: '10'
                    },
                    new BinaryNode('<=', createGetAttrNode('foo.bar'), new ConstantNode(10))
                ],
                'less than': [
                    {
                        type: '6',
                        value: 10
                    },
                    new BinaryNode('<', createGetAttrNode('foo.bar'), new ConstantNode(10))
                ],
                'between': [
                    {
                        type: '7',
                        value: 10,
                        value_end: 0
                    },
                    new BinaryNode(
                        'and',
                        new BinaryNode('>=', createGetAttrNode('foo.bar'), new ConstantNode(0)),
                        new BinaryNode('<=', createGetAttrNode('foo.bar'), new ConstantNode(10))
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
                        new BinaryNode('<', createGetAttrNode('foo.bar'), new ConstantNode(0)),
                        new BinaryNode('>', createGetAttrNode('foo.bar'), new ConstantNode(10))
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
                        new BinaryNode('>=', createGetAttrNode('foo.bar'), new ConstantNode(1)),
                        new BinaryNode('<=', createGetAttrNode('foo.bar'), new ConstantNode(10))
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
                        new BinaryNode('>=', createGetAttrNode('foo.bar'), new ConstantNode(1)),
                        new BinaryNode('<=', createGetAttrNode('foo.bar'), new ConstantNode(10))
                    )
                ],
                'range and value is empty': [
                    {
                        type: '8',
                        value: '',
                        value_end: 10
                    },
                    new BinaryNode('<', createGetAttrNode('foo.bar'), new ConstantNode(10))
                ],
                'range and value_end is empty': [
                    {
                        type: '8',
                        value: 10,
                        value_end: ''
                    },
                    new BinaryNode('>', createGetAttrNode('foo.bar'), new ConstantNode(10))
                ],
                'is any of': [
                    {
                        type: '9',
                        value: '1, 2'
                    },
                    new BinaryNode('in', createGetAttrNode('foo.bar'), createArrayNode([1, 2]))
                ],
                'is not any of': [
                    {
                        type: '10',
                        value: '1, 2'
                    },
                    new BinaryNode('not in', createGetAttrNode('foo.bar'), createArrayNode([1, 2]))
                ],
                'is empty': [
                    {
                        type: 'filter_empty_option'
                    },
                    new BinaryNode('=', createGetAttrNode('foo.bar'), new ConstantNode(0))
                ],
                'is not empty': [
                    {
                        type: 'filter_not_empty_option'
                    },
                    new BinaryNode('!=', createGetAttrNode('foo.bar'), new ConstantNode(0))
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it('when filter has `' + caseName + '` type', function() {
                    var leftOperand = createGetAttrNode('foo.bar');
                    var filterValue = testCase[0];
                    var expectedAST = testCase[1];

                    expect(translator.test(filterValue, filterConfig)).toBe(true);
                    expect(translator.translate(leftOperand, filterValue)).toEqual(expectedAST);
                });
            });
        });
    });
});
