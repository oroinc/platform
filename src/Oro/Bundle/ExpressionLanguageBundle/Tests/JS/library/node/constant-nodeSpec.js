define(function(require) {
    'use strict';

    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');

    describe('oroexpressionlanguage/js/library/node/constant-node', function() {
        it('compilation', function() {
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

        // @todo test has to be turned on once Compiler is complete
        /*it('evaluation', function() {
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
        });*/
    });
});
