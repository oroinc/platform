define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const jsmoduleExposure = require('jsmodule-exposure');
    const filters = require('./Fixture/segment-condition/filters.json');
    const BaseView = require('oroui/js/app/views/base/view');
    const SegmentChoiceMock = require('./Fixture/segment-condition/segment-choice-mock');
    const SegmentConditionView = require('orosegment/js/app/views/segment-condition-view');
    require('jasmine-jquery');

    const exposure = jsmoduleExposure.disclose('orosegment/js/app/views/segment-condition-view');

    xdescribe('orosegment/js/app/views/segment-condition-view', function() {
        let segmentConditionView;

        describe('without initial value', function() {
            beforeEach(function(done) {
                exposure.substitute('SegmentChoiceView').by(SegmentChoiceMock);
                segmentConditionView = new SegmentConditionView({
                    autoRender: true,
                    filters: filters,
                    segmentChoice: {
                        entity: 'Oro\\Bundle\\AccountBundle\\Entity\\Account'
                    }
                });
                $.when(segmentConditionView.deferredRender).then(function() {
                    done();
                });
            });

            afterEach(function() {
                segmentConditionView.dispose();
                delete SegmentChoiceMock.lastCreatedInstance;
                exposure.recover('SegmentChoiceView');
            });

            it('is instance of BaseView', function() {
                expect(segmentConditionView).toEqual(jasmine.any(BaseView));
            });

            it('has empty value into field choice input', function() {
                expect(segmentConditionView.getChoiceInputValue()).toBe('');
            });

            it('shows empty filter when has no selected field', function() {
                expect(segmentConditionView.$('.active-filter')).toBeEmpty();
            });

            it('sets correct value in choice input after value was set', function(done) {
                segmentConditionView.setValue({
                    columnName: 'id',
                    criterion: {
                        filter: 'segment',
                        data: {
                            type: null,
                            value: '2'
                        }
                    }
                });
                segmentConditionView.render();
                $.when(segmentConditionView.deferredRender).then(function() {
                    expect(SegmentChoiceMock.lastCreatedInstance.setValue).toHaveBeenCalledWith('id');
                    expect(segmentConditionView.getChoiceInputValue()).toBe('segment_2');
                    done();
                });
            });
        });

        describe('with initial value', function() {
            const initialValue = {
                columnName: 'id',
                criterion: {
                    filter: 'segment',
                    data: {
                        type: null,
                        value: '2'
                    }
                }
            };
            const filter = _.findWhere(filters, {name: initialValue.criterion.filter});
            const filterChoice = filter.choices[initialValue.criterion.data.value];
            const initialChoiceInputValue = 'segment_' + _.result(filterChoice, 'value');

            beforeEach(function(done) {
                exposure.substitute('SegmentChoiceView').by(SegmentChoiceMock);
                segmentConditionView = new SegmentConditionView({
                    autoRender: true,
                    filters: filters,
                    segmentChoice: {
                        entity: 'Oro\\Bundle\\AccountBundle\\Entity\\Account'
                    },
                    value: initialValue
                });
                $.when(segmentConditionView.deferredRender).then(function() {
                    done();
                });
            });

            afterEach(function() {
                segmentConditionView.dispose();
                delete SegmentChoiceMock.lastCreatedInstance;
                exposure.recover('SegmentChoiceView');
            });

            it('sets correct value into segment choice', function() {
                expect(SegmentChoiceMock.lastCreatedInstance.setValue).toHaveBeenCalledWith('id');
                expect(SegmentChoiceMock.lastCreatedInstance.setData).toHaveBeenCalledWith({
                    id: 'segment_2',
                    text: 'Segment 2'
                });
                expect(segmentConditionView.getChoiceInputValue()).toBe(initialChoiceInputValue);
            });

            it('contains a filter with correct value', function() {
                expect(segmentConditionView.getColumnName()).toBe(initialValue.columnName);
            });

            it('has correct value after fields are changed', function(done) {
                segmentConditionView.setChoiceInputValue('segment_1').then(function() {
                    const conditionValue = segmentConditionView.getValue();
                    expect(SegmentChoiceMock.lastCreatedInstance.setValue).toHaveBeenCalledWith('id');
                    expect(conditionValue.columnName).toBe('id');
                    expect(conditionValue.criterion.data.value).toBe('1');
                    done();
                });
            });
        });
    });
});
