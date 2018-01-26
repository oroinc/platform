define(function(require) {
    'use strict';

    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var Compiler = require('oroexpressionlanguage/js/library/compiler');

    describe('oroexpressionlanguage/js/library/node/constant-node', function() {
        describe('evaluation', function() {
            var cases = [
                ['false', false],
                ['true', true],
                ['null', null],
                ['3', 3],
                ['3.3', 3.3],
                ['"foo"', 'foo'],
                ['[1, "a"]', [1, 'a']],
                ['{"0": 1, "b": "a"}', {0: 1, b: 'a'}]
            ];

            cases.forEach(function(caseData) {
                it(caseData[0], function() {
                    var node = new ConstantNode(caseData[1]);
                    expect(node.evaluate()).toEqual(caseData[1]);
                });
            });
        });

        describe('compilation', function() {
            var cases = [
                ['false', false],
                ['true', true],
                ['null', null],
                ['3', 3],
                ['3.3', 3.3],
                ['"foo"', 'foo'],
                ['[1, "a"]', [1, 'a']],
                ['{"0": 1, "b": "a"}', {0: 1, b: 'a'}]
            ];

            cases.forEach(function(caseData) {
                it(caseData[0], function() {
                    var compiler = new Compiler({});
                    var node = new ConstantNode(caseData[1]);
                    node.compile(compiler);
                    expect(compiler.getSource()).toBe(caseData[0]);
                });
            });
        });
    });
});
