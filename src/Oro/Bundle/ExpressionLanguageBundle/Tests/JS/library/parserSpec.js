import ArgumentsNode from 'oroexpressionlanguage/js/library/node/arguments-node';
import BinaryNode from 'oroexpressionlanguage/js/library/node/binary-node';
import ConditionalNode from 'oroexpressionlanguage/js/library/node/conditional-node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import GetAttrNode from 'oroexpressionlanguage/js/library/node/get-attr-node';
import NameNode from 'oroexpressionlanguage/js/library/node/name-node';
import UnaryNode from 'oroexpressionlanguage/js/library/node/unary-node';
import ExpressionSyntaxError from 'oroexpressionlanguage/js/library/expression-syntax-error';
import Lexer from 'oroexpressionlanguage/js/library/lexer';
import Parser from 'oroexpressionlanguage/js/library/parser';

function createGetAttrNode(node, item, type) {
    return new GetAttrNode(node, new ConstantNode(item), new ArgumentsNode(), type);
}

describe('oroexpressionlanguage/js/library/parser', () => {
    let lexer;
    let parser;
    beforeEach(() => {
        lexer = new Lexer();
        parser = new Parser({});
    });

    describe('missing variable', () => {
        let tokenStream;
        beforeEach(() => {
            tokenStream = lexer.tokenize('foo');
        });

        it('empty names list', () => {
            expect(() => {
                parser.parse(tokenStream);
            }).toThrowError('Variable "foo" is not valid around position 1 for expression `foo`.');
        });

        it('no `foo` variable', () => {
            expect(() => {
                parser.parse(tokenStream, [0]);
            }).toThrowError('Variable "foo" is not valid around position 1 for expression `foo`.');
        });
    });

    describe('parse', () => {
        const cases = [
            [
                new NameNode('a'),
                'a',
                ['a']
            ],
            [
                new ConstantNode('a'),
                '"a"'
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
                new UnaryNode('-', new ConstantNode(3)),
                '-3'
            ],
            [
                new BinaryNode('-', new ConstantNode(3), new ConstantNode(3)),
                '3 - 3'
            ],
            [
                new BinaryNode('*',
                    new BinaryNode('-', new ConstantNode(3), new ConstantNode(3)),
                    new ConstantNode(2)
                ),
                '(3 - 3) * 2'
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
                    (() => {
                        const args = new ArgumentsNode();
                        args.addElement(new ConstantNode('arg1'));
                        args.addElement(new ConstantNode(2));
                        args.addElement(new ConstantNode(true));
                        return args;
                    })(),
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
            // chained calls
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
                new NameNode('foo'),
                'bar',
                {foo: 'bar'}
            ]
        ];

        cases.forEach(testCase => {
            it('expression: `' + testCase[1] + '`', () => {
                const tokenStream = lexer.tokenize(testCase[1]);
                const names = testCase[2] || {};
                const ast = testCase[0];
                expect(parser.parse(tokenStream, names)).toEqual(ast);
            });
        });
    });

    describe('invalid postfix data', () => {
        const cases = [
            ['foo."#"', ['foo']],
            ['foo."bar"', ['foo']],
            ['foo.**', ['foo']],
            ['foo.123', ['foo']]
        ];

        cases.forEach(testCase => {
            it('expression: `' + testCase[0] + '`', () => {
                const tokenStream = lexer.tokenize(testCase[0]);
                const names = testCase[1];
                expect(() => {
                    parser.parse(tokenStream, names);
                }).toThrowError(ExpressionSyntaxError);
            });
        });
    });
});
