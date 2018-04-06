define(function(require) {
    'use strict';

    var ColumnFormView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    ColumnFormView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'fieldChoiceView', 'functionChoiceView'
        ]),

        labelSelector: '[data-purpose=label]',

        /**
         * @inheritDoc
         */
        constructor: function ColumnFormView() {
            ColumnFormView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            ColumnFormView.__super__.render.call(this);
            this.listenTo(this.fieldChoiceView, 'change', this.onFieldsChange);
        },

        onFieldsChange: function(addedField) {
            this.updateFunctionChoices(_.result(addedField, 'id'));
            if (addedField) {
                // update label input on field change
                this.$('[data-purpose=label]').val(addedField.text).trigger('change');
            }
        },

        updateFunctionChoices: function(fieldId) {
            if (this.functionChoiceView) {
                var criteria = this.fieldChoiceView.getApplicableConditions(fieldId);
                this.functionChoiceView.setActiveFunctions(criteria);
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.fieldChoiceView;
            delete this.functionChoiceView;
            ColumnFormView.__super__.dispose.call(this);
        }
    });

    return ColumnFormView;
});

