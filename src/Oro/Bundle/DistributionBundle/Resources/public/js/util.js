/* global $, bootbox, Util */
// eslint-disable-next-line no-unused-vars
function Util(rootMessagesElements) {
    'use strict';

    rootMessagesElements = rootMessagesElements || $('#alerts');

    var messageTemplate = '<div class="alert fade in alert-{message-type} alert-dismissible" role="alert"> ' +
        '<span class="fa-{message-type}" aria-hidden="true"></span>' +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span></button> ' +
        '{message} ' +
        '</div>';

    var defaultDialogOptions = {
        title: 'Is this something you really want to do?',
        message: 'Test message. Just a stub',
        className: 'confirm-dialog',
        buttons: {
            'cancel': {
                label: 'Cancel',
                callback: function() {
                }
            },
            'continue': {
                className: 'btn-danger',
                callback: function() {
                }
            }
        }
    };

    function displayMessage(type, message) {
        var html = messageTemplate
            .replace(/\{message-type}/g, type)
            .replace('{message}', message);
        rootMessagesElements.append(html);
    }

    return {
        success: function(message) {
            displayMessage('success', message);
        },
        error: function(message) {
            displayMessage('error', message);
        },
        confirm: function(title, message, continueCallback, continueLabel, cancelCallback) {
            message = message.replace(/\n/g, '<br />');
            continueCallback = continueCallback || function() {
            };
            continueLabel = continueLabel || 'Yes, Continue';
            cancelCallback = cancelCallback || function() {
            };

            var dialogOptions = $.extend(true, defaultDialogOptions, {
                title: title,
                message: message,
                onEscape: cancelCallback,
                buttons: {
                    'cancel': {
                        callback: cancelCallback
                    },
                    'continue': {
                        label: continueLabel,
                        callback: continueCallback
                    }
                }
            });

            bootbox.dialog(dialogOptions);
        },
        redirect: function(url, message) {
            $.cookie('message', message, {path: '/'});
            window.location = url;
        },
        displayCookieMessage: function() {
            var message = $.cookie('message');
            if (message) {
                this.success(message);
                $.removeCookie('message', {path: '/'});
            }
        }
    };
}
