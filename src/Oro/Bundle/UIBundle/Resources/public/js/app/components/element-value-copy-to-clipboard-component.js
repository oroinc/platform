define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var error = require('oroui/js/error');

    var ElementValueCopyToClipboardComponent;

    var BaseComponent = require('oroui/js/app/components/base/component');
    var messenger = require('oroui/js/messenger');

    ElementValueCopyToClipboardComponent = BaseComponent.extend({

        options: {
            elementSelector: '',
            messages: {
                copy_not_supported: 'oro.ui.element_value.messages.copy_not_supported',
                copied: 'oro.ui.element_value.messages.copied',
                copy_not_successful: 'oro.ui.element_value.messages.copy_not_successful'
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function ElementValueCopyToClipboardComponent() {
            ElementValueCopyToClipboardComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$button = options._sourceElement;

            this.initListeners();
        },

        initListeners: function() {
            this.$button.on('click', this.buttonClickHandler.bind(this));
        },

        buttonClickHandler: function() {
            var element = document.querySelector(this.options.elementSelector);
            var range = document.createRange();
            range.selectNode(element);
            window.getSelection().addRange(range);
            try {
                var copied = document.execCommand('copy');
                if (copied) {
                    messenger.notificationFlashMessage('success', __(this.options.messages.copied));
                } else {
                    messenger.notificationFlashMessage('warning', __(this.options.messages.copy_not_successful));
                }
            } catch (err) {
                error.showErrorInConsole(err);
                messenger.notificationFlashMessage('warning', __(this.options.messages.copy_not_supported));
            }

            window.getSelection().removeAllRanges();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$button.off('click');

            ElementValueCopyToClipboardComponent.__super__.dispose.call(this);
        }
    });

    return ElementValueCopyToClipboardComponent;
});
