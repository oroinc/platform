import BooleanFilterTranslatorToExpression
    from 'oroquerydesigner/js/query-type-converter/to-expression/boolean-filter-translator';
import {BinaryNode, ConstantNode} from 'oroexpressionlanguage/js/expression-language-library';
import {createGetAttrNode} from 'oroexpressionlanguage/js/expression-language-tools';
import 'lib/jasmine-oro';

describe('oroquerydesigner/js/query-type-converter/to-expression/boolean-filter-translator', () => {
    let translator;
    const filterConfig = {
        type: 'boolean',
        name: 'boolean',
        choices: [
            {value: '1'},
            {value: '2'}
        ]
    };

    beforeEach(() => {
        translator = new BooleanFilterTranslatorToExpression(filterConfig);
    });

    describe('can not translate filter value', () => {
        const cases = {
            'when incorrect value type': [
                {value: ['1', '2']}
            ],
            'when unknown value': [
                {value: '3'}
            ]
        };

        jasmine.itEachCase(cases, filterValue => {
            expect(translator.test(filterValue)).toBe(false);
        });
    });

    describe('translate filter value', () => {
        const createLeftOperand = createGetAttrNode.bind(null, 'foo.bar');
        const cases = {
            'when field value is `yes`': [
                {value: '1'},
                new BinaryNode('=', createLeftOperand(), new ConstantNode(true))
            ],
            'when field value is `no`': [
                {value: '2'},
                new BinaryNode('=', createLeftOperand(), new ConstantNode(false))
            ]
        };

        jasmine.itEachCase(cases, (filterValue, expectedAST) => {
            const leftOperand = createLeftOperand();

            expect(translator.test(filterValue)).toBe(true);
            expect(translator.translate(leftOperand, filterValue)).toEqual(expectedAST);
        });
    });
});
