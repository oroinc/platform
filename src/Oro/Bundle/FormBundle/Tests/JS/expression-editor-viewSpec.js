import BaseView from 'oroui/js/app/views/base/view';
import ExpressionEditorView from 'oroform/js/app/views/expression-editor-view';
import DataProviderMock from './Fixture/entity-structure-data-provider-mock.js';

// fixtures
import entitiesData from './Fixture/entities-data.json';
import html from 'text-loader!./Fixture/expression-editor-template.html';
import dataSource from 'text-loader!./Fixture/data-source.html';
// import 'jasmine-jquery';

// variables
let expressionEditorView = null;
let typeahead = null;

function createEditorOptions(customOptions) {
    return Object.assign({
        autoRender: true,
        el: '#expression-editor',
        entityDataProvider: new DataProviderMock(entitiesData),
        dataSource: {
            pricelist: dataSource
        },
        rootEntities: ['pricelist', 'product']
    }, customOptions);
}

describe('oroform/js/app/views/expression-editor-view', () => {
    beforeEach(() => {
        window.setFixtures(html);
        const options = createEditorOptions({itemLevelLimit: 3});
        expressionEditorView = new ExpressionEditorView(options);
        typeahead = expressionEditorView.typeahead;
    });

    afterEach(() => {
        expressionEditorView.dispose();
        expressionEditorView = null;
        typeahead = null;
    });

    describe('check initialization', () => {
        it('view is defined and instance of BaseView', () => {
            expect(expressionEditorView).toEqual(jasmine.any(BaseView));
        });
        it('util throw an exeption when required options is missed', () => {
            expect(() => {
                const options = createEditorOptions({entityDataProvider: null});
                expressionEditorView = new ExpressionEditorView(options);
            }).toThrowError();
        });
        it('util throw an exeption when "itemLevelLimit" option is too small', () => {
            expect(() => {
                const options = createEditorOptions({itemLevelLimit: 1});
                expressionEditorView = new ExpressionEditorView(options);
            }).toThrowError();
        });
    });

    describe('check autocomplete logic', () => {
        it('chain select', () => {
            expressionEditorView.el.value = 'pro';
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.');
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.brand.');
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.brand.id ');
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.brand.id != ');
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.brand.id != pricelist[].');
        });

        it('check suggested items for "product.category."', () => {
            expressionEditorView.el.value = 'product.category.';
            typeahead.lookup();

            expect(typeahead.source()).toEqual([
                'id',
                'updatedAt'
            ]);
        });

        it('check suggested items if previous item is entity or scalar(not operation)', () => {
            const values = ['product.featured', '1', '1 in [1,2,3]', '(1 == 1)'];
            values.forEach(value => {
                expressionEditorView.el.value = value + ' ';
                expressionEditorView.el.selectionStart = expressionEditorView.el.value.length;
                typeahead.lookup();

                expect(typeahead.source()).toContain('!=');
            });
        });

        it('check suggested items if previous item is operation', () => {
            const values = ['', '+', '(1 =='];
            values.forEach(value => {
                expressionEditorView.el.value = value + ' ';
                expressionEditorView.el.selectionStart = expressionEditorView.el.value.length;
                typeahead.lookup();

                expect(typeahead.source()).toEqual([
                    'pricelist',
                    'product'
                ]);
            });
        });
    });

    describe('check value update after inserting selected value', () => {
        it('inserting in the field start', () => {
            expressionEditorView.el.value = 'pro';
            expressionEditorView.el.selectionStart = 2;
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.');

            expressionEditorView.el.value = 'product. == 10';
            expressionEditorView.el.selectionStart = 8;
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.brand. == 10');

            expressionEditorView.el.value = 'product.id !';
            expressionEditorView.el.selectionStart = 12;
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.id != ');
        });
    });

    describe('check data source render', () => {
        it('shown if type pricel', () => {
            expressionEditorView.el.value = 'pricel';
            typeahead.lookup();
            typeahead.select();
            const $dataSource = expressionEditorView.getDataSource('pricelist').$widget;

            expect($dataSource.is(':visible')).toBeTruthy();

            expressionEditorView.el.value = 'pricelist[1].id + product.id';
            expressionEditorView.el.selectionStart = 27;
            typeahead.lookup();

            expect($dataSource.is(':visible')).toBeFalsy();
        });
    });
});

describe('oroform/js/app/views/expression-editor-view', () => {
    describe('when limit is `2`', () => {
        beforeEach(() => {
            window.setFixtures(html);
            const options = createEditorOptions({itemLevelLimit: 2});
            expressionEditorView = new ExpressionEditorView(options);
            typeahead = expressionEditorView.typeahead;
        });

        afterEach(() => {
            expressionEditorView.dispose();
            expressionEditorView = null;
            typeahead = null;
        });
        it('second level is present', () => {
            expressionEditorView.el.value = 'pro';
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.');
            expect(typeahead.source()).toEqual([
                'id',
                'status'
            ]);
        });

        it('third level is missed', () => {
            expressionEditorView.el.value = 'pro';
            typeahead.lookup();
            expect(typeahead.source()).toContain('product');
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.');
            expect(typeahead.source()).toContain('id');
            typeahead.select();
            expect(typeahead.source()).toContain('!=');
        });
    });

    describe('when limit is `4`', () => {
        beforeEach(() => {
            window.setFixtures(html);
            const options = createEditorOptions({itemLevelLimit: 4});
            expressionEditorView = new ExpressionEditorView(options);
            typeahead = expressionEditorView.typeahead;
        });

        afterEach(() => {
            expressionEditorView.dispose();
            expressionEditorView = null;
            typeahead = null;
        });

        it('fourth level is present', () => {
            expressionEditorView.el.value = 'pro';
            typeahead.lookup();
            expect(typeahead.source()).toContain('product');
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.');
            expressionEditorView.el.value += 'cat';
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.category.');
            expressionEditorView.el.value += 'par';
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.category.parentCategory.');
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.category.parentCategory.id ');
        });

        it('fifth level is missed', () => {
            expressionEditorView.el.value = 'pro';
            typeahead.lookup();
            expect(typeahead.source()).toContain('product');
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.');
            expressionEditorView.el.value += 'cat';
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.category.');
            expressionEditorView.el.value += 'par';
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.category.parentCategory.');
            expect(typeahead.source()).not.toContain('parentCategory');
        });
    });

    describe('when allowed operations configured', () => {
        beforeEach(() => {
            window.setFixtures(html);
        });

        afterEach(() => {
            expressionEditorView.dispose();
            expressionEditorView = null;
            typeahead = null;
        });

        it('only math operations is accessible', () => {
            const options = createEditorOptions({
                allowedOperations: ['math'],
                itemLevelLimit: 2
            });
            expressionEditorView = new ExpressionEditorView(options);
            typeahead = expressionEditorView.typeahead;
            expressionEditorView.el.value = 'pro';
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.');
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.id ');
            expect(typeahead.source()).toContain('+');
            expect(typeahead.source()).not.toContain('!=');
            expect(typeahead.source()).not.toContain('and');
            expect(typeahead.source()).not.toContain('match');
        });

        it('only equality and compare operations are accessible', () => {
            const options = createEditorOptions({
                allowedOperations: ['equality', 'compare'],
                itemLevelLimit: 2
            });
            expressionEditorView = new ExpressionEditorView(options);
            typeahead = expressionEditorView.typeahead;
            expressionEditorView.el.value = 'pro';
            typeahead.lookup();
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.');
            typeahead.select();
            expect(expressionEditorView.el.value).toEqual('product.id ');
            expect(typeahead.source()).toContain('<');
            expect(typeahead.source()).toContain('!=');
            expect(typeahead.source()).not.toContain('+');
        });
    });
});
