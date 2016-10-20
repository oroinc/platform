define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var options = {};

    function onClick(e) {
        var method, url, redirect, successMessage, errorMessage;

        e.preventDefault();

        method         = $(e.currentTarget).data('method');
        url            = $(e.currentTarget).data('url');
        redirect       = $(e.currentTarget).data('redirect');
        successMessage = $(e.currentTarget).data('success-message');
        errorMessage   = $(e.currentTarget).data('error-message');

        mediator.execute('showLoading');

        $.ajax({
            url: url,
            type: method,
            success: function(data) {
                mediator.execute('showFlashMessage', 'success', data.message ? data.message : __(successMessage));
                mediator.execute('redirectTo', {url: redirect}, {redirect: true});
                mediator.execute('hideLoading');
            },
            error: function() {
                mediator.execute('showFlashMessage', 'error', __(errorMessage));
                mediator.execute('hideLoading');
            }
        });
    }

    return function(additionalOptions) {
        _.extend(options, additionalOptions || {});
        var button;
        button = options._sourceElement;
        button.click($.proxy(onClick, null));
    };
});
