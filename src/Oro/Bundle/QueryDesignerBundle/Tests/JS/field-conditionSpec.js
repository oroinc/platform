define(function(require) {
    'use strict';

    var $ = require('jquery');
    var data = JSON.parse(require('text!./Fixture/field-condition/entities.json'));
    var filters = JSON.parse(require('text!./Fixture/field-condition/filters.json'));
    var BaseView = require('oroui/js/app/views/base/view');
    var AbstractFilter = require('oro/filter/abstract-filter');
    var DateTimeFilter = require('oro/filter/datetime-filter');
    var FieldConditionView = require('oroquerydesigner/js/app/views/field-condition-view');
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

    describe('oroquerydesigner/js/app/views/field-condition-view', function() {

        var fieldConditionView;

        describe('without initial value', function() {
            beforeEach(function(done) {
                fieldConditionView = new FieldConditionView({
                    autoRender: true,
                    filters: filters,
                    fieldChoice: {
                        entity: 'Oro\\Bundle\\AccountBundle\\Entity\\Account',
                        data: data
                    }
                });
                window.setFixtures(fieldConditionView.$el);
                $.when(fieldConditionView.deferredRender).then(function() {
                    done();
                });
            });

            afterEach(function() {
                fieldConditionView.dispose();
            });

            it('is instance of BaseView', function() {
                expect(fieldConditionView).toEqual(jasmine.any(BaseView));
            });

            it('has choiceInput view', function() {
                expect(fieldConditionView.subview('choice-input')).toEqual(jasmine.any(BaseView));
            });

            it('has empty value into field choice input', function() {
                expect(fieldConditionView.getChoiceInputValue()).toBe('');
            });

            it('shows empty filter when has no selected field', function() {
                expect(fieldConditionView.$('.active-filter').html()).toBe('');
            });

            it('shows a filter when field is selected', function(done) {
                fieldConditionView.setChoiceInputValue('name').then(function() {
                    expect(fieldConditionView.filter).toEqual(jasmine.any(AbstractFilter));
                    done();
                });
            });

            it('shows datetime filter when selected field has datetime type', function(done) {
                fieldConditionView.setChoiceInputValue('createdAt').then(function() {
                    expect(fieldConditionView.filter).toEqual(jasmine.any(DateTimeFilter));
                    done();
                });
            });

            it('has correct value after fields are filled', function(done) {
                fieldConditionView.setChoiceInputValue('createdAt').then(function() {
                    var newFilterValue = {
                        type: '2',
                        part: 'value',
                        value: {
                            start: '2016-01-01 00:00',
                            end: '2017-01-01 00:00'
                        }
                    };
                    fieldConditionView.filter.setValue(newFilterValue);
                    var conditionValue = fieldConditionView.getValue();
                    expect(conditionValue.columnName).toBe('createdAt');
                    expect(conditionValue.criterion.data).toEqual(newFilterValue);
                    done();
                });
            });
        });

        describe('with initial value', function() {
            var initialValue = {
                'columnName': 'name',
                'criterion': {
                    'filter': 'string',
                    'data': {
                        'type': '1',
                        'value': 'test'
                    }
                }
            };
            beforeEach(function(done) {
                fieldConditionView = new FieldConditionView({
                    autoRender: true,
                    filters: filters,
                    value: initialValue,
                    fieldChoice: {
                        entity: 'Oro\\Bundle\\AccountBundle\\Entity\\Account',
                        data: data
                    }
                });
                window.setFixtures(fieldConditionView.$el);
                $.when(fieldConditionView.deferredRender).then(function() {
                    done();
                });
            });

            afterEach(function() {
                fieldConditionView.dispose();
            });

            it('shows a filter with value', function() {
                var filterValue = fieldConditionView.filter.getValue();
                expect(filterValue.value).toBe('test');
            });

            it('clears a filter after field is changed', function(done) {
                fieldConditionView.setChoiceInputValue('createdAt').then(function() {
                    var filterValue = fieldConditionView.filter.getValue();
                    expect(filterValue).toEqual(fieldConditionView.filter.emptyValue);
                    done();
                });
            });
        });
    });
});
