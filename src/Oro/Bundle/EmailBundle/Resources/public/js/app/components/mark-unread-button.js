/*jslint nomen: true*/
/*global define*/
define(function (require) {
    'use strict';

    var $ = require('jquery'),
        __ = require('orotranslation/js/translator'),
        routing = require('routing'),
        mediator = require('oroui/js/mediator'),
        options = {
            successMessage: 'oro.email.menu.mark_unread.success.message',
            errorMessage: 'oro.email.menu.mark_unread.error.message',
            redirect: '/'
        };

    function onClick(e) {
        var url;
        e.preventDefault();

        url = $(e.target).data('url');
        mediator.execute('showLoading');
        $.post(url, function () {
            mediator.execute('showFlashMessage', 'success', __(options.successMessage));
            mediator.execute('redirectTo', {url: options.redirect}, {redirect: true});
        }).error(function () {
            mediator.execute('showFlashMessage', 'error', __(options.errorMessage));
        }).always(function () {
            mediator.execute('hideLoading');
        });
    }

    return function (additionalOptions) {
        _.extend(options, additionalOptions || {});
        var button;
        button = options._sourceElement;
        button.click($.proxy(onClick, null));
    };
});
