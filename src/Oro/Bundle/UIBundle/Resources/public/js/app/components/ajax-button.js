define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var options = {};

    function onClick(e) {
        e.preventDefault();

        var method = $(e.currentTarget).data('method');
        var url = $(e.currentTarget).data('url');
        var redirect = $(e.currentTarget).data('redirect');
        var successMessage = $(e.currentTarget).data('success-message');
        var errorMessage = $(e.currentTarget).data('error-message');

        mediator.execute('showLoading');

        $.ajax({
            url: url,
            type: method,
            success: function(data) {
                mediator.execute(
                    'showFlashMessage',
                    'success',
                    data && data.message ? data.message : __(successMessage)
                );
                mediator.execute('redirectTo', {url: redirect}, {redirect: true});
            },
            errorHandlerMessage: __(errorMessage),
            complete: function() {
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
