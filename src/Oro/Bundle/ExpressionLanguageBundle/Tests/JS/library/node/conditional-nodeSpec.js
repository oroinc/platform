define(function(require) {
    'use strict';

    var ConditionalNode = require('oroexpressionlanguage/js/library/node/conditional-node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var Compiler = require('oroexpressionlanguage/js/library/compiler');

    describe('oroexpressionlanguage/js/library/node/conditional-node', function() {
        var compiler;

        beforeEach(function() {
            compiler = new Compiler({});
        });

        describe('then case', function() {
            var conditionalNode;

            beforeEach(function() {
                conditionalNode = new ConditionalNode(
                    new ConstantNode(true),
                    new ConstantNode(1),
                    new ConstantNode(2)
                );
            });

            it('evaluation', function() {
                expect(conditionalNode.evaluate()).toEqual(1);
            });

            it('compilation', function() {
                conditionalNode.compile(compiler);
                expect(compiler.getSource()).toBe('((true) ? (1) : (2))');
            });
        });

        describe('else case', function() {
            var conditionalNode;

            beforeEach(function() {
                conditionalNode = new ConditionalNode(
                    new ConstantNode(false),
                    new ConstantNode(1),
                    new ConstantNode(2)
                );
            });

            it('evaluation', function() {
                expect(conditionalNode.evaluate()).toEqual(2);
            });

            it('compilation', function() {
                conditionalNode.compile(compiler);
                expect(compiler.getSource()).toBe('((false) ? (1) : (2))');
            });
        });
    });
});
