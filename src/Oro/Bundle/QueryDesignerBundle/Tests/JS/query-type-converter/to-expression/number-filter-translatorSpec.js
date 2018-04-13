define(function(require) {
    'use strict';

    var NumberFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/number-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var createArrayNode = ExpressionLanguageLibrary.tools.createArrayNode;
    var createGetAttrNode = ExpressionLanguageLibrary.tools.createGetAttrNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/number-filter-translator', function() {
        var translator;
        var filterConfig = {
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

        beforeEach(function() {
            translator = new NumberFilterTranslator();
        });

        describe('can not translate filter value', function() {
            var cases = {
                'when criterion type is unknown': [{
                    type: 'qux',
                    value: 1
                }],
                'when missing criterion type': [{
                    value: 1
                }]
            };

            jasmine.itEachCase(cases, function(filterValue) {
                expect(translator.test(filterValue, filterConfig)).toBe(false);
            });
        });

        describe('translate filter value', function() {
            var createLeftOperand = createGetAttrNode.bind(null, 'foo.bar');
            var cases = {
                'when filter has `equals or greater than` type': [
                    {
                        type: '1',
                        value: 10
                    },
                    new BinaryNode('>=', createLeftOperand(), new ConstantNode(10))
                ],
                'when filter has `greater than` type': [
                    {
                        type: '2',
                        value: '10'
                    },
                    new BinaryNode('>', createLeftOperand(), new ConstantNode(10))
                ],
                'when filter has `is equal to` type': [
                    {
                        type: '3',
                        value: 10.5
                    },
                    new BinaryNode('=', createLeftOperand(), new ConstantNode(10.5))
                ],
                'when filter has `is not equals to` type': [
                    {
                        type: '4',
                        value: 10
                    },
                    new BinaryNode('!=', createLeftOperand(), new ConstantNode(10))
                ],
                'when filter has `equals or less than` type': [
                    {
                        type: '5',
                        value: '10'
                    },
                    new BinaryNode('<=', createLeftOperand(), new ConstantNode(10))
                ],
                'when filter has `less than` type': [
                    {
                        type: '6',
                        value: 10
                    },
                    new BinaryNode('<', createLeftOperand(), new ConstantNode(10))
                ],
                'when filter has `between` type': [
                    {
                        type: '7',
                        value: 10,
                        value_end: 0
                    },
                    new BinaryNode(
                        'and',
                        new BinaryNode('>=', createLeftOperand(), new ConstantNode(0)),
                        new BinaryNode('<=', createLeftOperand(), new ConstantNode(10))
                    )
                ],
                'when filter has `not between` type': [
                    {
                        type: '8',
                        value: '0',
                        value_end: 10
                    },
                    new BinaryNode(
                        'and',
                        new BinaryNode('<', createLeftOperand(), new ConstantNode(0)),
                        new BinaryNode('>', createLeftOperand(), new ConstantNode(10))
                    )
                ],
                'when filter has `range and value_end less them value` type': [
                    {
                        type: '7',
                        value: 10,
                        value_end: 1
                    },
                    new BinaryNode(
                        'and',
                        new BinaryNode('>=', createLeftOperand(), new ConstantNode(1)),
                        new BinaryNode('<=', createLeftOperand(), new ConstantNode(10))
                    )
                ],
                'when filter has `range and values are string` type': [
                    {
                        type: '7',
                        value: '10',
                        value_end: '1'
                    },
                    new BinaryNode(
                        'and',
                        new BinaryNode('>=', createLeftOperand(), new ConstantNode(1)),
                        new BinaryNode('<=', createLeftOperand(), new ConstantNode(10))
                    )
                ],
                'when filter has `range and value is empty` type': [
                    {
                        type: '8',
                        value: '',
                        value_end: 10
                    },
                    new BinaryNode('<', createLeftOperand(), new ConstantNode(10))
                ],
                'when filter has `range and value_end is empty` type': [
                    {
                        type: '8',
                        value: 10,
                        value_end: ''
                    },
                    new BinaryNode('>', createLeftOperand(), new ConstantNode(10))
                ],
                'when filter has `is any of` type': [
                    {
                        type: '9',
                        value: '1, 2'
                    },
                    new BinaryNode('in', createLeftOperand(), createArrayNode([1, 2]))
                ],
                'when filter has `is not any of` type': [
                    {
                        type: '10',
                        value: '1, 2'
                    },
                    new BinaryNode('not in', createLeftOperand(), createArrayNode([1, 2]))
                ],
                'when filter has `is empty` type': [
                    {
                        type: 'filter_empty_option'
                    },
                    new BinaryNode('=', createLeftOperand(), new ConstantNode(0))
                ],
                'when filter has `is not empty` type': [
                    {
                        type: 'filter_not_empty_option'
                    },
                    new BinaryNode('!=', createLeftOperand(), new ConstantNode(0))
                ]
            };

            jasmine.itEachCase(cases, function(filterValue, expectedAST) {
                var leftOperand = createLeftOperand();

                expect(translator.test(filterValue, filterConfig)).toBe(true);
                expect(translator.translate(leftOperand, filterValue)).toEqual(expectedAST);
            });
        });
    });
});
