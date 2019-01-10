define(function(require) {
    'use strict';

    var DateValidationMessageHandlerView;
    var $ = require('jquery');
    var AbstractValidationMessageHandlerView =
        require('oroform/js/app/views/validation-message-handler/abstract-validation-message-handler-view');

    DateValidationMessageHandlerView = AbstractValidationMessageHandlerView.extend({
        events: {
            'datepicker:dialogHide': 'onDatepickerDialogReposition',
            'datepicker:dialogReposition': 'onDatepickerDialogReposition'
        },

        /**
         * @inheritDoc
         */
        constructor: function DateValidationMessageHandlerView() {
            DateValidationMessageHandlerView.__super__.constructor.apply(this, arguments);
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
