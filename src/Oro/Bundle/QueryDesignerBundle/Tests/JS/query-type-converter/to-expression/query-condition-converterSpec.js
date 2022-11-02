import QueryConditionConverterToExpression
    from 'oroquerydesigner/js/query-type-converter/to-expression/query-condition-converter';
import {ConstantNode} from 'oroexpressionlanguage/js/expression-language-library';
import 'lib/jasmine-oro';

describe('oroquerydesigner/js/query-type-converter/to-expression/query-condition-converter', () => {
    let converterToExpression;

    beforeEach(() => {
        const translatorMock = jasmine.createSpyObj('translator', ['tryToTranslate']);

        translatorMock.tryToTranslate.and.callFake(obj => 'constant' in obj ? new ConstantNode(obj.constant) : null);

        converterToExpression = new QueryConditionConverterToExpression([
            translatorMock
        ]);
    });

    describe('can not convert condition value', () => {
        const cases = {
            'unknown condition operation': [
                [{constant: true}, 'NOR', {constant: 0}]
            ],
            'logical operator has to be in upper case ': [
                [{constant: true}, 'And', {constant: 0}]
            ],
            'unknown condition item': [
                [{name: 'foo'}]
            ],
            'invalid length of condition operands': [
                [{constant: true}, 'AND', {constant: 0}, 'OR']
            ],
            'invalid sequence of condition operands': [
                [{constant: true}, {constant: 0}, 'AND']
            ],
            'empty condition item': [
                [{constant: true}, 'AND', {}]
            ],
            'empty condition group': [
                [{constant: true}, 'AND', []]
            ],
            'root item of condition has to be array': [
                'AND'
            ]
        };

        jasmine.itEachCase(cases, condition => {
            expect(converterToExpression.convert(condition)).toBe(void 0);
        });
    });

    describe('convert condition value', () => {
        const cases = {
            'empty condition': [
                [],
                ''
            ],
            'single condition': [
                [{constant: 'bar'}],
                '"bar"'
            ],
            'several conditions': [
                [{constant: 'bar'}, 'AND', {constant: true}, 'OR', {constant: 42}],
                '"bar" and true or 42'
            ],
            'tested conditions': [
                [{constant: 'bar'}, 'AND', [{constant: true}, 'OR', {constant: 42}]],
                '"bar" and (true or 42)'
            ]
        };

        jasmine.itEachCase(cases, (condition, expression) => {
            expect(converterToExpression.convert(condition)).toBe(expression);
        });
    });
});
