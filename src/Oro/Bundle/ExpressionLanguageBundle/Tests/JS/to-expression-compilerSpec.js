import ToExpressionCompiler from 'oroexpressionlanguage/js/to-expression-compiler';
import {BinaryNode, GetAttrNode, ConstantNode, NameNode, ArgumentsNode, ConditionalNode, UnaryNode, tools}
    from 'oroexpressionlanguage/js/expression-language-library';

const {createArrayNode, createFunctionNode} = tools;

function createGetAttrNode(node, item, type) {
    return new GetAttrNode(node, new ConstantNode(item), new ArgumentsNode(), type);
}

/**
 * Constructs ArgumentsNode with received items
 *
 * @param {...*} items - items
 * @returns {ArgumentsNode}
 */
function createArgumentsNode(...items) {
    const node = new ArgumentsNode();
    items.forEach(item => node.addElement(new ConstantNode(item)));
    return node;
}

describe('oroexpressionlanguage/js/to-expression-compiler', () => {
    describe('compile expression from AST', () => {
        const cases = [
            [
                new NameNode('foo'),
                'foo',
                ['foo']
            ],
            [
                new ConstantNode('a'),
                '"a"'
            ],
            [
                new ConstantNode('a\\'),
                '"a\\\\"'
            ],
            [
                new ConstantNode(3),
                '3'
            ],
            [
                new ConstantNode(false),
                'false'
            ],
            [
                new ConstantNode(true),
                'true'
            ],
            [
                new ConstantNode(null),
                'null'
            ],
            [
                new ConstantNode([1, 'a']),
                '[1, "a"]'
            ],
            [
                new ConstantNode({0: 1, b: 'a'}),
                '{"0": 1, b: "a"}'
            ],
            [
                new UnaryNode('-', new ConstantNode(3)),
                '-3'
            ],
            [
                new BinaryNode('-', new ConstantNode(3), new ConstantNode(2)),
                '3 - 2'
            ],
            [
                new UnaryNode('-',
                    new BinaryNode('+', new ConstantNode(3), new ConstantNode(2))
                ),
                '-(3 + 2)'
            ],
            [
                new UnaryNode('not',
                    new BinaryNode('/', new ConstantNode(3), new ConstantNode(2))
                ),
                'not 3 / 2'
            ],
            [
                new UnaryNode('!',
                    new BinaryNode('/', new ConstantNode(3), new ConstantNode(2))
                ),
                '!3 / 2'
            ],
            [
                new BinaryNode('/',
                    new UnaryNode('!', new ConstantNode(3)),
                    new ConstantNode(2)
                ),
                '(!3) / 2'
            ],
            [
                new UnaryNode('!',
                    new UnaryNode('-',
                        new ConstantNode(3)
                    )
                ),
                '!-3'
            ],
            [
                new UnaryNode('-',
                    new UnaryNode('not',
                        new ConstantNode(3)
                    )
                ),
                '-(not 3)'
            ],
            [
                new BinaryNode('*',
                    new BinaryNode('-', new ConstantNode(3), new ConstantNode(3)),
                    new ConstantNode(2)
                ),
                '(3 - 3) * 2'
            ],
            [
                new BinaryNode(
                    '*',
                    new ConstantNode(5),
                    new BinaryNode(
                        '/',
                        new ConstantNode(6),
                        new BinaryNode('+', new ConstantNode(3), new ConstantNode(4))
                    )
                ),
                '5 * 6 / (3 + 4)'
            ],
            [
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('bar'),
                    new ArgumentsNode(),
                    GetAttrNode.PROPERTY_CALL
                ),
                'foo.bar',
                ['foo']
            ],
            [
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('bar'),
                    new ArgumentsNode(),
                    GetAttrNode.METHOD_CALL
                ),
                'foo.bar()',
                ['foo']
            ],
            [
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('not'),
                    new ArgumentsNode(),
                    GetAttrNode.METHOD_CALL
                ),
                'foo.not()',
                ['foo']
            ],
            [
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('bar'),
                    createArgumentsNode('arg1', 2, true),
                    GetAttrNode.METHOD_CALL
                ),
                'foo.bar("arg1", 2, true)',
                ['foo']
            ],
            [
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode(3),
                    new ArgumentsNode(),
                    GetAttrNode.ARRAY_CALL
                ),
                'foo[3]',
                ['foo']
            ],
            [
                new ConditionalNode(new ConstantNode(true), new ConstantNode(true), new ConstantNode(false)),
                'true ? true : false'
            ],
            [
                new BinaryNode('matches', new ConstantNode('foo'), new ConstantNode('/foo/')),
                '"foo" matches "/foo/"'
            ],
            [
                createGetAttrNode(
                    createGetAttrNode(
                        createGetAttrNode(
                            createGetAttrNode(new NameNode('foo'), 'bar', GetAttrNode.METHOD_CALL),
                            'foo', GetAttrNode.METHOD_CALL),
                        'baz', GetAttrNode.PROPERTY_CALL),
                    3, GetAttrNode.ARRAY_CALL),
                'foo.bar().foo().baz[3]',
                ['foo']
            ],
            [
                createArrayNode([3, 'foo']),
                '[3, "foo"]'
            ],
            [
                createArrayNode(['foo', 'bar']),
                '["foo", "bar"]'
            ],
            [
                createArrayNode({foo: 3}),
                '{foo: 3}'
            ],
            [
                createArrayNode([[4, 3]]),
                '{4: 3}'
            ],
            [
                createArrayNode([['b', 'a'], 'b']),
                '{b: "a", 0: "b"}'
            ],
            [
                createArrayNode({'foo bar': 1}),
                '{"foo bar": 1}'
            ],
            [
                new NameNode('foo'),
                'bar',
                {foo: 'bar'}
            ],
            [
                createFunctionNode('foo', ['arg1', 2, true]),
                'foo("arg1", 2, true)'
            ]
        ];

        cases.forEach(([ast, expected, names = {}]) => {
            it(`node: \n\n${ast}\n\n`, () => {
                const toExpressionCompiler = new ToExpressionCompiler();
                toExpressionCompiler.compile(ast);
                expect(toExpressionCompiler.compile(ast, names)).toEqual(expected);
            });
        });
    });
});
