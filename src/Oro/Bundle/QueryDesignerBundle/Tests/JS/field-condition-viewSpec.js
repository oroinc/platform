define(function(require) {
    'use strict';

    const $ = require('jquery');
    const jsmoduleExposure = require('jsmodule-exposure');
    const data = require('./Fixture/field-condition/entities.json');
    const filters = require('./Fixture/field-condition/filters.json');
    const BaseView = require('oroui/js/app/views/base/view');
    const AbstractFilter = require('oro/filter/abstract-filter');
    const DateTimeFilter = require('oro/filter/datetime-filter');
    const FieldConditionView = require('oroquerydesigner/js/app/views/field-condition-view');
    const FieldChoiceMock = require('./Fixture/field-condition/field-choice-mock');
    require('jasmine-jquery');

    const exposure = jsmoduleExposure.disclose('oroquerydesigner/js/app/views/field-condition-view');

    xdescribe('oroquerydesigner/js/app/views/field-condition-view', function() {
        let fieldConditionView;

        describe('without initial value', function() {
            beforeEach(function(done) {
                FieldChoiceMock.setData(data);
                exposure.substitute('FieldChoiceView').by(FieldChoiceMock);
                fieldConditionView = new FieldConditionView({
                    autoRender: true,
                    filters: filters,
                    fieldChoice: {
                        entity: 'Oro\\Bundle\\AccountBundle\\Entity\\Account'
                    }
                });
                window.setFixtures(fieldConditionView.$el);
                $.when(fieldConditionView.deferredRender).then(function() {
                    done();
                });
            });

            afterEach(function() {
                fieldConditionView.dispose();
                exposure.recover('FieldChoiceView');
                delete FieldChoiceMock.lastCreatedInstance;
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
                    const newFilterValue = {
                        type: '2',
                        part: 'value',
                        value: {
                            start: '2016-01-01 00:00',
                            end: '2017-01-01 00:00'
                        }
                    };
                    fieldConditionView.filter.setValue(newFilterValue);
                    const conditionValue = fieldConditionView.getValue();
                    expect(FieldChoiceMock.lastCreatedInstance.setValue).toHaveBeenCalledWith('createdAt');
                    expect(conditionValue.columnName).toBe('createdAt');
                    expect(conditionValue.criterion.data).toEqual(newFilterValue);
                    done();
                });
            });
        });

        describe('with initial value', function() {
            const initialValue = {
                columnName: 'name',
                criterion: {
                    filter: 'string',
                    data: {
                        type: '1',
                        value: 'test'
                    }
                }
            };
            beforeEach(function(done) {
                FieldChoiceMock.setData(data);
                exposure.substitute('FieldChoiceView').by(FieldChoiceMock);
                fieldConditionView = new FieldConditionView({
                    autoRender: true,
                    filters: filters,
                    value: initialValue,
                    fieldChoice: {
                        entity: 'Oro\\Bundle\\AccountBundle\\Entity\\Account'
                    }
                });
                window.setFixtures(fieldConditionView.$el);
                $.when(fieldConditionView.deferredRender).then(function() {
                    done();
                });
            });

            afterEach(function() {
                fieldConditionView.dispose();
                exposure.recover('FieldChoiceView');
                delete FieldChoiceMock.lastCreatedInstance;
            });

            it('shows a filter with value', function() {
                const filterValue = fieldConditionView.filter.getValue();
                expect(filterValue.value).toBe('test');
            });

            it('clears a filter after field is changed', function(done) {
                fieldConditionView.setChoiceInputValue('createdAt').then(function() {
                    const filterValue = fieldConditionView.filter.getValue();
                    expect(FieldChoiceMock.lastCreatedInstance.setValue).toHaveBeenCalledWith('createdAt');
                    expect(filterValue).toEqual(fieldConditionView.filter.emptyValue);
                    done();
                });
            });
        });
    });
});
