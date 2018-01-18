define(function(require) {
    'use strict';

    var UnaryNode = require('oroexpressionlanguage/js/library/node/unary-node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var Compiler = require('oroexpressionlanguage/js/library/compiler');

    describe('oroexpressionlanguage/js/library/node/unary-node', function() {
        var compiler;

        beforeEach(function() {
            compiler = new Compiler({});
        });

        describe('negative', function() {
            var node;
            beforeEach(function() {
                node = new UnaryNode('-', new ConstantNode(1));
            });

            it('evaluation', function() {
                expect(node.evaluate({}, {})).toEqual(-1);
            });

            it('compilation', function() {
                node.compile(compiler);
                expect(compiler.getSource()).toBe('(-1)');
            });
        });

        describe('positive', function() {
            var node;
            beforeEach(function() {
                node = new UnaryNode('+', new ConstantNode(3));
            });

            it('evaluation', function() {
                expect(node.evaluate({}, {})).toEqual(3);
            });

            it('compilation', function() {
                node.compile(compiler);
                expect(compiler.getSource()).toBe('(+3)');
            });
        });

        describe('negation over !', function() {
            var node;
            beforeEach(function() {
                node = new UnaryNode('!', new ConstantNode(true));
            });

            it('evaluation', function() {
                expect(node.evaluate({}, {})).toEqual(false);
            });

            it('compilation', function() {
                node.compile(compiler);
                expect(compiler.getSource()).toBe('(!true)');
            });
        });

        describe('negation over "not"', function() {
            var node;
            beforeEach(function() {
                node = new UnaryNode('not', new ConstantNode(true));
            });

            it('evaluation', function() {
                expect(node.evaluate({}, {})).toEqual(false);
            });

            it('compilation', function() {
                node.compile(compiler);
                expect(compiler.getSource()).toBe('(!true)');
            });
        });
    });
});
