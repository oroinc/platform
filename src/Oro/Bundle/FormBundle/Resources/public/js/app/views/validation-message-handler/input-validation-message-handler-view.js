define(function(require) {
    'use strict';

    const $ = require('jquery');
    const AbstractValidationMessageHandlerView =
        require('oroform/js/app/views/validation-message-handler/abstract-validation-message-handler-view');
    const Popper = require('popper').default;

    const InputValidationMessageHandlerView = AbstractValidationMessageHandlerView.extend({
        useMessageLabelWidth: false,

        events: {
            'validate-element': 'onUpdate',
            'focus': 'onUpdate'
        },

        /**
         * @inheritdoc
         */
        constructor: function InputValidationMessageHandlerView(options) {
            InputValidationMessageHandlerView.__super__.constructor.call(this, options);
        },

        isActive() {
            return this.$el.hasClass('error') && this.$el.is(':focus');
        },

        onUpdate() {
            this.active = this.isActive();

            this.update();
        },

        getPopperReferenceElement() {
            return this.$el;
        },

        update() {
            InputValidationMessageHandlerView.__super__.update.call(this);

            this.label.addClass('hide');
        },

        render() {
            InputValidationMessageHandlerView.__super__.render.call(this);

            this.update();

            return this;
        },

        dispose() {
            if (this.disposed) {
                return;
            }

            this.label.removeClass('hide');

            InputValidationMessageHandlerView.__super__.dispose.call(this);
        },

        hideModifier: function(data, options) {
            const scrollRect = this.scrollParent.getBoundingClientRect();

            if (data.offsets.reference.top < scrollRect.top ||
                data.offsets.reference.top > scrollRect.bottom || data.offsets.left > scrollRect.right ||
                data.offsets.left > scrollRect.left
            ) {
                data.hide = true;
                data.attributes['x-out-of-boundaries'] = '';
            } else {
                Popper.Defaults.modifiers.hide.fn(data, options);
            }

            return data;
        }
    }, {
        test(element) {
            return $(element).is('[data-floating-error]');
        }
    });

    return InputValidationMessageHandlerView;
});
