define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var data = JSON.parse(require('text!./Fixture/entities.json'));
    var filters = JSON.parse(require('text!./Fixture/filters.json'));
    var listOptions = require('text!./Fixture/list-options.json');
    var FieldConditionView = require('oroquerydesigner/js/app/views/field-condition-view');
    var ActivityConditionView = require('oroactivitylist/js/app/views/activity-condition-view');
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

    describe('oroactivitylist/js/app/views/activity-condition-view', function() {
        var activityConditionView;

        describe('without initial value', function() {

            beforeEach(function(done) {
                var $html = $('<div />');
                window.setFixtures($html);
                var $fieldLoader = $('<div/>', {id: 'test-fields-loader'}).data('fields', _.clone(data));
                $html.append($fieldLoader);
                activityConditionView = new ActivityConditionView({
                    autoRender: true,
                    filters: filters,
                    fieldsLoaderSelector: '#test-fields-loader',
                    listOptions: _.clone(listOptions)
                });
                $html.append(activityConditionView.$el);
                $.when(activityConditionView.deferredRender).then(function() {
                    activityConditionView.$choiceInput.fieldChoice('updateData',
                        'Oro\\Bundle\\AccountBundle\\Entity\\Account', data);
                    done();
                });
            });

            afterEach(function() {
                activityConditionView.dispose();
            });

            it('is ascendant of FieldConditionView', function() {
                expect(activityConditionView).toEqual(jasmine.any(FieldConditionView));
            });

            it('has correct value after filters are set', function(done) {
                var activityTypes = ['Oro_Bundle_TaskBundle_Entity_Task'];
                activityConditionView.setActivityExistence('hasNotActivity');
                activityConditionView.setActivityTypes(activityTypes);
                activityConditionView.setChoiceInputValue('$createdAt').then(function() {
                    var newFilterValue = {
                        type: '1',
                        part: 'value',
                        value: {
                            start: '2016-01-01 00:00',
                            end: '2017-01-01 00:00'
                        }
                    };
                    activityConditionView.filter.setValue(newFilterValue);
                    var conditionValue = activityConditionView.getValue();
                    expect(conditionValue.criterion.data.filterType).toEqual('hasNotActivity');
                    expect(conditionValue.criterion.data.activityType.value).toEqual(activityTypes);
                    expect(conditionValue.criterion.data.activityFieldName).toBe('$createdAt');
                    expect(conditionValue.criterion.data.filter.data).toEqual(newFilterValue);
                    done();
                });
            });
        });

        describe('with initial value', function() {
            var activityConditionView;
            var initialValue = {
                criterion: {
                    filter: 'activityList',
                    data: {
                        activityFieldName: '$updatedAt',
                        filterType: 'hasNotActivity',
                        activityType: {
                            value: [
                                'Oro_Bundle_TaskBundle_Entity_Task',
                                'Oro_Bundle_CallBundle_Entity_Call'
                            ]
                        },
                        filter: {
                            filter: 'datetime',
                            data: {
                                type: '2',
                                part: 'value',
                                value: {
                                    start: '2017-09-01 00:00',
                                    end: '2017-10-01 00:00'
                                }
                            }
                        }
                    }
                }
            };
            beforeEach(function(done) {
                var $html = $('<div />');
                window.setFixtures($html);
                var $fieldLoader = $('<div/>', {id: 'test-fields-loader'}).data('fields', _.clone(data));
                $html.append($fieldLoader);
                activityConditionView = new ActivityConditionView({
                    autoRender: true,
                    filters: filters,
                    value: initialValue,
                    fieldsLoaderSelector: '#test-fields-loader',
                    listOptions: listOptions
                });
                $html.append(activityConditionView.$el);
                $.when(activityConditionView.deferredRender).then(function() {
                    activityConditionView.$choiceInput.fieldChoice('updateData',
                        'Oro\\Bundle\\AccountBundle\\Entity\\Account', data);
                    done();
                });
            });

            afterEach(function() {
                activityConditionView.dispose();
            });

            it('renders a correct field', function() {
                var choiceInputValue = activityConditionView.getChoiceInputValue();
                expect(choiceInputValue).toBe('$updatedAt');
            });
            it('shows a filter with value', function() {
                var filterValue = activityConditionView.filter.getValue();
                expect(filterValue.value).toEqual({start: '2017-09-01 00:00', end: '2017-10-01 00:00'});
            });
        });
    });
});
