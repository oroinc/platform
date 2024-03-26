import BinaryNode from 'oroexpressionlanguage/js/library/node/binary-node';
import ArrayNode from 'oroexpressionlanguage/js/library/node/array-node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import Compiler from 'oroexpressionlanguage/js/library/compiler';

describe('oroexpressionlanguage/js/library/node/binary-node', () => {
    let compiler;
    const suites = [
        {
            name: 'logical',
            cases: [
                [true, 'or', false, '(true || false)', true],
                [true, '||', false, '(true || false)', true],
                [true, 'and', false, '(true && false)', false],
                [true, '&&', false, '(true && false)', false]
            ]
        },
        {
            name: 'bitwise',
            cases: [
                [2, '&', 4, '(2 & 4)', 0],
                [2, '|', 4, '(2 | 4)', 6],
                [2, '^', 4, '(2 ^ 4)', 6]
            ]
        },
        {
            name: 'comparison',
            cases: [
                [1, '<', 2, '(1 < 2)', true],
                [1, '>', 2, '(1 > 2)', false],
                [1, '<=', 2, '(1 <= 2)', true],
                [1, '>=', 2, '(1 >= 2)', false],
                [1, '<=', 1, '(1 <= 1)', true],
                [1, '>=', 1, '(1 >= 1)', true],
                [true, '===', true, '(true === true)', true],
                [true, '!==', true, '(true !== true)', false],
                [2, '==', 1, '(2 == 1)', false],
                [2, '!=', 1, '(2 != 1)', true]
            ]
        },
        {
            name: 'arithmetic',
            cases: [
                [1, '-', 2, '(1 - 2)', -1],
                [1, '+', 2, '(1 + 2)', 3],
                [2, '*', 2, '(2 * 2)', 4],
                [2, '/', 2, '(2 / 2)', 1],
                [5, '%', 2, '(5 % 2)', 1],
                [5, '**', 2, 'Math.pow(5, 2)', 25]
            ]
        },
        {
            name: 'string',
            cases: [
                ['a', '~', 'b', '("a" + "b")', 'ab']
            ]
        },
        {
            name: 'match',
            cases: [
                ['abc', 'matches', '/^[a-z]+$/i', '/^[a-z]+$/i.test("abc")', true]
            ]
        }
    ];

    beforeEach(() => {
        compiler = new Compiler({});
    });

    suites.forEach(suiteData => {
        describe(suiteData.name + ' operations', () => {
            suiteData.cases.forEach(testCase => {
                describe(testCase[1], () => {
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
    });

    describe('.. operation', () => {
        let node;
        beforeEach(() => {
            const left = new ConstantNode(1);
            const right = new ConstantNode(3);
            node = new BinaryNode('..', left, right);
        });

        it('evaluation', () => {
            expect(node.evaluate({}, {})).toEqual([1, 2, 3]);
        });

        it('compilation', () => {
            node.compile(compiler);
            expect(compiler.getSource()).toEqual(jasmine.stringMatching(/^\(function\([\w\W]+\}\)\(1, 3\)$/));
        });
    });

    describe('in operations', () => {
        const cases = [
            ['a', 'in', '(Object.values({0: "a", 1: "b"}).indexOf("a") !== -1)', true],
            ['c', 'in', '(Object.values({0: "a", 1: "b"}).indexOf("c") !== -1)', false],
            ['c', 'not in', '(Object.values({0: "a", 1: "b"}).indexOf("c") === -1)', true],
            ['a', 'not in', '(Object.values({0: "a", 1: "b"}).indexOf("a") === -1)', false]
        ];

        cases.forEach(testCase => {
            describe(testCase[1] + ' (' + (testCase[3] ? 'truthy' : 'falsy') + ' result)', () => {
                let node;
                beforeEach(() => {
                    const left = new ConstantNode(testCase[0]);
                    const array = new ArrayNode();
                    array.addElement(new ConstantNode('a'));
                    array.addElement(new ConstantNode('b'));
                    node = new BinaryNode(testCase[1], left, array);
                });

                it('evaluation', () => {
                    expect(node.evaluate({}, {})).toEqual(testCase[3]);
                });

                it('compilation', () => {
                    node.compile(compiler);
                    expect(compiler.getSource()).toBe(testCase[2]);
                });
            });
        });
    });
});
