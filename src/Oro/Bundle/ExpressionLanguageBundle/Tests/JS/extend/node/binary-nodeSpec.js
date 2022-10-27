import BinaryNode from 'oroexpressionlanguage/js/extend/node/binary-node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import Compiler from 'oroexpressionlanguage/js/library/compiler';

describe('oroexpressionlanguage/js/equality/node/binary-node', () => {
    let compiler;
    const cases = [
        [true, '=', true, '(true === true)', true],
        [true, '!=', true, '(true !== true)', false]
    ];

    beforeEach(() => {
        compiler = new Compiler({});
    });

    cases.forEach(testCase => {
        describe(`equality ${testCase[1]}`, () => {
            let node;
            beforeEach(() => {
                const left = new ConstantNode(testCase[0]);
                const right = new ConstantNode(testCase[2]);
                node = new BinaryNode(testCase[1], left, right);
            });

            it('evaluation', () => {
                expect(node.evaluate({}, {})).toEqual(testCase[4]);
            });

            it('compilation', () => {
                node.compile(compiler);
                expect(compiler.getSource()).toBe(testCase[3]);
            });
        });
    });
});
