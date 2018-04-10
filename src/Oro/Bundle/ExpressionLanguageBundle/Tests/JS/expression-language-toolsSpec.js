define(function(require) {
    'use strict';

    var _ = require('underscore');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var ArrayNode = ExpressionLanguageLibrary.ArrayNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var FunctionNode = ExpressionLanguageLibrary.FunctionNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;
    var Node = ExpressionLanguageLibrary.Node;

    describe('oroexpressionlanguage/js/expression-language-tools', function() {
        describe('isIndexedArrayNode', function() {
            var isIndexedArrayNode = ExpressionLanguageLibrary.tools.isIndexedArrayNode;
            var cases = {
                'indexed array': [
                    (function() {
                        var arr = new ArrayNode();
                        arr.addElement(new ConstantNode('foo'));
                        arr.addElement(new ConstantNode(null));
                        arr.addElement(new ConstantNode(42));
                        arr.addElement(new ConstantNode(3.14));
                        return arr;
                    })(),
                    true
                ],
                'associative array': [
                    (function() {
                        var arr = new ArrayNode();
                        arr.addElement(new ConstantNode('foo'), new ConstantNode('text'));
                        arr.addElement(new ConstantNode(null), new ConstantNode('empty'));
                        arr.addElement(new ConstantNode(42), new ConstantNode('sense'));
                        arr.addElement(new ConstantNode(3.14), new ConstantNode('pi'));
                        return arr;
                    })(),
                    false
                ],
                'mixed array': [
                    (function() {
                        var arr = new ArrayNode();
                        arr.addElement(new ConstantNode('foo'), new ConstantNode('text'));
                        arr.addElement(new ConstantNode(null));
                        arr.addElement(new ConstantNode(42));
                        arr.addElement(new ConstantNode(3.14), new ConstantNode('pi'));
                        return arr;
                    })(),
                    false
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it(caseName, function() {
                    expect(isIndexedArrayNode(testCase[0])).toEqual(testCase[1]);
                });
            });
        });

        describe('createArrayNode', function() {
            var createArrayNode = ExpressionLanguageLibrary.tools.createArrayNode;

            var cases = {
                'indexed array': [
                    ['foo', null, 42, 3.14],
                    (function() {
                        var arr = new ArrayNode();
                        arr.addElement(new ConstantNode('foo'));
                        arr.addElement(new ConstantNode(null));
                        arr.addElement(new ConstantNode(42));
                        arr.addElement(new ConstantNode(3.14));
                        return arr;
                    })()
                ],
                'associative array': [
                    {text: 'foo', empty: null, sense: 42, pi: 3.14},
                    (function() {
                        var arr = new ArrayNode();
                        arr.addElement(new ConstantNode('foo'), new ConstantNode('text'));
                        arr.addElement(new ConstantNode(null), new ConstantNode('empty'));
                        arr.addElement(new ConstantNode(42), new ConstantNode('sense'));
                        arr.addElement(new ConstantNode(3.14), new ConstantNode('pi'));
                        return arr;
                    })()
                ],
                'mixed array': [
                    [['text', new NameNode('foo')], null, 42, [new ConstantNode('pi'), 3.14]],
                    (function() {
                        var arr = new ArrayNode();
                        arr.addElement(new NameNode('foo'), new ConstantNode('text'));
                        arr.addElement(new ConstantNode(null));
                        arr.addElement(new ConstantNode(42));
                        arr.addElement(new ConstantNode(3.14), new ConstantNode('pi'));
                        return arr;
                    })()
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it(caseName, function() {
                    expect(createArrayNode(testCase[0])).toEqual(testCase[1]);
                });
            });
        });

        describe('createGetAttrNode', function() {
            var createGetAttrNode = ExpressionLanguageLibrary.tools.createGetAttrNode;

            var cases = {
                'one element in list': [
                    ['foo'],
                    'foo',
                    new NameNode('foo')
                ],
                'two elements in list': [
                    ['foo', 'bar'],
                    'foo.bar',
                    new GetAttrNode(
                        new NameNode('foo'),
                        new ConstantNode('bar'),
                        new ArgumentsNode(),
                        GetAttrNode.PROPERTY_CALL
                    )
                ],
                'four elements in list': [
                    ['foo', 'bar', 'baz', 'qoo'],
                    'foo.bar.baz.qoo',
                    new GetAttrNode(
                        new GetAttrNode(
                            new GetAttrNode(
                                new NameNode('foo'),
                                new ConstantNode('bar'),
                                new ArgumentsNode(),
                                GetAttrNode.PROPERTY_CALL
                            ),
                            new ConstantNode('baz'),
                            new ArgumentsNode(),
                            GetAttrNode.PROPERTY_CALL
                        ),
                        new ConstantNode('qoo'),
                        new ArgumentsNode(),
                        GetAttrNode.PROPERTY_CALL
                    )
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it(caseName, function() {
                    expect(createGetAttrNode(testCase[0])).toEqual(testCase[2]);
                    expect(createGetAttrNode(testCase[1])).toEqual(testCase[2]);
                });
            });
        });

        describe('createFunctionNode', function() {
            var createFunctionNode = ExpressionLanguageLibrary.tools.createFunctionNode;

            var cases = {
                'function without arguments': [
                    ['foo'],
                    new FunctionNode('foo', new Node([]))
                ],
                'function with arguments': [
                    [
                        'foo',
                        ['bar', null, true, 3.14, new NameNode('bar')]
                    ],
                    new FunctionNode('foo', new Node([
                        new ConstantNode('bar'),
                        new ConstantNode(null),
                        new ConstantNode(true),
                        new ConstantNode(3.14),
                        new NameNode('bar')
                    ]))
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it(caseName, function() {
                    expect(createFunctionNode(testCase[0][0], testCase[0][1])).toEqual(testCase[1]);
                });
            });
        });

        describe('compareAST', function() {
            var compareAST = ExpressionLanguageLibrary.tools.compareAST;

            var cases = {
                'different types of node': [
                    // first node
                    (function() {
                        var node = new ArgumentsNode();
                        node.addElement(new ConstantNode('foo'));
                        return node;
                    })(),
                    // second node
                    (function() {
                        var node = new ArrayNode();
                        node.addElement(new ConstantNode('foo'));
                        return node;
                    })(),
                    // result
                    false
                ],
                'compare with not a node': [
                    ['foo'],
                    (function() {
                        var node = new ArrayNode();
                        node.addElement(new ConstantNode('foo'));
                        return node;
                    })(),
                    false
                ],
                'different attributes': [
                    new ConstantNode(42),
                    new ConstantNode('42'),
                    false
                ],
                'different sub-nodes': [
                    new FunctionNode('foo', new Node([
                        new ConstantNode('bar')
                    ])),
                    new FunctionNode('foo', new Node([
                        new NameNode('bar')
                    ])),
                    false
                ],
                'identical AST': [
                    new FunctionNode('foo', new Node([
                        (function() {
                            var node = new ArrayNode();
                            node.addElement(new ConstantNode('foo'));
                            return node;
                        })()
                    ])),
                    new FunctionNode('foo', new Node([
                        (function() {
                            var node = new ArrayNode();
                            node.addElement(new ConstantNode('foo'));
                            return node;
                        })()
                    ])),
                    true
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it(caseName, function() {
                    expect(compareAST(testCase[0], testCase[1])).toEqual(testCase[2]);
                });
            });
        });
    });
});
