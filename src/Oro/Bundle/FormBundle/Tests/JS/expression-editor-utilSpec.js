import Token from 'oroexpressionlanguage/js/extend/token';
import ExpressionEditorUtil from 'oroform/js/expression-editor-util';
import DataProviderMock from './Fixture/entity-structure-data-provider-mock';

// fixtures
import entitiesData from './Fixture/entities-data.json';

function createUtilOptions(customOptions) {
    return Object.assign(customOptions, {
        entityDataProvider: new DataProviderMock(entitiesData),
        dataSourceNames: ['pricelist'],
        rootEntities: ['pricelist', 'product']
    }, customOptions);
}

describe('oroform/js/expression-editor-util', () => {
    let expressionEditorUtil = null;

    describe('with default field level limit', () => {
        beforeEach(() => {
            const options = createUtilOptions({itemLevelLimit: 3});
            expressionEditorUtil = new ExpressionEditorUtil(options);
        });

        afterEach(() => {
            expressionEditorUtil = null;
        });

        describe('has to make correct validation', () => {
            const checks = {
                'product': false,
                'product.': false,
                'product.id': true,
                'product.category.id': true,
                'product.id == 1.234': true,
                'product.id in [1, 2, 3, 4, 5]': true,
                'product.status matches test': false,
                'product.status matches "test"': true,
                'product.id matches "test"': true,
                'product.id not in [1, 2, 3, 4, 5]': true,
                'product.id == product.id': true,
                'product.id != product.id': true,
                'product.id > product.id': true,
                'product.id < product.id': true,
                'someStr == 4': false,
                'product.someStr == 4': false,
                '(product.id == 5 and product.id == 10(': false,
                '(product.id == 5 and product.id == 10()': false,
                '(product.id == 5((((  and product.id == 10()': false,
                ')product.id == 5 and product.id == 10(': false,
                '(product.id == 5() and product.id == 10)': false,
                '{product.id == 5 and product.id == 10}': false,
                '(product.id == 5 and product.id == 10) or (product.status in ["status1", "status2"])': true,
                'pricelist': false,
                'pricelist.': false,
                'pricelist.id': false,
                'pricelist[]': false,
                'pricelist[].': false,
                'pricelist[].id': false,
                'pricelist[1]': false,
                'pricelist[1].': false,
                'pricelist[1].id': true,
                'pricelist[1].prices.value == 1.234': true,
                'window.category = {id: 1}; true and category.id': false,
                '"1string" == \'string\'': true,
                '"2string\\" == \'string\'': false,
                '"3string\\\\" == \'string\'': true,
                '"4string" == \'string\\\'': false,
                '"5string" == \'string\\\\\'': true,
                '"6str\\"ing" == \'st\\\'ring\'': true
            };

            Object.entries(checks).forEach(([expression, result]) => {
                it(`of${(!result ? ' in' : '')} valid expression \`${expression}\``, () => {
                    expect(expressionEditorUtil.validate(expression)).toEqual(result);
                });
            });
        });

        describe('has recognize all member of field chain correctly', () => {
            let fieldChain;
            let tokens;

            it('in expression `foo.`', () => {
                tokens = [
                    new Token(Token.NAME_TYPE, 'foo', 1, 3),
                    new Token(Token.PUNCTUATION_TYPE, '.', 4),
                    new Token(Token.EOF_TYPE, null, 5)
                ];
                for (let i = 0; i <= 1; i++) {
                    fieldChain = expressionEditorUtil.findFieldChain(tokens, i, ['foo']);
                    expect(fieldChain).not.toBeNull();

                    expect(fieldChain).toEqual(jasmine.objectContaining({
                        entity: jasmine.any(Token)
                    }));
                    expect(fieldChain.entity).toEqual(jasmine.objectContaining({
                        value: 'foo'
                    }));
                    expect(fieldChain.dataSourceOpenBracket).toBeUndefined();
                    expect(fieldChain.dataSourceCloseBracket).toBeUndefined();
                    expect(fieldChain.dataSourceValue).toBeUndefined();
                    expect(fieldChain.fields.length).toBe(0);
                }
            });

            it('in expression `bar foo[1].baz.qux..`', () => {
                tokens = [
                    new Token(Token.NAME_TYPE, 'bar', 1, 3),
                    new Token(Token.NAME_TYPE, 'foo', 5, 3),
                    new Token(Token.PUNCTUATION_TYPE, '[', 8),
                    new Token(Token.NUMBER_TYPE, 1, 9),
                    new Token(Token.PUNCTUATION_TYPE, ']', 10),
                    new Token(Token.PUNCTUATION_TYPE, '.', 11),
                    new Token(Token.NAME_TYPE, 'baz', 12, 3),
                    new Token(Token.PUNCTUATION_TYPE, '.', 15),
                    new Token(Token.NAME_TYPE, 'qux', 16, 3),
                    new Token(Token.PUNCTUATION_TYPE, '.', 19),
                    new Token(Token.PUNCTUATION_TYPE, '.', 20),
                    new Token(Token.EOF_TYPE, null, 21)
                ];
                for (let i = 1; i <= 8; i++) {
                    fieldChain = expressionEditorUtil.findFieldChain(tokens, i, ['foo']);
                    expect(fieldChain).not.toBeNull();
                    expect(fieldChain).toEqual(jasmine.objectContaining({
                        entity: jasmine.any(Token),
                        dataSourceOpenBracket: jasmine.any(Token),
                        dataSourceCloseBracket: jasmine.any(Token),
                        dataSourceValue: jasmine.any(Token)
                    }));
                    expect(fieldChain.entity).toEqual(jasmine.objectContaining({
                        value: 'foo'
                    }));
                    expect(fieldChain.dataSourceValue.value).toBe(1);
                    expect(fieldChain.fields.length).toBe(2);
                }
            });

            it('in expression `bar. foo[]. .baz`', () => {
                tokens = [
                    new Token(Token.NAME_TYPE, 'bar', 1, 3),
                    new Token(Token.PUNCTUATION_TYPE, '.', 4),
                    new Token(Token.NAME_TYPE, 'foo', 6, 3),
                    new Token(Token.PUNCTUATION_TYPE, '[', 9),
                    new Token(Token.PUNCTUATION_TYPE, ']', 10),
                    new Token(Token.PUNCTUATION_TYPE, '.', 11),
                    new Token(Token.PUNCTUATION_TYPE, '.', 13),
                    new Token(Token.NAME_TYPE, 'baz', 14, 3),
                    new Token(Token.EOF_TYPE, null, 17)
                ];

                for (let i = 2; i <= 5; i++) {
                    fieldChain = expressionEditorUtil.findFieldChain(tokens, i, ['foo']);
                    expect(fieldChain).not.toBeNull();
                    expect(fieldChain).toEqual(jasmine.objectContaining({
                        entity: jasmine.any(Token),
                        dataSourceOpenBracket: jasmine.any(Token),
                        dataSourceCloseBracket: jasmine.any(Token)
                    }));
                    expect(fieldChain.entity).toEqual(jasmine.objectContaining({
                        value: 'foo'
                    }));
                    expect(fieldChain.dataSourceValue).toBeUndefined();
                    expect(fieldChain.fields.length).toBe(0);
                }
            });
        });

        describe('has find no field chain', () => {
            let fieldChain;
            let tokens;

            it('expression `bar.` since only `foo` is allowable entity name', () => {
                tokens = [
                    new Token(Token.NAME_TYPE, 'bar', 1, 2),
                    new Token(Token.PUNCTUATION_TYPE, '.', 3),
                    new Token(Token.EOF_TYPE, null, 4)
                ];

                for (let i = 0; i < tokens.length; i++) {
                    fieldChain = expressionEditorUtil.findFieldChain(tokens, i, ['foo']);
                    expect(fieldChain).toBeNull();
                }
            });

            it('in expression `bar foo[.baz` since there is unclosed bracket inside', () => {
                tokens = [
                    new Token(Token.NAME_TYPE, 'bar', 1, 3),
                    new Token(Token.NAME_TYPE, 'foo', 5, 3),
                    new Token(Token.PUNCTUATION_TYPE, '[', 4),
                    new Token(Token.PUNCTUATION_TYPE, '.', 5),
                    new Token(Token.NAME_TYPE, 'baz', 6, 3),
                    new Token(Token.EOF_TYPE, null, 9)
                ];

                for (let i = 0; i < tokens.length; i++) {
                    fieldChain = expressionEditorUtil.findFieldChain(tokens, i, ['foo']);
                    expect(fieldChain).toBeNull();
                }
            });
        });

        describe('prepare datasource options', () => {
            const cases = [
                [
                    'pricelist[]',
                    [0, 5, 9, 10, 11],
                    '',
                    11,
                    'entity name with empty brackets'
                ],
                [
                    'pricelist[7]',
                    [0, 5, 9, 10, 11, 12],
                    7,
                    12,
                    'entity name with selected value of datasource'],
                [
                    'foo pricelist[7].id',
                    [4, 10, 13, 14, 15, 16],
                    7,
                    19,
                    'entity name with selected value of datasource and selected field'
                ]
            ];

            cases.forEach(([expression, positions, dataSourceValue, newPosition, explanation]) => {
                const description = `for expression \`${expression}\` when cursor on ${explanation} at `;
                positions.forEach(position => {
                    it(description + position, () => {
                        const data = expressionEditorUtil.getAutocompleteData(expression, position);
                        expect(data).toEqual(
                            jasmine.objectContaining({
                                itemsType: 'datasource',
                                dataSourceKey: 'pricelist',
                                dataSourceValue: dataSourceValue,
                                position: newPosition
                            })
                        );
                    });
                });
            });
        });

        describe('prepare autocomplete data', () => {
            const rootEntityItems = {
                pricelist: jasmine.any(Object),
                product: jasmine.any(Object)
            };
            const operationItems = jasmine.objectContaining({'==': jasmine.any(Object)});
            const cases = [
                ['', [0], 'entities', rootEntityItems, '', 'in empty expression'],
                ['foo  bar', [4], 'entities', rootEntityItems, '', 'surrounded with spaces'],
                ['foo  + bar', [4], 'entities', rootEntityItems, '', 'surrounded with spaces'],
                ['foo * ', [6], 'entities', rootEntityItems, '',
                    'at the end of expression and previous element is an operation'],
                ['foo * ( ', [8], 'entities', rootEntityItems, '',
                    'at the end of expression and previous element is an bracket'],
                ['foo  bar', [5, 6, 7], 'entities', rootEntityItems, 'bar', 'on entity name'],
                ['foo ', [4], 'operations', operationItems, '',
                    'at the end of expression and previous element isn\'t an bracket or operation'],
                ['foo + boo', [4, 5], 'operations', operationItems, '+', 'on operation item'],
                ['5 not in [1, 2, 3]', [2, 5, 6, 7, 8], 'operations', operationItems, 'not in',
                    'on operation item'],
                ['foo + product.brand.i ', [6, 10, 13, 14, 16, 19, 20, 21], 'entities',
                    jasmine.objectContaining({id: jasmine.any(Object)}), 'i', 'on entity field item'],
                ['foo + product.brand. ', [6, 10, 13, 14, 16, 19, 20], 'entities',
                    jasmine.objectContaining({id: jasmine.any(Object)}), '', 'on entity field item'],
                ['foo + product.bar', [6, 10, 13, 14, 17], 'entities',
                    jasmine.objectContaining({brand: jasmine.any(Object)}), 'bar', 'on entity field item'],
                ['foo + product.bar. ', [6, 10, 13, 14, 17, 18], 'entities', {}, '', 'on entity field item']
            ];

            cases.forEach(([expression, positions, itemsType, items, query, explanation]) => {
                positions.forEach(position => {
                    const description = `for expression \`${expression}\` with ${itemsType} items, ` +
                        ` when cursor ${explanation} at ${position}`;
                    it(description, () => {
                        const data = expressionEditorUtil.getAutocompleteData(expression, position);
                        expect(data.itemsType).toBe(itemsType);
                        expect(data.query).toBe(query);
                        expect(data.items).toEqual(items);
                    });
                });
            });
        });
    });

    describe('when limit is `2`', () => {
        beforeEach(() => {
            const options = createUtilOptions({itemLevelLimit: 2});
            expressionEditorUtil = new ExpressionEditorUtil(options);
        });

        afterEach(() => {
            expressionEditorUtil = null;
        });

        it('second level is present', () => {
            const data = expressionEditorUtil.getAutocompleteData('product.', 8);
            expect(data.itemsType).toBe('entities');
            expect(data.query).toBe('');
            expect(data.items).toEqual({
                id: jasmine.any(Object),
                status: jasmine.any(Object)
            });
        });

        it('third level is missed', () => {
            const data = expressionEditorUtil.getAutocompleteData('product.category.', 17);
            expect(data.itemsType).toBe('entities');
            expect(data.query).toBe('');
            expect(data.items).toEqual({});
        });
    });

    describe('when limit is `4`', () => {
        beforeEach(() => {
            const options = createUtilOptions({itemLevelLimit: 4});
            expressionEditorUtil = new ExpressionEditorUtil(options);
        });

        afterEach(() => {
            expressionEditorUtil = null;
        });

        it('fourth level is present', () => {
            const data = expressionEditorUtil.getAutocompleteData('product.category.parentCategory.', 32);
            expect(data.itemsType).toBe('entities');
            expect(data.query).toBe('');
            expect(data.items).toEqual(jasmine.objectContaining({
                id: jasmine.any(Object)
            }));
        });

        it('fifth level is missed', () => {
            const data =
                expressionEditorUtil.getAutocompleteData('product.category.parentCategory.parentCategory.', 17);
            expect(data.itemsType).toBe('entities');
            expect(data.query).toBe('');
            expect(data.items).toEqual({});
        });
    });

    describe('when allowed operations configured', () => {
        it('only math operations are accessible', () => {
            const options = createUtilOptions({
                allowedOperations: ['math']
            });
            expressionEditorUtil = new ExpressionEditorUtil(options);
            const data = expressionEditorUtil.getAutocompleteData('product.id ', 11);
            const operations = Object.keys(data.items);
            expect(operations).toContain('+');
            expect(operations).not.toContain('!=');
            expect(operations).not.toContain('and');
            expect(operations).not.toContain('match');
        });

        it('only equality and compare operations are accessible', () => {
            const options = createUtilOptions({
                allowedOperations: ['equality', 'compare']
            });
            expressionEditorUtil = new ExpressionEditorUtil(options);
            const data = expressionEditorUtil.getAutocompleteData('product.id ', 11);
            const operations = Object.keys(data.items);
            expect(operations).toContain('<');
            expect(operations).toContain('!=');
            expect(operations).not.toContain('+');
        });
    });
});
