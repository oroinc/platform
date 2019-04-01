define(function(require) {
    'use strict';

    var ElementValueCopyToClipboardView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var tools = require('oroui/js/tools');
    var error = require('oroui/js/error');
    var BaseView = require('oroui/js/app/views/base/view');
    var messenger = require('oroui/js/messenger');

    ElementValueCopyToClipboardView = BaseView.extend({
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
         * @inheritDoc
         */
        constructor: function ElementValueCopyToClipboardView() {
            ElementValueCopyToClipboardView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            ElementValueCopyToClipboardView.__super__.initialize.call(this, options);
        },

        clickHandler: function() {
            var $source = this.options.elementSelector !== null ? $(this.options.elementSelector) : this.$el;
            var textToCopy = $source.text();
            var $textarea = this.createUtilityTextarea(textToCopy);

            $source.closest('.ui-dialog, body').append($textarea);

            if (tools.isIOS()) {
                var selection = window.getSelection();
                var range = document.createRange();

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
            var $textarea = $('<textarea/>', {'contenteditable': 'true', 'aria-hidden': 'true'});

            $textarea.css({position: 'fixed', top: '-1000px'}).val(value);

            return $textarea;
        }
    });

    return ElementValueCopyToClipboardView;
});
