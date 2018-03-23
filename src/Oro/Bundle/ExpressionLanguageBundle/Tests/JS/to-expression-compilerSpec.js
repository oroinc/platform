define(function(require) {
    'use strict';

    var ToExpressionCompiler = require('oroexpressionlanguage/js/to-expression-compiler');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var Node = ExpressionLanguageLibrary.Node;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;
    var ArrayNode = ExpressionLanguageLibrary.ArrayNode;
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var ConditionalNode = ExpressionLanguageLibrary.ConditionalNode;
    var UnaryNode = ExpressionLanguageLibrary.UnaryNode;
    var FunctionNode = ExpressionLanguageLibrary.FunctionNode;

    function createGetAttrNode(node, item, type) {
        return new GetAttrNode(node, new ConstantNode(item), new ArgumentsNode(), type);
    }

    /**
     * Constructs ArrayNode with received items
     *
     * @param {...*} - items, if item is an array first item of it is key and second is value for node element
     * @returns {ArgumentsNode}
     */
    function createArrayNode() {
        var node = new ArrayNode();

        for (var i = 0; i < arguments.length; i++) {
            if (Object.prototype.toString.call(arguments[i]) === '[object Array]' && arguments[i].length > 1) {
                node.addElement(new ConstantNode(arguments[i][1]), new ConstantNode(arguments[i][0]));
            } else {
                node.addElement(new ConstantNode(arguments[i]));
            }
        }

        return node;
    }

    /**
     * Constructs ArgumentsNode with received items
     *
     * @param {...*} - items
     * @returns {ArgumentsNode}
     */
    function createArgumentsNode() {
        var node = new ArgumentsNode();

        for (var i = 0; i < arguments.length; i++) {
            node.addElement(new ConstantNode(arguments[i]));
        }

        return node;
    }

    describe('oroexpressionlanguage/js/ToExpressionCompiler', function() {
        describe('parse AST', function() {
            var cases = [
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
                    createArrayNode(3, 'foo'),
                    '[3, "foo"]'
                ],
                [
                    createArrayNode([0, 'foo'], [1, 'bar']),
                    '["foo", "bar"]'
                ],
                [
                    createArrayNode(['foo', 3]),
                    '{foo: 3}'
                ],
                [
                    createArrayNode([4, 3]),
                    '{4: 3}'
                ],
                [
                    createArrayNode(['b', 'a'], 'b'),
                    '{b: "a", 0: "b"}'
                ],
                [
                    createArrayNode(['foo bar', 1]),
                    '{"foo bar": 1}'
                ],
                [
                    new NameNode('foo'),
                    'bar',
                    {foo: 'bar'}
                ],
                [
                    new FunctionNode(
                        'foo',
                        new Node([
                            new ConstantNode('arg1'),
                            new ConstantNode(2),
                            new ConstantNode(true)
                        ])
                    ),
                    'foo("arg1", 2, true)'
                ]
            ];

            cases.forEach(function(testCase) {
                it('node: \n\n' + testCase[0] + '\n\n', function() {
                    var toExpressionCompiler = new ToExpressionCompiler();
                    var ast = testCase[0];
                    var expected = testCase[1];
                    var names = testCase[2] || {};

                    toExpressionCompiler.compile(ast);
                    expect(toExpressionCompiler.compile(ast, names)).toEqual(expected);
                });
            });
        });
    });
});
