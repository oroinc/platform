define(function(require) {
    'use strict';

    var GetAttrNode = require('oroexpressionlanguage/js/library/node/get-attr-node');
    var NameNode = require('oroexpressionlanguage/js/library/node/name-node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var ArrayNode = require('oroexpressionlanguage/js/library/node/array-node');
    var Compiler = require('oroexpressionlanguage/js/library/compiler');

    function Obj() {
        this.foo = 'bar';
        this.quz = function() {
            return 'baz';
        };
    }

    describe('oroexpressionlanguage/js/library/node/get-attr-node', function() {
        var argsNode;
        var compiler;

        beforeEach(function() {
            compiler = new Compiler({});
            argsNode = new ArrayNode();
            argsNode.addElement(new ConstantNode('a'), new ConstantNode('b'));
            argsNode.addElement(new ConstantNode('b'));
        });

        describe('array call by numeric index', function() {
            var node;
            beforeEach(function() {
                node = new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode(0),
                    argsNode,
                    GetAttrNode.ARRAY_CALL
                );
            });

            it('evaluation', function() {
                expect(node.evaluate({}, {foo: {b: 'a', 0: 'b'}})).toEqual('b');
            });

            it('compilation', function() {
                node.compile(compiler);
                expect(compiler.getSource()).toBe('foo[0]');
            });
        });

        describe('array call by string index', function() {
            var node;
            beforeEach(function() {
                node = new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('b'),
                    argsNode,
                    GetAttrNode.ARRAY_CALL
                );
            });

            it('evaluation', function() {
                expect(node.evaluate({}, {foo: {b: 'a', 0: 'b'}})).toEqual('a');
            });

            it('compilation', function() {
                node.compile(compiler);
                expect(compiler.getSource()).toBe('foo["b"]');
            });
        });

        describe('property call', function() {
            var node;
            beforeEach(function() {
                node = new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('foo'),
                    argsNode,
                    GetAttrNode.PROPERTY_CALL
                );
            });

            it('evaluation', function() {
                expect(node.evaluate({}, {foo: new Obj()})).toEqual('bar');
            });

            it('compilation', function() {
                node.compile(compiler);
                expect(compiler.getSource()).toBe('foo.foo');
            });
        });

        describe('method call', function() {
            var node;
            beforeEach(function() {
                node = new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('quz'),
                    argsNode,
                    GetAttrNode.METHOD_CALL
                );
            });

            it('evaluation', function() {
                expect(node.evaluate({}, {foo: new Obj()})).toEqual('baz');
            });

            it('compilation', function() {
                node.compile(compiler);
                expect(compiler.getSource()).toBe('foo.quz({"b": "a", 0: "b"})');
            });
        });

        describe('property call by variable name', function() {
            var node;
            beforeEach(function() {
                node = new GetAttrNode(
                    new NameNode('foo'),
                    new NameNode('index'),
                    argsNode,
                    GetAttrNode.ARRAY_CALL
                );
            });

            it('evaluation', function() {
                expect(node.evaluate({}, {foo: {b: 'a', 0: 'b'}, index: 'b'})).toEqual('a');
            });

            it('compilation', function() {
                node.compile(compiler);
                expect(compiler.getSource()).toBe('foo[index]');
            });
        });
    });
});
