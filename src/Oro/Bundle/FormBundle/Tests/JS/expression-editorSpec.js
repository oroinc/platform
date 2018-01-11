define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var ExpressionEditorView = require('oroform/js/app/views/expression-editor-view');
    var DataProviderMock = require('./Fixture/entity-structure-data-provider-mock.js');

    //fixtures
    var entitiesData = JSON.parse(require('text!./Fixture/entities-data.json'));
    var html = require('text!./Fixture/expression-editor-template.html');
    var dataSource = require('text!./Fixture/data-source.html');

    //variables
    var expressionEditorView = null;
    var typeahead = null;

    require('jasmine-jquery');

    function createEditorOptions(customOptions) {
        return _.defaults(customOptions, {
            autoRender: true,
            el: '#expression-editor',
            entityDataProvider: new DataProviderMock(entitiesData),
            dataSource: {
                pricelist: dataSource
            },
            entities: {
                root_entities: ['pricelist', 'product']
            }
        });
    }

    describe('oroform/js/app/views/expression-editor-view', function() {

        beforeEach(function() {
            window.setFixtures(html);
            var options = createEditorOptions({itemLevelLimit: 3});
            expressionEditorView = new ExpressionEditorView(options);
            typeahead = expressionEditorView.typeahead;
        });

        afterEach(function() {
            expressionEditorView.dispose();
            expressionEditorView = null;
            typeahead = null;
        });

        describe('check initialization', function() {
            it('view is defined and instance of BaseView', function() {
                expect(expressionEditorView).toEqual(jasmine.any(BaseView));
            });
            it('util throw an exeption when required options is missed', function() {
                expect(function() {
                    var options = createEditorOptions({entityDataProvider: null});
                    expressionEditorView = new ExpressionEditorView(options);
                }).toThrowError();
            });
            it('util throw an exeption when "itemLevelLimit" option is too small', function() {
                expect(function() {
                    var options = createEditorOptions({itemLevelLimit: 1});
                    expressionEditorView = new ExpressionEditorView(options);
                }).toThrowError();
            });
        });

        describe('check rule editor validation', function() {
            var checks = {
                'product': false,
                'product.': false,
                'product.id': true,
                'product.category.id': true,
                'product.id == 1.234': true,
                'product.id in [1, 2, 3, 4, 5]': true,
                'product.status matches test': false,
                'product.status matches "test"': true,
                'product.id matches "test"': false,
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

            _.each(checks, function(result, check) {
                it('should' + (!result ? ' not' : '') + ' be valid when "' + check + '"', function() {
                    expect(expressionEditorView.util.validate(check)).toEqual(result);
                });
            });
        });

        describe('check autocomplete logic', function() {
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
    });

    describe('oroform/js/app/views/expression-editor-view', function() {
        describe('when limit is `2`', function() {
            beforeEach(function() {
                window.setFixtures(html);
                var options = createEditorOptions({itemLevelLimit: 2});
                expressionEditorView = new ExpressionEditorView(options);
                typeahead = expressionEditorView.typeahead;
            });

            afterEach(function() {
                expressionEditorView.dispose();
                expressionEditorView = null;
                typeahead = null;
            });
            it('second level is present', function() {
                expressionEditorView.el.value = 'pro';
                typeahead.lookup();
                typeahead.select();
                expect(expressionEditorView.el.value).toEqual('product.');
                expect(typeahead.source()).toEqual([
                    'id',
                    'status'
                ]);
            });

            it('third level is missed', function() {
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

        describe('when limit is `4`', function() {
            beforeEach(function() {
                window.setFixtures(html);
                var options = createEditorOptions({itemLevelLimit: 4});
                expressionEditorView = new ExpressionEditorView(options);
                typeahead = expressionEditorView.typeahead;
            });

            afterEach(function() {
                expressionEditorView.dispose();
                expressionEditorView = null;
                typeahead = null;
            });
            it('fourth level is present', function() {
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

            it('fifth level is missed', function() {
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
        describe('when allowed operations configured', function() {
            beforeEach(function() {
                window.setFixtures(html);
            });

            afterEach(function() {
                expressionEditorView.dispose();
                expressionEditorView = null;
                typeahead = null;
            });
            it('only math operations is accessible', function() {
                var options = createEditorOptions({
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
            it('only equality and compare operations are accessible', function() {
                var options = createEditorOptions({
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
});
