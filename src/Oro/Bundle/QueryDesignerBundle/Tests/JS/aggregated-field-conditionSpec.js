define(function(require) {
    'use strict';

    var $ = require('jquery');
    var Backbone = require('backbone');
    var markup = require('text!./Fixture/aggregated-field-condition/markup.html');
    var data = JSON.parse(require('text!./Fixture/aggregated-field-condition/entities.json'));
    var filters = JSON.parse(require('text!./Fixture/aggregated-field-condition/filters.json'));
    var columnsData = JSON.parse(require('text!./Fixture/aggregated-field-condition/columnsData.json'));
    var FieldConditionView = require('oroquerydesigner/js/app/views/field-condition-view');
    var AggregatedFieldConditionView = require('oroquerydesigner/js/app/views/aggregated-field-condition-view');
    require('jasmine-jquery');

    //Make cross link inside data members
    // TODO: remove following processing of data after fileLoader is refactored
    data = (function(data) {
        $.each(data, function() {
            var entity = this;
            entity.fieldsIndex = {};
            $.each(entity.fields, function() {
                var field = this;
                if (field.relation_type && field.related_entity_name) {
                    field.related_entity = data[field.related_entity_name];
                    delete field.related_entity_name;
                }
                field.entity = entity;
                entity.fieldsIndex[field.name] = field;
            });
        });
        return data;
    })(data);

    describe('oroquerydesigner/js/app/views/aggregated-field-condition-view', function() {

        var aggregatedFieldConditionView;

        describe('without initial value', function() {
            var columnsCollection;
            beforeEach(function(done) {
                window.setFixtures(markup);
                columnsCollection = new Backbone.Collection(columnsData);
                aggregatedFieldConditionView = new AggregatedFieldConditionView({
                    autoRender: true,
                    filters: filters,
                    columnsCollection: columnsCollection,
                    fieldChoice: {
                        select2: {
                            formatSelectionTemplateSelector: '#format-selection-template'
                        }
                    }
                });
                window.setFixtures(aggregatedFieldConditionView.$el);
                $.when(aggregatedFieldConditionView.deferredRender).then(function() {
                    aggregatedFieldConditionView.$choiceInput.fieldChoice('updateData',
                        'Oro\\Bundle\\AccountBundle\\Entity\\Account', data);
                    done();
                });
            });

            afterEach(function() {
                aggregatedFieldConditionView.dispose();
            });

            it('is instance of FieldConditionView', function() {
                expect(aggregatedFieldConditionView).toEqual(jasmine.any(FieldConditionView));

            });

            it('has function name into label of field choice value', function(done) {
                aggregatedFieldConditionView.setChoiceInputValue('id').then(function() {
                    expect(aggregatedFieldConditionView.$('.select2 .select2-chosen').text()).toContain('Count');
                    done();
                });
            });

        });

        describe('with initial value', function() {
            var columnsCollection;
            var initialValue = {
                'columnName': 'id',
                'criterion': {
                    'filter': 'number',
                    'data': {
                        'value': 1,
                        'type': '3',
                        'params': {
                            'filter_by_having': true
                        }
                    }
                },
                'func': {
                    'name': 'Count',
                    'group_type': 'aggregates',
                    'group_name': 'number'
                },
                'criteria': 'aggregated-condition-item'
            };

            beforeEach(function(done) {
                window.setFixtures(markup);
                columnsCollection = new Backbone.Collection(columnsData);
                aggregatedFieldConditionView = new AggregatedFieldConditionView({
                    autoRender: true,
                    filters: filters,
                    columnsCollection: columnsCollection,
                    fieldChoice: {
                        select2: {
                            formatSelectionTemplateSelector: '#format-selection-template'
                        }
                    },
                    value: initialValue
                });
                window.setFixtures(aggregatedFieldConditionView.$el);
                $.when(aggregatedFieldConditionView.deferredRender).then(function() {
                    aggregatedFieldConditionView.$choiceInput.fieldChoice('updateData',
                        'Oro\\Bundle\\AccountBundle\\Entity\\Account', data);
                    done();
                });
            });

            afterEach(function() {
                aggregatedFieldConditionView.dispose();
            });

            it('has function name into label of field choice value', function() {
                expect(aggregatedFieldConditionView.$('.select2 .select2-chosen').text()).toContain('Count');
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
