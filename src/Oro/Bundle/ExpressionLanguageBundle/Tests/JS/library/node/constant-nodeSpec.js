import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import Compiler from 'oroexpressionlanguage/js/library/compiler';

describe('oroexpressionlanguage/js/library/node/constant-node', () => {
    describe('evaluation', () => {
        const cases = [
            ['false', false],
            ['true', true],
            ['null', null],
            ['3', 3],
            ['3.3', 3.3],
            ['"foo"', 'foo'],
            ['[1, "a"]', [1, 'a']],
            ['{"0": 1, "b": "a"}', {0: 1, b: 'a'}]
        ];

        cases.forEach(caseData => {
            it(caseData[0], () => {
                const node = new ConstantNode(caseData[1]);
                expect(node.evaluate()).toEqual(caseData[1]);
            });
        });
    });

    describe('compilation', () => {
        const cases = [
            ['false', false],
            ['true', true],
            ['null', null],
            ['3', 3],
            ['3.3', 3.3],
            ['"foo"', 'foo'],
            ['[1, "a"]', [1, 'a']],
            ['{"0": 1, "b": "a"}', {0: 1, b: 'a'}]
        ];

        cases.forEach(caseData => {
            it(caseData[0], () => {
                const compiler = new Compiler({});
                const node = new ConstantNode(caseData[1]);
                node.compile(compiler);
                expect(compiler.getSource()).toBe(caseData[0]);
            });
        });
    });
});
