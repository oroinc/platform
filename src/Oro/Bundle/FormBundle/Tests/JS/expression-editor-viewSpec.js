import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import ExpressionEditorView from 'oroform/js/app/views/expression-editor-view';
import ExpressionEditorUtil from 'oroform/js/expression-editor-util';
import DataProviderMock from './Fixture/entity-structure-data-provider-mock.js';

// fixtures
import entitiesData from './Fixture/entities-data.json';
import html from 'text-loader!./Fixture/expression-editor-template.html';
import dataSource from 'text-loader!./Fixture/data-source.html';
import 'jasmine-jquery';

// variables
let expressionEditorView = null;
let typeahead = null;

function createEditorOptions(customOptions) {
    const utilOptions = _.result(customOptions, 'util');
    let viewOptions = _.omit(customOptions, 'util');
    viewOptions = Object.assign({
        autoRender: true,
        el: '#expression-editor',
        dataSource: {
            pricelist: dataSource
        }
    }, viewOptions);
    if (utilOptions !== null) {
        viewOptions.util = new ExpressionEditorUtil(Object.assign({
            entityDataProvider: new DataProviderMock(entitiesData),
            dataSourceNames: ['pricelist'],
            supportedNames: ['pricelist', 'product'],
            itemLevelLimit: 3
        }, utilOptions || {}));
    }
    return viewOptions;
}

describe('oroform/js/app/views/expression-editor-view', () => {
    beforeEach(() => {
        window.setFixtures(html);
    });

    afterEach(() =>{
        expressionEditorView.dispose();
    });

    describe('check initialization', () => {
        beforeEach(() => {
            const options = createEditorOptions();
            expressionEditorView = new ExpressionEditorView(options);
            typeahead = expressionEditorView.typeahead;
        });

        it('view is defined and instance of BaseView', () =>{
            expect(expressionEditorView).toEqual(jasmine.any(BaseView));
        });

        it('view throw an exception when util options is missed', () => {
            expect(() => {
                const options = createEditorOptions({util: null});
                expressionEditorView = new ExpressionEditorView(options);
            }).toThrowError();
        });
    });

    describe('check autocomplete logic', () =>{
        beforeEach(() =>{
            const options = createEditorOptions();
            expressionEditorView = new ExpressionEditorView(options);
            typeahead = expressionEditorView.typeahead;
        });

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
        beforeEach(() => {
            const options = createEditorOptions();
            expressionEditorView = new ExpressionEditorView(options);
            typeahead = expressionEditorView.typeahead;
        });

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
        beforeEach(() => {
            const options = createEditorOptions();
            expressionEditorView = new ExpressionEditorView(options);
            typeahead = expressionEditorView.typeahead;
        });

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

    describe('when allowed operations configured', () => {
        it('only math operations is accessible', () => {
            const options = createEditorOptions({
                util: {
                    allowedOperations: ['math'],
                    itemLevelLimit: 2
                }
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
                util: {
                    allowedOperations: ['equality', 'compare'],
                    itemLevelLimit: 2
                }
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
