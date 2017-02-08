define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var options = {
        successMessage: 'oro.email.menu.mark_unread.success.message',
        errorMessage: 'oro.email.menu.mark_unread.error.message',
        redirect: '/'
    };

    function onClick(e) {
        var url;
        e.preventDefault();

        url = $(e.target).data('url');
        mediator.execute('showLoading');
        $.post({
            url: url,
            errorHandlerMessage: __(options.errorMessage)
        }).done(function() {
            mediator.execute('showFlashMessage', 'success', __(options.successMessage));
            mediator.execute('redirectTo', {url: options.redirect}, {redirect: true});
        }).always(function() {
            mediator.execute('hideLoading');
        });
    }

    return function(additionalOptions) {
        _.extend(options, additionalOptions || {});
        var button;
        button = options._sourceElement;
        button.click($.proxy(onClick, null));
    };
});
