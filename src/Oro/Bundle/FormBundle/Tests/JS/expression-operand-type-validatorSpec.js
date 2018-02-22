define(function(require) {
    'use strict';

    var _ = require('underscore');
    var ParsedExpression = require('oroexpressionlanguage/js/library/parsed-expression');
    var ExpressionOperandTypeValidator = require('oroform/js/expression-operand-type-validator');
    var DataProviderMock = require('./Fixture/entity-structure-data-provider-mock.js');
    var entitiesData = JSON.parse(require('text!./Fixture/entities-data.json'));
    var BinaryNode = require('oroexpressionlanguage/js/library/node/binary-node');
    var GetAttrNode = require('oroexpressionlanguage/js/library/node/get-attr-node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var NameNode = require('oroexpressionlanguage/js/library/node/name-node');
    var ArgumentsNode = require('oroexpressionlanguage/js/library/node/arguments-node');
    var ConditionalNode = require('oroexpressionlanguage/js/library/node/conditional-node');
    var ArrayNode = require('oroexpressionlanguage/js/library/node/array-node');

    // pre-calculated object to using in `createValidatorOptions` function only
    var _operationsValidatorOption = _.object(_.map(
        ['+', '-', '%', '*', '/', 'and', 'or', '==', '!=', '>', '<', '<=', '>=', 'in', 'not in', 'matches'],
        function(val) {
            return [val, {item: val}];
        }
    ));

    function createValidatorOptions(customOptions) {
        var entityDataProvider = new DataProviderMock(entitiesData);
        var entities = [
            {
                name: 'product',
                isCollection: false,
                fields: entityDataProvider.getEntityTreeNodeByPropertyPath('product')
            },
            {
                name: 'pricelist',
                isCollection: true,
                fields: entityDataProvider.getEntityTreeNodeByPropertyPath('pricelist')
            }
        ];

        return _.defaults(customOptions, {
            entities: entities,
            operations: _operationsValidatorOption,
            isConditionalNodeAllowed: false
        });
    }

    describe('oroform/js/expression-operand-type-validator', function() {
        describe('with default field deep level limit', function() {
            var operandTypeValidator;

            beforeEach(function() {
                var options = createValidatorOptions({itemLevelLimit: 3});
                operandTypeValidator = new ExpressionOperandTypeValidator(options);
            });

            afterEach(function() {
                operandTypeValidator = null;
            });

            describe('check correct expression', function() {
                function createArrayNode(values) {
                    var node = new ArrayNode();
                    _.each(values, function(value) {
                        node.addElement(new ConstantNode(value));
                    });
                    return node;
                }

                var correctCases = {
                    '1': new ConstantNode(1),
                    '1 == 1': new BinaryNode('==', new ConstantNode(1), new ConstantNode(1)),
                    '1 < 2': new BinaryNode('<', new ConstantNode(1), new ConstantNode(2)),
                    '1 + 1': new BinaryNode('+', new ConstantNode(1), new ConstantNode(1)),
                    'product.id': new GetAttrNode(
                        new NameNode('product'), new ConstantNode('id'), new ArgumentsNode(), 1
                    ),
                    'pricelist[1].id': new GetAttrNode(
                        new GetAttrNode(new NameNode('pricelist'), new ConstantNode(1), new ArgumentsNode(), 3),
                        new ConstantNode('id'),
                        new ArgumentsNode(),
                        1
                    ),
                    'product.category.id': new GetAttrNode(
                        new GetAttrNode(new NameNode('product'), new ConstantNode('category'), new ArgumentsNode(), 1),
                        new ConstantNode('id'),
                        new ArgumentsNode(),
                        1
                    ),
                    'product.id not in [1, 2, 3]': new BinaryNode('not in',
                        new GetAttrNode(new NameNode('product'), new ConstantNode('id'), new ArgumentsNode(), 1),
                        createArrayNode([1, 2, 3])
                    )
                };

                _.each(correctCases, function(nodes, expression) {
                    it('`' + expression + '` shouldn\'t throw an error', function() {
                        var parsedExpression = new ParsedExpression(expression, nodes);
                        expect(function() {
                            operandTypeValidator.expectValid(parsedExpression);
                        }).not.toThrow(jasmine.any(Error));
                    });
                });
            });

            describe('check incorrect expression', function() {
                var incorrectCases = [
                    [
                        'true ? 1 : 2',
                        new ConditionalNode(new ConstantNode(true), new ConstantNode(1), new ConstantNode(2)),
                        'conditional constructions are forbidden'
                    ], [
                        '1 ^ 1',
                        new BinaryNode('^', new ConstantNode(1), new ConstantNode(1)),
                        'used forbidden operation'
                    ], [
                        'product',
                        new NameNode('product'),
                        'entity used like a simple variable'
                    ], [
                        'pricelist',
                        new NameNode('pricelist'),
                        'collection used like a simple variable'
                    ], [
                        'product[1]',
                        new GetAttrNode(new NameNode('product'), new ConstantNode(1), new ArgumentsNode(), 3),
                        'simple entity used like a collection'
                    ], [
                        'pricelist[1]',
                        new GetAttrNode(new NameNode('pricelist'), new ConstantNode(1), new ArgumentsNode(), 3),
                        'item of collection of entities used like a field'
                    ], [
                        'product[1].id',
                        new GetAttrNode(
                            new GetAttrNode(new NameNode('product'), new ConstantNode(1), new ArgumentsNode(), 3),
                            new ConstantNode('id'),
                            new ArgumentsNode(),
                            1
                        ),
                        'simple entity used like a collection'
                    ], [
                        'product.category.parentCategory.id',
                        new GetAttrNode(
                            new GetAttrNode(
                                new GetAttrNode(
                                    new NameNode('product'), new ConstantNode('category'), new ArgumentsNode(), 1
                                ),
                                new ConstantNode('parentCategory'),
                                new ArgumentsNode(),
                                1
                            ),
                            new ConstantNode('id'),
                            new ArgumentsNode(),
                            1
                        ),
                        'used field located deeper than level limit'
                    ]
                ];

                _.each(incorrectCases, function(testCase) {
                    (function(expression, nodes, reason) {
                        it('`' + expression + '` should throw an type error because ' + reason, function() {
                            var parsedExpression = new ParsedExpression(expression, nodes);
                            expect(function() {
                                operandTypeValidator.expectValid(parsedExpression);
                            }).toThrow(jasmine.any(TypeError));
                        });
                    }).apply(null, testCase);
                });
            });
        });

        describe('when field deep level limit is `2`', function() {
            var operandTypeValidator;

            beforeEach(function() {
                var options = createValidatorOptions({itemLevelLimit: 2});
                operandTypeValidator = new ExpressionOperandTypeValidator(options);
            });

            afterEach(function() {
                operandTypeValidator = null;
            });

            it('second level is valid', function() {
                var parsedExpression = new ParsedExpression('product.id',
                    new GetAttrNode(new NameNode('product'), new ConstantNode('id'), new ArgumentsNode(), 1));
                expect(function() {
                    operandTypeValidator.expectValid(parsedExpression);
                }).not.toThrow(jasmine.any(Error));
            });

            it('third level is not valid', function() {
                var parsedExpression = new ParsedExpression('product.category.id', new GetAttrNode(
                    new GetAttrNode(new NameNode('product'), new ConstantNode('category'), new ArgumentsNode(), 1),
                    new ConstantNode('id'),
                    new ArgumentsNode(),
                    1
                ));
                expect(function() {
                    operandTypeValidator.expectValid(parsedExpression);
                }).toThrow(jasmine.any(TypeError));
            });
        });

        describe('when field deep level limit is `4`', function() {
            var operandTypeValidator;

            beforeEach(function() {
                var options = createValidatorOptions({itemLevelLimit: 4});
                operandTypeValidator = new ExpressionOperandTypeValidator(options);
            });

            afterEach(function() {
                operandTypeValidator = null;
            });

            it('fourth level is valid', function() {
                var nodes = new GetAttrNode(
                    new GetAttrNode(
                        new GetAttrNode(new NameNode('product'), new ConstantNode('category'), new ArgumentsNode(), 1),
                        new ConstantNode('parentCategory'),
                        new ArgumentsNode(),
                        1
                    ),
                    new ConstantNode('id'),
                    new ArgumentsNode(),
                    1
                );
                var parsedExpression = new ParsedExpression('product.category.parentCategory.id', nodes);
                expect(function() {
                    operandTypeValidator.expectValid(parsedExpression);
                }).not.toThrow(jasmine.any(Error));
            });

            it('fifth level is not valid', function() {
                var nodes = new GetAttrNode(
                    new GetAttrNode(
                        new GetAttrNode(
                            new GetAttrNode(
                                new NameNode('product'),
                                new ConstantNode('category'),
                                new ArgumentsNode(),
                                1
                            ),
                            new ConstantNode('parentCategory'),
                            new ArgumentsNode(),
                            1
                        ),
                        new ConstantNode('parentCategory'),
                        new ArgumentsNode(),
                        1
                    ),
                    new ConstantNode('id'),
                    new ArgumentsNode(),
                    1
                );
                var parsedExpression = new ParsedExpression('product.category.parentCategory.parentCategory.id', nodes);
                expect(function() {
                    operandTypeValidator.expectValid(parsedExpression);
                }).toThrow(jasmine.any(TypeError));
            });
        });

        describe('when additional options configured', function() {
            describe('with `+` operation only', function() {
                var operandTypeValidator;

                beforeEach(function() {
                    var options = createValidatorOptions({
                        operations: {
                            '+': {
                                item: '+'
                            }
                        }
                    });
                    operandTypeValidator = new ExpressionOperandTypeValidator(options);
                });

                afterEach(function() {
                    operandTypeValidator = null;
                });

                it('expression `1 + 1` hasn\'t to throw an error', function() {
                    var parsedExpression = new ParsedExpression('1 + 1',
                        new BinaryNode('+', new ConstantNode(1), new ConstantNode(1)));
                    expect(function() {
                        operandTypeValidator.expectValid(parsedExpression);
                    }).not.toThrow(jasmine.any(Error));
                });

                it('expression `1 - 1` has to throw a type error since forbidden operation is used', function() {
                    var parsedExpression = new ParsedExpression('1 - 1',
                        new BinaryNode('-', new ConstantNode(1), new ConstantNode(1)));
                    expect(function() {
                        operandTypeValidator.expectValid(parsedExpression);
                    }).toThrow(jasmine.any(TypeError));
                });
            });

            it('with conditional nodes allowed', function() {
                var options = createValidatorOptions({
                    isConditionalNodeAllowed: true
                });
                var operandTypeValidator = new ExpressionOperandTypeValidator(options);
                var parsedExpression = new ParsedExpression('true ? 1 : 2',
                    new ConditionalNode(new ConstantNode(true), new ConstantNode(1), new ConstantNode(2)));
                expect(function() {
                    operandTypeValidator.expectValid(parsedExpression);
                }).not.toThrow(jasmine.any(Error));
            });
        });
    });
});
