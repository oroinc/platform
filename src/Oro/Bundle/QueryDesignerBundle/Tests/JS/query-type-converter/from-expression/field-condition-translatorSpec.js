import FieldConditionTranslatorFromExpression
    from 'oroquerydesigner/js/query-type-converter/from-expression/field-condition-translator';
import {BinaryNode, ConstantNode} from 'oroexpressionlanguage/js/expression-language-library';
import {createGetAttrNode} from 'oroexpressionlanguage/js/expression-language-tools';
import 'lib/jasmine-oro';

const createLeftOperand = () => createGetAttrNode('foo.bar');

describe('oroquerydesigner/js/query-type-converter/from-expression/field-condition-translator', () => {
    let translator;
    let filterConfigProviderMock;
    let stringFilterTranslatorMock;
    let fieldIdTranslatorMock;
    const filterConfigs = {
        string: {
            type: 'string',
            name: 'string',
            choices: [
                {value: '1'},
                {value: '2'},
                {value: '3'}
            ]
        },
        number: {
            type: 'number',
            name: 'number',
            choices: [
                {value: '1'},
                {value: '2'},
                {value: '3'}
            ]
        }
    };

    beforeEach(() => {
        fieldIdTranslatorMock = jasmine.combineSpyObj('fieldIdTranslator', [
            jasmine.createSpy('translate').and
                .callFake(node => `${node.nodes[0].attrs.name}.${node.nodes[1].attrs.value}`)
        ]);

        fieldIdTranslatorMock.provider = jasmine.combineSpyObj('entityStructureDataProvider', [
            jasmine.createSpy('getFieldSignatureSafely').and
                .callFake(fieldId => fieldId === 'foo.bar' ? {type: 'string'} : {type: 'number'})
        ]);

        filterConfigProviderMock = jasmine.combineSpyObj('filterConfigProvider', [
            jasmine.createSpy('getApplicableFilterConfig').and
                .callFake(fieldSignature => filterConfigs[fieldSignature.type])
        ]);

        stringFilterTranslatorMock = jasmine.combineSpyObj('stringFilterTranslator', [
            jasmine.createSpy('tryToTranslate').and.returnValue(
                {condition: 'string'}
            )
        ]);

        const filterTranslatorProviderMock = jasmine.combineSpyObj('filterTranslatorProvider', [
            jasmine.createSpy('getTranslatorConstructor').and.callFake(name => {
                const filterTranslators = {
                    string: function StringFilterTranslator() {
                        return stringFilterTranslatorMock;
                    }
                };
                return filterTranslators[name] || null;
            })
        ]);

        translator = new FieldConditionTranslatorFromExpression(
            fieldIdTranslatorMock,
            filterConfigProviderMock,
            filterTranslatorProviderMock
        );
    });

    describe('inappropriate AST for field condition', () => {
        const cases = {
            'constant node': [new ConstantNode('test')],
            'complex field condition with different fields': [
                new BinaryNode(
                    'and',
                    new BinaryNode('>', new ConstantNode('start'), createLeftOperand()),
                    new BinaryNode('<', new ConstantNode('end'), createGetAttrNode('foo.que'))
                )
            ],
            'with not supported filter type': [
                new BinaryNode('=', createGetAttrNode('foo.que'), new ConstantNode('baz'))
            ]
        };

        jasmine.itEachCase(cases, ast => {
            const actualAST = translator.tryToTranslate(ast);
            expect(actualAST).toBe(null);
        });
    });

    describe('appropriate AST for field condition', () => {
        const cases = {
            'with conventional order of operands': [
                new BinaryNode('=', createLeftOperand(), new ConstantNode('baz'))
            ],
            'with unconventional order of operands': [
                new BinaryNode('=', new ConstantNode('baz'), createLeftOperand())
            ],
            'complex field condition with unconventional order of operands': [
                new BinaryNode(
                    'and',
                    new BinaryNode('>', new ConstantNode('start'), createLeftOperand()),
                    new BinaryNode('<', new ConstantNode('end'), createLeftOperand())
                )
            ]
        };

        jasmine.itEachCase(cases, ast => {
            const actualAST = translator.tryToTranslate(ast);
            expect(actualAST).not.toBe(null);
        });
    });
});
