define(function(require) {
    'use strict';

    var $ = require('jquery');
    var Backbone = require('backbone');
    var requirejsExposure = require('requirejs-exposure');
    var markup = require('text!./Fixture/aggregated-field-condition/markup.html');
    var data = JSON.parse(require('text!./Fixture/aggregated-field-condition/entities.json'));
    var filters = JSON.parse(require('text!./Fixture/aggregated-field-condition/filters.json'));
    var columnsData = JSON.parse(require('text!./Fixture/aggregated-field-condition/columnsData.json'));
    var FieldConditionView = require('oroquerydesigner/js/app/views/field-condition-view');
    var FieldChoiceMock = require('./Fixture/field-condition/field-choice-mock');
    var AggregatedFieldConditionView = require('oroquerydesigner/js/app/views/aggregated-field-condition-view');
    require('jasmine-jquery');

    var exposure = requirejsExposure.disclose('oroquerydesigner/js/app/views/field-condition-view');

    describe('oroquerydesigner/js/app/views/aggregated-field-condition-view', function() {
        var aggregatedFieldConditionView;

        describe('without initial value', function() {
            var columnsCollection;
            beforeEach(function(done) {
                FieldChoiceMock.setData(data);
                exposure.substitute('FieldChoiceView').by(FieldChoiceMock);
                window.setFixtures(markup);
                columnsCollection = new Backbone.Collection(columnsData);
                aggregatedFieldConditionView = new AggregatedFieldConditionView({
                    autoRender: true,
                    filters: filters,
                    columnsCollection: columnsCollection,
                    fieldChoice: {
                        select2: {
                            formatSelectionTemplateSelector: '#format-selection-template'
                        },
                        entity: 'Oro\\Bundle\\AccountBundle\\Entity\\Account'
                    }
                });
                window.setFixtures(aggregatedFieldConditionView.$el);
                $.when(aggregatedFieldConditionView.deferredRender).then(function() {
                    done();
                });
            });

            afterEach(function() {
                aggregatedFieldConditionView.dispose();
                exposure.recover('FieldChoiceView');
                delete FieldChoiceMock.lastCreatedInstance;
            });

            it('is instance of FieldConditionView', function() {
                expect(aggregatedFieldConditionView).toEqual(jasmine.any(FieldConditionView));
            });
        });

        describe('with initial value', function() {
            var columnsCollection;
            var initialValue = {
                columnName: 'id',
                criterion: {
                    filter: 'number',
                    data: {
                        value: 1,
                        type: '3',
                        params: {
                            filter_by_having: true
                        }
                    }
                },
                func: {
                    name: 'Count',
                    group_type: 'aggregates',
                    group_name: 'number'
                },
                criteria: 'aggregated-condition-item'
            };

            beforeEach(function(done) {
                FieldChoiceMock.setData(data);
                exposure.substitute('FieldChoiceView').by(FieldChoiceMock);
                window.setFixtures(markup);
                columnsCollection = new Backbone.Collection(columnsData);
                aggregatedFieldConditionView = new AggregatedFieldConditionView({
                    autoRender: true,
                    filters: filters,
                    columnsCollection: columnsCollection,
                    fieldChoice: {
                        select2: {
                            formatSelectionTemplateSelector: '#format-selection-template'
                        },
                        entity: 'Oro\\Bundle\\AccountBundle\\Entity\\Account'
                    },
                    value: initialValue
                });
                window.setFixtures(aggregatedFieldConditionView.$el);
                $.when(aggregatedFieldConditionView.deferredRender).then(function() {
                    done();
                });
            });

            afterEach(function() {
                aggregatedFieldConditionView.dispose();
                exposure.recover('FieldChoiceView');
                delete FieldChoiceMock.lastCreatedInstance;
            });

            it('shows a filter with value', function() {
                var filterValue = aggregatedFieldConditionView.filter.getValue();
                expect(filterValue.value).toBe(initialValue.criterion.data.value);
            });

            it('triggers a \'close\' event when related column was deleted', function(done) {
                aggregatedFieldConditionView.on('close', function() {
                    expect(aggregatedFieldConditionView.filter).not.toBeDefined();
                    done();
                });
                var columnWithFunction = columnsCollection.at(1);
                columnsCollection.remove(columnWithFunction);
            });

            it('triggers a \'close\' event when label of related column was changed', function(done) {
                aggregatedFieldConditionView.on('close', function() {
                    expect(aggregatedFieldConditionView.filter).not.toBeDefined();
                    done();
                });
                columnsCollection.at(1).set('label', 'test');
            });
        });
    });
});
