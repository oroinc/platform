define(function(require) {
    'use strict';

    require('jasmine-jquery');
    const ConditionBuilderView = require('oroquerydesigner/js/app/views/condition-builder/condition-builder-view');
    const $ = require('jquery');
    // const StubConditionView = require('../Fixture/condition-builder/stub-views.js');
    const html = require('text-loader!../Fixture/condition-builder/markup.html');
    const initialValue = require('../Fixture/condition-builder/initial-value.json');
    const runtimeValue = require('../Fixture/condition-builder/runtime-value.json');

    // define('condition-builder/condition-item-stub-view', function() {
    //     return StubConditionView;
    // });
    // define('condition-builder/matrix-condition-stub-view', function() {
    //     return StubConditionView;
    // });

    xdescribe('oroquerydesigner/js/app/views/condition-builder/condition-builder-view', function() {
        let builderView;

        // emulates drag and drop action
        function changeHierarchy($elem1, position, $elem2) {
            const $sender = $elem1.parent();
            if (position === 'before') {
                $elem1.insertBefore($elem2);
            } else {
                $elem1.insertAfter($elem2);
            }
            if (!$sender.is(builderView.$criteriaList)) {
                // emulates remove handler of sortable
                builderView
                    ._onStructureUpdate({target: $sender[0]}, {sender: null, item: $elem1});
            }
            // emulates update handler of sortable, call handler directly
            builderView
                ._onStructureUpdate({target: $elem2.parent()[0]}, {sender: $sender, item: $elem1});
        }

        // emulates drag and drop new condition action
        function addNewCondition(criterion, position, $elem2) {
            let $criterion = $('.criteria-list>[data-criteria=' + criterion + ']');
            $criterion = $criterion.clone().insertAfter($criterion);
            changeHierarchy($criterion, position, $elem2);
            const $condition = $criterion.prev();
            $criterion.remove();
            return $condition;
        }

        beforeEach(function(done) {
            window.setFixtures(html);
            builderView = new ConditionBuilderView({
                autoRender: true,
                el: $('.condition-builder'),
                value: initialValue
            });
            builderView.deferredRender.done(done);
        });

        it('current value equals to initial', function() {
            expect(builderView.getValue()).toEqual(initialValue);
        });

        describe('container structure', function() {
            let $group;
            let $matrix;
            let $condition;
            let $operator;
            beforeEach(function() {
                $group = $('.condition-container [data-criteria=conditions-group]');
                $matrix = $('.condition-container [data-criteria=matrix-condition]');
                $condition = $('.condition-container [data-criteria=condition-item]');
                $operator = $('.condition-container .condition-operator');
            });

            it('group elements count', function() {
                expect($group).toHaveLength(1);
            });

            it('checks group value', function() {
                const groupView = builderView.getConditionViewOfElement($group[0]);
                expect(groupView.getValue()).toEqual(initialValue[2]);
            });

            it('matrix elements count', function() {
                expect($matrix).toHaveLength(2);
            });

            it('checks rendered content of matrix elements', function() {
                expect($matrix).toContainText('The Matrix Condition');
            });

            it('checks matrix condition values', function() {
                const matrix1View = builderView.getConditionViewOfElement($matrix[0]);
                const matrix2View = builderView.getConditionViewOfElement($matrix[1]);
                expect(matrix1View.getValue()).toEqual(initialValue[0]);
                expect(matrix2View.getValue()).toEqual(initialValue[2][2]);
            });

            it('condition-item elements count', function() {
                expect($condition).toHaveLength(1);
            });

            it('checks rendered content of matrix elements', function() {
                expect($condition).toContainText('The Condition Item');
            });

            it('checks condition-item value', function() {
                const conditionView = builderView.getConditionViewOfElement($condition[0]);
                expect(conditionView.getValue()).toEqual(initialValue[2][0]);
            });

            it('operator elements count', function() {
                expect($operator).toHaveLength(2);
            });

            it('checks operator values', function() {
                const operator1View = builderView.getConditionViewOfElement($operator[0]);
                const operator2View = builderView.getConditionViewOfElement($operator[1]);
                expect(operator1View.getValue()).toEqual(initialValue[1]);
                expect(operator2View.getValue()).toEqual(initialValue[2][1]);
            });
        });

        describe('restructure process', function() {
            let $group;
            let $matrix1;
            let $matrix2;
            let $condition;
            beforeEach(function() {
                $group = $('.condition-container [data-criteria=conditions-group]');
                $matrix1 = $('.condition-container [data-criteria=matrix-condition]').first();
                $matrix2 = $('.condition-container [data-criteria=matrix-condition]').last();
                $condition = $('.condition-container [data-criteria=condition-item]');
            });

            it('moves group at the beginning', function() {
                changeHierarchy($group, 'before', $matrix1);
                expect(builderView.getValue()).toEqual([
                    [{equal: 5}, 'OR', {criteria: 'matrix-condition', less: 8}],
                    'AND',
                    {criteria: 'matrix-condition', great: 10}
                ]);
            });

            it('moves condition-item inside group', function() {
                changeHierarchy($matrix2, 'before', $condition);
                expect(builderView.getValue()).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    [{criteria: 'matrix-condition', less: 8}, 'AND', {equal: 5}]
                ]);
            });

            it('puts condition-item outside group', function() {
                changeHierarchy($matrix2, 'after', $matrix1);
                expect(builderView.getValue()).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    {criteria: 'matrix-condition', less: 8},
                    'AND',
                    [{equal: 5}]
                ]);
            });

            it('puts condition-item into group', function() {
                changeHierarchy($matrix1, 'after', $matrix2);
                expect(builderView.getValue()).toEqual([
                    [
                        {equal: 5},
                        'OR',
                        {criteria: 'matrix-condition', less: 8},
                        'AND',
                        {criteria: 'matrix-condition', great: 10}
                    ]
                ]);
            });
        });

        describe('add a new condition', function() {
            let $group;
            let $matrix2;
            let $condition;
            beforeEach(function() {
                $group = $('.condition-container [data-criteria=conditions-group]');
                $matrix2 = $('.condition-container [data-criteria=matrix-condition]').last();
                $condition = $('.condition-container [data-criteria=condition-item]');
            });

            it('adds "matrix condition" into group', function() {
                const $newCondition = addNewCondition('matrix-condition', 'after', $matrix2);
                expect($newCondition[0]).not.toBe($matrix2[0]);
                expect($newCondition.find('>input[type=checkbox]')).not.toBeChecked();
                expect($newCondition).toContainText('The Matrix Condition');
                expect(builderView.getValue()).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    [{equal: 5}, 'OR', {criteria: 'matrix-condition', less: 8}, 'AND', {}]
                ]);
            });

            it('adds "condition item" before group', function() {
                const $newCondition = addNewCondition('condition-item', 'before', $group);
                expect($newCondition.find('>input[type=checkbox]')).not.toBeChecked();
                expect($newCondition).toContainText('The Condition Item');
                expect(builderView.getValue()).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    {},
                    'AND',
                    [{equal: 5}, 'OR', {criteria: 'matrix-condition', less: 8}]
                ]);
            });

            it('adds a new group inside the group', function() {
                const $newCondition = addNewCondition('conditions-group', 'before', $condition);
                expect($newCondition.find('>input[type=checkbox]')).not.toBeChecked();
                expect(builderView.getValue()).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    [[], 'AND', {equal: 5}, 'OR', {criteria: 'matrix-condition', less: 8}]
                ]);
            });
        });

        describe('condition-item\'s value change', function() {
            let matrix;
            let condition;
            beforeEach(function() {
                matrix = builderView.getConditionViewOfElement(
                    $('.condition-container [data-criteria=matrix-condition]').first());
                condition = builderView.getConditionViewOfElement(
                    $('.condition-container [data-criteria=condition-item]'));
            });

            it('changes value of a condition-item in a root', function() {
                const criterionStub = matrix.subview('criterion');
                criterionStub.value = {less: 18};
                criterionStub.trigger('change');
                expect(builderView.getValue()).toEqual([
                    {criteria: 'matrix-condition', less: 18},
                    'AND',
                    [{equal: 5}, 'OR', {criteria: 'matrix-condition', less: 8}]
                ]);
            });

            it('changes value of a condition-item inside group', function() {
                const criterionStub = condition.subview('criterion');
                criterionStub.value = {less: -8};
                criterionStub.trigger('change');
                expect(builderView.getValue()).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    [{less: -8}, 'OR', {criteria: 'matrix-condition', less: 8}]
                ]);
            });
        });

        describe('operator\'s value change', function() {
            let operator1;
            let operator2;
            beforeEach(function() {
                operator1 = builderView
                    .getConditionViewOfElement($('.condition-container .condition-operator').first());
                operator2 = builderView
                    .getConditionViewOfElement($('.condition-container .condition-operator').last());
            });

            it('changes value of the operator before group', function() {
                let actualValue = null;
                const expectedValue = [
                    {criteria: 'matrix-condition', great: 10},
                    'OR',
                    [{equal: 5}, 'OR', {criteria: 'matrix-condition', less: 8}]
                ];
                builderView.once('change', function(value) {
                    actualValue = value;
                });
                operator1.setValue('OR');
                expect(actualValue).toEqual(expectedValue);
            });

            it('changes value of the operator inside group', function() {
                let actualValue = null;
                const expectedValue = [
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    [{equal: 5}, 'AND', {criteria: 'matrix-condition', less: 8}]
                ];
                builderView.once('change', function(value) {
                    actualValue = value;
                });
                operator2.setValue('AND');
                expect(actualValue).toEqual(expectedValue);
            });
        });

        describe('validation\'s checkboxes', function() {
            let group;
            let condition;
            beforeEach(function() {
                group = builderView.getConditionViewOfElement(
                    $('.condition-container [data-criteria=conditions-group]'));
                condition = builderView.getConditionViewOfElement(
                    $('.condition-container [data-criteria=condition-item]'));
            });

            it('checks validation input for condition-item', function() {
                const criterionStub = condition.subview('criterion');
                const validationInput = condition.$('>input[name^=condition_item_]');
                expect(validationInput).toBeChecked();
                criterionStub.value = {};
                criterionStub.trigger('change');
                expect(validationInput).not.toBeChecked();
            });

            it('checks validation input for group', function() {
                const validationInput = group.$('>input[name^=condition_item_]');
                expect(validationInput).toBeChecked();
                while (group.subviews.length) {
                    group.subviews[0].closeCondition();
                }
                expect(validationInput).not.toBeChecked();
            });
        });

        describe('close condition', function() {
            let $group;
            let $condition;
            beforeEach(function() {
                $group = $('.condition-container [data-criteria=conditions-group]');
                $condition = $('.condition-container [data-criteria=condition-item]');
            });

            it('closes condition-item', function() {
                $condition.find('>.btn-close').trigger('click');
                expect(builderView.getValue()).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    [
                        {criteria: 'matrix-condition', less: 8}
                    ]
                ]);
            });

            it('closes group', function() {
                $group.find('>.btn-close').trigger('click');
                expect(builderView.getValue()).toEqual([
                    {criteria: 'matrix-condition', great: 10}
                ]);
            });
        });

        describe('new value', function() {
            it('checks new structure', function() {
                builderView.setValue(runtimeValue);
                expect(builderView.$('.condition-container [data-criteria=conditions-group]')).toHaveLength(3);
                expect(builderView.$('.condition-container [data-criteria=matrix-condition]')).toHaveLength(3);
                expect(builderView.$('.condition-container [data-criteria=condition-item]')).toHaveLength(4);
                expect(builderView.$('.condition-container .condition-operator')).toHaveLength(6);
                expect(builderView.getValue()).toEqual(runtimeValue);
            });
        });
    });
});
