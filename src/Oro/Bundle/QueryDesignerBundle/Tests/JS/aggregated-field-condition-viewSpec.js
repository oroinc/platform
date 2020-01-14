define(function(require) {
    'use strict';

    const $ = require('jquery');
    const Backbone = require('backbone');
    const jsmoduleExposure = require('jsmodule-exposure');
    const markup = require('text-loader!./Fixture/aggregated-field-condition/markup.html');
    const data = require('./Fixture/aggregated-field-condition/entities.json');
    const filters = require('./Fixture/aggregated-field-condition/filters.json');
    const columnsData = require('./Fixture/aggregated-field-condition/columnsData.json');
    const FieldConditionView = require('oroquerydesigner/js/app/views/field-condition-view');
    const FieldChoiceMock = require('./Fixture/field-condition/field-choice-mock');
    const AggregatedFieldConditionView = require('oroquerydesigner/js/app/views/aggregated-field-condition-view');
    require('jasmine-jquery');

    const exposure = jsmoduleExposure.disclose('oroquerydesigner/js/app/views/field-condition-view');

    xdescribe('oroquerydesigner/js/app/views/aggregated-field-condition-view', function() {
        let aggregatedFieldConditionView;

        describe('without initial value', function() {
            let columnsCollection;
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
            let columnsCollection;
            const initialValue = {
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
                const filterValue = aggregatedFieldConditionView.filter.getValue();
                expect(filterValue.value).toBe(initialValue.criterion.data.value);
            });

            it('triggers a \'close\' event when related column was deleted', function(done) {
                aggregatedFieldConditionView.on('close', function() {
                    expect(aggregatedFieldConditionView.filter).not.toBeDefined();
                    done();
                });
                const columnWithFunction = columnsCollection.at(1);
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
