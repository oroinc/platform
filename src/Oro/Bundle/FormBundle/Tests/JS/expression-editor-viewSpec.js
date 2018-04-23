define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var ExpressionEditorView = require('oroform/js/app/views/expression-editor-view');
    var ExpressionEditorUtil = require('oroform/js/expression-editor-util');
    var DataProviderMock = require('./Fixture/entity-structure-data-provider-mock.js');

    // fixtures
    var entitiesData = JSON.parse(require('text!./Fixture/entities-data.json'));
    var html = require('text!./Fixture/expression-editor-template.html');
    var dataSource = require('text!./Fixture/data-source.html');

    // variables
    var expressionEditorView = null;
    var typeahead = null;

    require('jasmine-jquery');

    function createEditorOptions(customOptions) {
        var utilOptions = _.result(customOptions, 'util');
        var viewOptions = _.omit(customOptions, 'util');
        _.defaults(viewOptions, {
            autoRender: true,
            el: '#expression-editor',
            dataSource: {
                pricelist: dataSource
            }
        });
        if (utilOptions !== null) {
            viewOptions.util = new ExpressionEditorUtil(_.defaults(utilOptions || {}, {
                entityDataProvider: new DataProviderMock(entitiesData),
                dataSourceNames: ['pricelist'],
                supportedNames: ['pricelist', 'product'],
                itemLevelLimit: 3
            }));
        }
        return viewOptions;
    }

    describe('oroform/js/app/views/expression-editor-view', function() {
        beforeEach(function() {
            window.setFixtures(html);
        });

        afterEach(function() {
            expressionEditorView.dispose();
        });

        describe('check initialization', function() {
            beforeEach(function() {
                var options = createEditorOptions();
                expressionEditorView = new ExpressionEditorView(options);
                typeahead = expressionEditorView.typeahead;
            });

            it('view is defined and instance of BaseView', function() {
                expect(expressionEditorView).toEqual(jasmine.any(BaseView));
            });

            it('view throw an exception when util options is missed', function() {
                expect(function() {
                    var options = createEditorOptions({util: null});
                    expressionEditorView = new ExpressionEditorView(options);
                }).toThrowError();
            });
        });

        describe('check autocomplete logic', function() {
            beforeEach(function() {
                var options = createEditorOptions();
                expressionEditorView = new ExpressionEditorView(options);
                typeahead = expressionEditorView.typeahead;
            });

            it('chain select', function() {
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

            it('check suggested items for "product.category."', function() {
                expressionEditorView.el.value = 'product.category.';
                typeahead.lookup();

                expect(typeahead.source()).toEqual([
                    'id',
                    'updatedAt'
                ]);
            });

            it('check suggested items if previous item is entity or scalar(not operation)', function() {
                var values = ['product.featured', '1', '1 in [1,2,3]', '(1 == 1)'];
                _.each(values, function(value) {
                    expressionEditorView.el.value = value + ' ';
                    expressionEditorView.el.selectionStart = expressionEditorView.el.value.length;
                    typeahead.lookup();

                    expect(typeahead.source()).toContain('!=');
                });
            });

            it('check suggested items if previous item is operation', function() {
                var values = ['', '+', '(1 =='];
                _.each(values, function(value) {
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

        describe('check value update after inserting selected value', function() {
            beforeEach(function() {
                var options = createEditorOptions();
                expressionEditorView = new ExpressionEditorView(options);
                typeahead = expressionEditorView.typeahead;
            });

            it('inserting in the field start', function() {
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

        describe('check data source render', function() {
            beforeEach(function() {
                var options = createEditorOptions();
                expressionEditorView = new ExpressionEditorView(options);
                typeahead = expressionEditorView.typeahead;
            });

            it('shown if type pricel', function() {
                expressionEditorView.el.value = 'pricel';
                typeahead.lookup();
                typeahead.select();
                var $dataSource = expressionEditorView.getDataSource('pricelist').$widget;

                expect($dataSource.is(':visible')).toBeTruthy();

                expressionEditorView.el.value = 'pricelist[1].id + product.id';
                expressionEditorView.el.selectionStart = 27;
                typeahead.lookup();

                expect($dataSource.is(':visible')).toBeFalsy();
            });
        });

        describe('when allowed operations configured', function() {
            it('only math operations is accessible', function() {
                var options = createEditorOptions({
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

            it('only equality and compare operations are accessible', function() {
                var options = createEditorOptions({
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
});
