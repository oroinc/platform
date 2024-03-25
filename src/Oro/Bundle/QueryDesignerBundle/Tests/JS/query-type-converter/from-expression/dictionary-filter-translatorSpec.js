import DictionaryFilterTranslatorFromExpression
    from 'oroquerydesigner/js/query-type-converter/from-expression/dictionary-filter-translator';
import FieldIdTranslatorFromExpression
    from 'oroquerydesigner/js/query-type-converter/from-expression/field-id-translator';
import {BinaryNode, ConstantNode, NameNode} from 'oroexpressionlanguage/js/expression-language-library';
import {createArrayNode, createGetAttrNode} from 'oroexpressionlanguage/js/expression-language-tools';
import 'lib/jasmine-oro';

describe('oroquerydesigner/js/query-type-converter/from-expression/dictionary-filter-translator', () => {
    let translator;
    let entityStructureDataProviderMock;
    let filterConfigProviderMock;

    beforeEach(() => {
        entityStructureDataProviderMock = jasmine.combineSpyObj('entityStructureDataProvider', [
            jasmine.createSpy('getPathByRelativePropertyPath').and.returnValue('bar'),
            jasmine.createSpy('getFieldSignatureSafely'),
            jasmine.combineSpyObj('rootEntity', [
                jasmine.createSpy('get').and.returnValue('foo')
            ])
        ]);
        filterConfigProviderMock = jasmine.createSpyObj('filterConfigProvider', ['getApplicableFilterConfig']);

        translator = new DictionaryFilterTranslatorFromExpression(
            new FieldIdTranslatorFromExpression(entityStructureDataProviderMock),
            filterConfigProviderMock
        );
    });

    describe('rejects node structure because', () => {
        const cases = {
            'improper AST':
                [new ConstantNode('test')],
            'unsupported operation':
                [new BinaryNode('>=', createGetAttrNode('foo.bar'), createArrayNode([1, 2]))],
            'improper left operand AST':
                [new BinaryNode('in', new ConstantNode('test'), createArrayNode([1, 2]))],
            'improper right operand AST':
                [new BinaryNode('in', createGetAttrNode('foo.bar'), new ConstantNode('test'))],
            'improper AST values in right operand':
                [new BinaryNode('in', createGetAttrNode('foo.bar'), createArrayNode([new NameNode('foo'), 2]))]
        };

        jasmine.itEachCase(cases, ast => {
            expect(translator.tryToTranslate(ast)).toBe(null);
            expect(entityStructureDataProviderMock.getFieldSignatureSafely).not.toHaveBeenCalled();
        });
    });

    describe('valid node structure', () => {
        let node;

        beforeEach(() => {
            node = new BinaryNode('in', createGetAttrNode('foo.bar'), createArrayNode(['1', '2']));
        });

        const cases = {
            'improper filter type': [
                {type: 'number'},
                null
            ],
            'unavailable operation in filter config': [
                {
                    type: 'dictionary',
                    name: 'enum',
                    choices: [{value: '2'}, {value: '3'}]
                },
                null
            ],
            'successful translation': [
                {
                    type: 'dictionary',
                    name: 'enum',
                    choices: [{value: '1'}, {value: '2'}]
                },
                {
                    columnName: 'bar',
                    criterion: {
                        filter: 'enum',
                        data: {
                            type: '1',
                            value: ['1', '2']
                        }
                    }
                }
            ],
            'successful translation with extra params': [
                {
                    type: 'dictionary',
                    name: 'tag',
                    choices: [{value: '1'}, {value: '2'}],
                    filterParams: {'class': 'Oro\\TagClass', 'entityClass': 'Oro\\BarClass'}
                },
                {
                    columnName: 'bar',
                    criterion: {
                        filter: 'tag',
                        data: {
                            type: '1',
                            value: ['1', '2'],
                            params: {'class': 'Oro\\TagClass', 'entityClass': 'Oro\\BarClass'}
                        }
                    }
                }
            ],
            'not value are available in filter config': [
                {
                    type: 'dictionary',
                    name: 'multicurrency',
                    choices: [{value: '1'}, {value: '2'}],
                    select2ConfigData: [
                        {id: '1', value: 'UAH', text: 'Ukrainian Hryvnia (UAH)'}
                    ]
                },
                null
            ],
            'successful translation values with predefined options set in filter config': [
                {
                    type: 'dictionary',
                    name: 'multicurrency',
                    choices: [{value: '1'}, {value: '2'}],
                    select2ConfigData: [
                        {id: '1', value: 'UAH', text: 'Ukrainian Hryvnia (UAH)'},
                        {id: '2', value: 'USD', text: 'US Dollar ($)'}
                    ]
                },
                {
                    columnName: 'bar',
                    criterion: {
                        filter: 'multicurrency',
                        data: {
                            type: '1',
                            value: ['1', '2']
                        }
                    }
                }
            ]
        };

        jasmine.itEachCase(cases, (filterConfig, condition) => {
            filterConfigProviderMock.getApplicableFilterConfig.and.returnValue(filterConfig);
            expect(translator.tryToTranslate(node)).toEqual(condition);
            expect(entityStructureDataProviderMock.getFieldSignatureSafely).toHaveBeenCalledWith('bar');
        });
    });
});
