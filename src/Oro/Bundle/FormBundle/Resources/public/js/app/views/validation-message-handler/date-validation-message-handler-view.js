define(function(require) {
    'use strict';

    const $ = require('jquery');
    const AbstractValidationMessageHandlerView =
        require('oroform/js/app/views/validation-message-handler/abstract-validation-message-handler-view');

    const DateValidationMessageHandlerView = AbstractValidationMessageHandlerView.extend({
        events: {
            'datepicker:dialogHide': 'onDatepickerDialogReposition',
            'datepicker:dialogReposition': 'onDatepickerDialogReposition'
        },

        /**
         * @inheritdoc
         */
        constructor: function DateValidationMessageHandlerView(options) {
            DateValidationMessageHandlerView.__super__.constructor.call(this, options);
        },

        isActive: function() {
            return this.$el.data('datepicker').dpDiv.is(':visible') && this.$el.is($.datepicker._lastInput) &&
                this.$el.hasClass('ui-datepicker-dialog-is-below');
        },

        getPopperReferenceElement: function() {
            return this.$el;
        },

        onDatepickerDialogReposition: function(e, position) {
            this.active = position === 'below';

            this.update();
        }
    }, {
        test: function(element) {
            return $(element).hasClass('datepicker-input');
        }
    });

    return DateValidationMessageHandlerView;
});
