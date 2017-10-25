define(function(require) {
    'use strict';

    var ColumnFormView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    require('oroquerydesigner/js/function-choice');

    ColumnFormView = BaseView.extend({

        showItems: ['column', 'label', 'function', 'sorting', 'action'],

        initialize: function(options) {
            _.extend(this, _.pick(options, 'showItems', 'functionChoiceOptions', 'fieldChoiceView'));
            ColumnFormView.__super__.initialize.call(this, options);
        },

        labelSelector: '[data-purpose=label]',

        render: function() {
            ColumnFormView.__super__.render.call(this);
            this.listenTo(this.fieldChoiceView, 'change', this.onFieldsChange);
            if (_.contains(this.showItems, 'function')) {
                this.functionChoiceWidget = this.$('[data-purpose=function-selector]')
                    .functionChoice(this.functionChoiceOptions).functionChoice('instance');
            }
        },

        onFieldsChange: function(value, addedField) {
            this.updateFunctionChoices(value);
            if (addedField) {
                // update label input on field change
                this.$('[data-purpose=label]').val(addedField.text).trigger('change');
            }
        },

        updateFunctionChoices: function(fieldId) {
            if (this.functionChoiceWidget) {
                var criteria = this.fieldChoiceView.getApplicableConditions(fieldId);
                this.functionChoiceWidget.setActiveFunctions(criteria);
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.fieldChoiceView;
            ColumnFormView.__super__.dispose.call(this);
        }
    });

    return ColumnFormView;
});

