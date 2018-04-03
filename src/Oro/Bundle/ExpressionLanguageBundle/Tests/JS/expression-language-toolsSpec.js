define(function(require) {
    'use strict';

    var _ = require('underscore');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var ArrayNode = ExpressionLanguageLibrary.ArrayNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;

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
                    new NameNode('foo')
                ],
                'two elements in list': [
                    ['foo', 'bar'],
                    new GetAttrNode(
                        new NameNode('foo'),
                        new ConstantNode('bar'),
                        new ArgumentsNode(),
                        GetAttrNode.PROPERTY_CALL
                    )
                ],
                'four elements in list': [
                    ['foo', 'bar', 'baz', 'qoo'],
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
                    expect(createGetAttrNode(testCase[0])).toEqual(testCase[1]);
                });
            });
        });
    });
});
