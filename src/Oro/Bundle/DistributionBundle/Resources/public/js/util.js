function Util(rootMessagesElements) {

    rootMessagesElements = rootMessagesElements || $('#alerts');

    var messageTemplate = '<div class="alert fade in alert-{message-type}"> \
        <i class="icon icon-{message-type}"></i>\
        <button type="button" class="close" data-dismiss="alert">&times;</button> \
        {message} \
        </div>';

    var defaultDialogOptions = {
        title: 'Is this something you really want to do?',
        message: 'Test message. Just a stub',
        className: 'confirm-dialog',
        buttons: {
            cancel: {
                label: 'Cancel',
                callback: function () {
                }
            },
            continue: {
                className: 'btn-danger',
                callback: function () {
                }
            }
        }
    };

    function displayMessage(type, message) {
        var html = messageTemplate
            .replace(/{message-type}/g, type)
            .replace('{message}', message);
        rootMessagesElements.append(html);
    }

    return {
        success: function (message) {
            displayMessage('success', message);
        },
        error: function (message) {
            displayMessage('error', message);
        },
        confirm: function (title, message, continueCallback, continueLabel, cancelCallback) {
            message = message.replace(/\n/g, '<br />');
            continueCallback = continueCallback || function () {
            };
            continueLabel = continueLabel || 'Yes, Continue';
            cancelCallback = cancelCallback || function () {
            };

            var dialogOptions = $.extend(true, defaultDialogOptions, {
                title: title,
                message: message,
                onEscape: cancelCallback,
                buttons: {
                    cancel: {
                        callback: cancelCallback
                    },
                    continue: {
                        label: continueLabel,
                        callback: continueCallback
                    }
                }
            });

            bootbox.dialog(dialogOptions);
        },
        redirect: function (url, message) {
            $.cookie('message', message, {path: '/'});
            window.location = url;
        },
        displayCookieMessage: function () {
            var message = $.cookie('message');
            if (message) {
                this.success(message);
                $.removeCookie('message', {path: '/'});
            }
        }
    };
}
