define(function(require) {
    'use strict';

    var Select2ValidationMessageHandlerView;
    var $ = require('jquery');
    var AbstractValidationMessageHandlerView =
        require('oroform/js/app/views/validation-message-handler/abstract-validation-message-handler-view');

    Select2ValidationMessageHandlerView = AbstractValidationMessageHandlerView.extend({
        events: {
            'select2-close': 'onSelect2DialogReposition',
            'select2:dialogReposition': 'onSelect2DialogReposition'
        },

        /**
         * @inheritDoc
         */
        constructor: function Select2ValidationMessageHandlerView() {
            Select2ValidationMessageHandlerView.__super__.constructor.apply(this, arguments);
        },

        isActive: function() {
            var select2Instance = this.$el.data('select2');

            return select2Instance.opened() && !select2Instance.container.hasClass('select2-drop-above');
        },

        getPopperReferenceElement: function() {
            return this.$el.data('select2').container;
        },

        onSelect2DialogReposition: function(e, position) {
            this.active = position === 'below';
            this.update();
        }
    }, {
        test: function(element) {
            return $(element).data('select2') !== void 0;
        }
    });

    return Select2ValidationMessageHandlerView;
});
