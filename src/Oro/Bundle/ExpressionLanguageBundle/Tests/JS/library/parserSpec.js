define(function(require) {
    'use strict';

    var ArgumentsNode = require('oroexpressionlanguage/js/library/node/arguments-node');
    var BinaryNode = require('oroexpressionlanguage/js/library/node/binary-node');
    var ConditionalNode = require('oroexpressionlanguage/js/library/node/conditional-node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var GetAttrNode = require('oroexpressionlanguage/js/library/node/get-attr-node');
    var NameNode = require('oroexpressionlanguage/js/library/node/name-node');
    var UnaryNode = require('oroexpressionlanguage/js/library/node/unary-node');
    var ExpressionSyntaxError = require('oroexpressionlanguage/js/library/expression-syntax-error');
    var Lexer = require('oroexpressionlanguage/js/library/lexer');
    var Parser = require('oroexpressionlanguage/js/library/parser');

    function createGetAttrNode(node, item, type) {
        return new GetAttrNode(node, new ConstantNode(item), new ArgumentsNode(), type);
    }

    describe('oroexpressionlanguage/js/library/parser', function() {
        var lexer;
        var parser;
        beforeEach(function() {
            lexer = new Lexer();
            parser = new Parser({});
        });

        describe('missing variable', function() {
            var tokenStream;
            beforeEach(function() {
                tokenStream = lexer.tokenize('foo');
            });

            it('empty names list', function() {
                expect(function() {
                    parser.parse(tokenStream);
                }).toThrowError('Variable "foo" is not valid around position 1 for expression `foo`.');
            });

            it('no `foo` variable', function() {
                expect(function() {
                    parser.parse(tokenStream, [0]);
                }).toThrowError('Variable "foo" is not valid around position 1 for expression `foo`.');
            });
        });

        describe('parse', function() {
            var cases = [
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
                        (function() {
                            var args = new ArgumentsNode();
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

            cases.forEach(function(testCase) {
                it('expression: `' + testCase[1] + '`', function() {
                    var tokenStream = lexer.tokenize(testCase[1]);
                    var names = testCase[2] || {};
                    var ast = testCase[0];
                    expect(parser.parse(tokenStream, names)).toEqual(ast);
                });
            });
        });

        describe('invalid postfix data', function() {
            var cases = [
                ['foo."#"', ['foo']],
                ['foo."bar"', ['foo']],
                ['foo.**', ['foo']],
                ['foo.123', ['foo']]
            ];

            cases.forEach(function(testCase) {
                it('expression: `' + testCase[0] + '`', function() {
                    var tokenStream = lexer.tokenize(testCase[0]);
                    var names = testCase[1];
                    expect(function() {
                        parser.parse(tokenStream, names);
                    }).toThrowError(ExpressionSyntaxError);
                });
            });
        });
    });
});
