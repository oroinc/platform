import {BinaryNode, ConstantNode, NameNode, UnaryNode} from 'oroexpressionlanguage/js/expression-language-library';
import QueryConditionConverterFromExpression
    from 'oroquerydesigner/js/query-type-converter/from-expression/query-condition-converter';

describe('oroquerydesigner/js/query-type-converter/from-expression/query-condition-converter', () => {
    let queryConditionConverterFromExpression;

    beforeEach(() => {
        const conditionTranslatorsMock = [
            jasmine.combineSpyObj('constantConditionTranslator', [
                jasmine.createSpy('tryToTranslate').and
                    .callFake(node => {
                        return node instanceof ConstantNode ? {value: node.attrs.value} : null;
                    })
            ]),
            jasmine.combineSpyObj('binaryConditionTranslator', [
                jasmine.createSpy('tryToTranslate').and
                    .callFake(node => {
                        return node instanceof BinaryNode &&
                            node.attrs.operator === 'and' &&
                            node.nodes[0] instanceof NameNode &&
                            node.nodes[1] instanceof ConstantNode
                            ? {name: node.nodes[0].attrs.name, value: node.nodes[1].attrs.value} : null;
                    })
            ])
        ];

        queryConditionConverterFromExpression = new QueryConditionConverterFromExpression(conditionTranslatorsMock);
    });

    describe('convert AST to condition', () => {
        const cases = {
            'single supported node': [
                new ConstantNode(42),
                [{value: 42}]
            ],
            'single unsupported node': [
                new NameNode('foo'),
                null
            ],
            'supported binary node to single condition': [
                new BinaryNode('and', new NameNode('foo'), new ConstantNode(42)),
                [{name: 'foo', value: 42}]
            ],
            'unsupported binary node': [
                new BinaryNode('and', new UnaryNode('!', new NameNode('foo')), new ConstantNode(42)),
                null
            ],
            'binary node to complex condition with `AND` operation': [
                new BinaryNode('and', new ConstantNode(1), new ConstantNode(42)),
                [{value: 1}, 'AND', {value: 42}]
            ],
            'binary node to complex condition with `OR` operation': [
                new BinaryNode('or', new ConstantNode(1), new ConstantNode(42)),
                [{value: 1}, 'OR', {value: 42}]
            ],
            'binary node to complex condition with grouped `OR` operation': [
                new BinaryNode('and',
                    new BinaryNode('and', new NameNode('foo'), new ConstantNode(42)),
                    new BinaryNode('or', new ConstantNode(1), new ConstantNode(42))
                ),
                [{name: 'foo', value: 42}, 'AND', [{value: 1}, 'OR', {value: 42}]]
            ],
            'binary node to complex condition with plain `OR` operation': [
                new BinaryNode('or',
                    new BinaryNode('and',
                        new BinaryNode('and', new NameNode('foo'), new ConstantNode(42)),
                        new ConstantNode(1)
                    ),
                    new ConstantNode(42)
                ),
                [{name: 'foo', value: 42}, 'AND', {value: 1}, 'OR', {value: 42}]
            ],
            'binary node to complex condition with nested unsupported condition': [
                new BinaryNode('or',
                    new BinaryNode('and',
                        new BinaryNode('and', new NameNode('foo'), new ConstantNode(42)),
                        new ConstantNode(1)
                    ),
                    new NameNode('foo')
                ),
                null
            ]
        };

        jasmine.itEachCase(cases, (ast, expected) => {
            const actual = queryConditionConverterFromExpression.convert({nodes: ast});
            expect(actual).toEqual(expected);
        });
    });
});
