define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const tools = require('oroui/js/tools');
    const error = require('oroui/js/error');
    const BaseView = require('oroui/js/app/views/base/view');
    const messenger = require('oroui/js/messenger');

    const ElementValueCopyToClipboardView = BaseView.extend({
        options: {
            elementSelector: null,
            messages: {
                copy_not_supported: 'oro.ui.element_value.messages.copy_not_supported',
                copied: 'oro.ui.element_value.messages.copied',
                copy_not_successful: 'oro.ui.element_value.messages.copy_not_successful'
            }
        },

        events: {
            click: 'clickHandler'
        },

        /**
         * @inheritdoc
         */
        constructor: function ElementValueCopyToClipboardView(options) {
            ElementValueCopyToClipboardView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            ElementValueCopyToClipboardView.__super__.initialize.call(this, options);
        },

        clickHandler: function() {
            const $source = this.options.elementSelector !== null ? $(this.options.elementSelector) : this.$el;
            const textToCopy = $source.text();
            const $textarea = this.createUtilityTextarea(textToCopy);

            $source.closest('.ui-dialog, body').append($textarea);

            if (tools.isIOS()) {
                const selection = window.getSelection();
                const range = document.createRange();

                range.selectNodeContents($textarea[0]);
                selection.removeAllRanges();
                selection.addRange(range);
                $textarea[0].setSelectionRange(0, textToCopy.length);
            } else {
                $textarea.select();
            }

            try {
                if (document.execCommand('copy')) {
                    this.onCopySuccess();
                } else {
                    this.onCopyFailed();
                }
            } catch (err) {
                error.showErrorInConsole(err);
                this.onCopyNotSupported();
            }

            $textarea.remove();
        },

        onCopySuccess: function() {
            messenger.notificationFlashMessage('success', __(this.options.messages.copied));
        },

        onCopyFailed: function() {
            messenger.notificationFlashMessage('warning', __(this.options.messages.copy_not_successful));
        },

        onCopyNotSupported: function() {
            messenger.notificationFlashMessage('warning', __(this.options.messages.copy_not_supported));
        },

        /**
         * Creates jQuery object with textarea stylized to be placed outside screen and containing text to copy
         *
         * @param {string} value
         * @return {jQuery}
         */
        createUtilityTextarea: function(value) {
            const $textarea = $('<textarea/>', {'contenteditable': 'true', 'aria-hidden': 'true'});

            $textarea.css({position: 'fixed', top: '-1000px'}).val(value);

            return $textarea;
        }
    });

    return ElementValueCopyToClipboardView;
});
