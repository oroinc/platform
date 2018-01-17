define(function(require) {
    'use strict';

    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var Compiler = require('oroexpressionlanguage/js/library/compiler');

    describe('oroexpressionlanguage/js/library/node/constant-node', function() {
        it('evaluation', function() {
            var testData = [
                [false, false],
                [true, true],
                [null, null],
                [3, 3],
                [3.3, 3.3],
                ['foo', 'foo'],
                [[1, 'a'], [1, 'a']],
                [{'0': 1, 'b': 'a'}, {'0': 1, 'b': 'a'}]
            ];
            testData.forEach(function(caseData) {
                var node = new ConstantNode(caseData[1]);
                expect(node.evaluate()).toEqual(caseData[0]);
            });
        });

        it('compilation', function() {
            var testData = [
                ['false', false],
                ['true', true],
                ['null', null],
                ['3', 3],
                ['3.3', 3.3],
                ['"foo"', 'foo'],
                ['[1, "a"]', [1, 'a']],
                ['{"0": 1, "b": "a"}', {'0': 1, 'b': 'a'}]
            ];
            testData.forEach(function(caseData) {
                var compiler = new Compiler({});
                var node = new ConstantNode(caseData[1]);
                node.compile(compiler);
                expect(compiler.getSource()).toBe(caseData[0]);
            });
        });
    });
});
