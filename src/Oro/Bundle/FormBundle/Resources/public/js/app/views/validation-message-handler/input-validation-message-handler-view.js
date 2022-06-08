define(function(require) {
    'use strict';

    const $ = require('jquery');
    const AbstractValidationMessageHandlerView =
        require('oroform/js/app/views/validation-message-handler/abstract-validation-message-handler-view');

    const InputValidationMessageHandlerView = AbstractValidationMessageHandlerView.extend({
        events: {
            'validate-element': 'onValidateElement'
        },

        /**
         * @inheritdoc
         */
        constructor: function InputValidationMessageHandlerView(options) {
            InputValidationMessageHandlerView.__super__.constructor.call(this, options);
        },

        isActive() {
            return this.$el.hasClass('error');
        },

        getPopperReferenceElement() {
            return this.$el;
        },

        onValidateElement() {
            this.label.hide();
        },


        render() {
            this.label.show();

            return InputValidationMessageHandlerView.__super__.render.call(this);
        },

        dispose() {
            if (this.disposed) {
                return;
            }

            this.label.show();

            InputValidationMessageHandlerView.__super__.dispose.call(this);
        }
    }, {
        test(element) {
            return $(element).is('[data-floating-error]');
        }
    });

    return InputValidationMessageHandlerView;
});
