import ParsedExpression from 'oroexpressionlanguage/js/library/parsed-expression';
import ExpressionOperandTypeValidator from 'oroform/js/expression-operand-type-validator';
import DataProviderMock from './Fixture/entity-structure-data-provider-mock.js';
import entitiesData from './Fixture/entities-data.json';
import BinaryNode from 'oroexpressionlanguage/js/library/node/binary-node';
import GetAttrNode from 'oroexpressionlanguage/js/library/node/get-attr-node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import NameNode from 'oroexpressionlanguage/js/library/node/name-node';
import ArgumentsNode from 'oroexpressionlanguage/js/library/node/arguments-node';
import ConditionalNode from 'oroexpressionlanguage/js/library/node/conditional-node';
import ArrayNode from 'oroexpressionlanguage/js/library/node/array-node';

// pre-calculated object to using in `createValidatorOptions` function only
const _operationsValidatorOption = Object.fromEntries(
    ['+', '-', '%', '*', '/', 'and', 'or', '==', '!=', '>', '<', '<=', '>=', 'in', 'not in', 'matches']
        .map(val => [val, {item: val}])
);

function createValidatorOptions(customOptions) {
    const entityDataProvider = new DataProviderMock(entitiesData);
    const entities = [
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

    return Object.assign({
        entities,
        operations: _operationsValidatorOption,
        isConditionalNodeAllowed: false
    }, customOptions);
}

describe('oroform/js/expression-operand-type-validator', () => {
    describe('with default field deep level limit', () => {
        let operandTypeValidator;

        beforeEach(() => {
            const options = createValidatorOptions({itemLevelLimit: 3});
            operandTypeValidator = new ExpressionOperandTypeValidator(options);
        });

        afterEach(() => {
            operandTypeValidator = null;
        });

        describe('check correct expression', () => {
            function createArrayNode(values) {
                const node = new ArrayNode();
                values.forEach(value => node.addElement(new ConstantNode(value)));
                return node;
            }

            const correctCases = {
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

            Object.entries(correctCases).forEach(([expression, nodes]) => {
                it('`' + expression + '` shouldn\'t throw an error', () => {
                    const parsedExpression = new ParsedExpression(expression, nodes);
                    expect(() => {
                        operandTypeValidator.expectValid(parsedExpression);
                    }).not.toThrow(jasmine.any(Error));
                });
            });
        });

        describe('check incorrect expression', () => {
            const incorrectCases = [
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

            incorrectCases.forEach(([expression, nodes, reason]) => {
                it(`\`${expression}\` should throw an type error because ${reason}`, () => {
                    const parsedExpression = new ParsedExpression(expression, nodes);
                    expect(() => {
                        operandTypeValidator.expectValid(parsedExpression);
                    }).toThrow(jasmine.any(TypeError));
                });
            });
        });
    });

    describe('when field deep level limit is `2`', () => {
        let operandTypeValidator;

        beforeEach(() => {
            const options = createValidatorOptions({itemLevelLimit: 2});
            operandTypeValidator = new ExpressionOperandTypeValidator(options);
        });

        afterEach(() => {
            operandTypeValidator = null;
        });

        it('second level is valid', () => {
            const parsedExpression = new ParsedExpression('product.id',
                new GetAttrNode(new NameNode('product'), new ConstantNode('id'), new ArgumentsNode(), 1));
            expect(() => {
                operandTypeValidator.expectValid(parsedExpression);
            }).not.toThrow(jasmine.any(Error));
        });

        it('third level is not valid', () => {
            const parsedExpression = new ParsedExpression('product.category.id', new GetAttrNode(
                new GetAttrNode(new NameNode('product'), new ConstantNode('category'), new ArgumentsNode(), 1),
                new ConstantNode('id'),
                new ArgumentsNode(),
                1
            ));
            expect(() => {
                operandTypeValidator.expectValid(parsedExpression);
            }).toThrow(jasmine.any(TypeError));
        });
    });

    describe('when field deep level limit is `4`', () => {
        let operandTypeValidator;

        beforeEach(() => {
            const options = createValidatorOptions({itemLevelLimit: 4});
            operandTypeValidator = new ExpressionOperandTypeValidator(options);
        });

        afterEach(() => {
            operandTypeValidator = null;
        });

        it('fourth level is valid', () => {
            const nodes = new GetAttrNode(
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
            const parsedExpression = new ParsedExpression('product.category.parentCategory.id', nodes);
            expect(() => {
                operandTypeValidator.expectValid(parsedExpression);
            }).not.toThrow(jasmine.any(Error));
        });

        it('fifth level is not valid', () => {
            const nodes = new GetAttrNode(
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
            const parsedExpression =
                new ParsedExpression('product.category.parentCategory.parentCategory.id', nodes);
            expect(() => {
                operandTypeValidator.expectValid(parsedExpression);
            }).toThrow(jasmine.any(TypeError));
        });
    });

    describe('when additional options configured', () => {
        describe('with `+` operation only', () => {
            let operandTypeValidator;

            beforeEach(() => {
                const options = createValidatorOptions({
                    operations: {
                        '+': {
                            item: '+'
                        }
                    }
                });
                operandTypeValidator = new ExpressionOperandTypeValidator(options);
            });

            afterEach(() => {
                operandTypeValidator = null;
            });

            it('expression `1 + 1` hasn\'t to throw an error', () => {
                const parsedExpression = new ParsedExpression('1 + 1',
                    new BinaryNode('+', new ConstantNode(1), new ConstantNode(1)));
                expect(() => {
                    operandTypeValidator.expectValid(parsedExpression);
                }).not.toThrow(jasmine.any(Error));
            });

            it('expression `1 - 1` has to throw a type error since forbidden operation is used', () => {
                const parsedExpression = new ParsedExpression('1 - 1',
                    new BinaryNode('-', new ConstantNode(1), new ConstantNode(1)));
                expect(() => {
                    operandTypeValidator.expectValid(parsedExpression);
                }).toThrow(jasmine.any(TypeError));
            });
        });

        it('with conditional nodes allowed', () => {
            const options = createValidatorOptions({
                isConditionalNodeAllowed: true
            });
            const operandTypeValidator = new ExpressionOperandTypeValidator(options);
            const parsedExpression = new ParsedExpression('true ? 1 : 2',
                new ConditionalNode(new ConstantNode(true), new ConstantNode(1), new ConstantNode(2)));
            expect(() => {
                operandTypeValidator.expectValid(parsedExpression);
            }).not.toThrow(jasmine.any(Error));
        });
    });
});
