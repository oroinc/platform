define(function(require) {
    'use strict';

    var BinaryNode = require('oroexpressionlanguage/js/extend/node/binary-node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var Compiler = require('oroexpressionlanguage/js/library/compiler');

    describe('oroexpressionlanguage/js/equality/node/binary-node', function() {
        var compiler;
        var cases = [
            [true, '=', true, '(true === true)', true],
            [true, '!=', true, '(true !== true)', false]
        ];

        beforeEach(function() {
            compiler = new Compiler({});
        });

        cases.forEach(function(testCase) {
            describe('equality ' + testCase[1], function() {
                var node;
                beforeEach(function() {
                    var left = new ConstantNode(testCase[0]);
                    var right = new ConstantNode(testCase[2]);
                    node = new BinaryNode(testCase[1], left, right);
                });

                it('evaluation', function() {
                    expect(node.evaluate({}, {})).toEqual(testCase[4]);
                });

                it('compilation', function() {
                    node.compile(compiler);
                    expect(compiler.getSource()).toBe(testCase[3]);
                });
            });
        });
    });
});
