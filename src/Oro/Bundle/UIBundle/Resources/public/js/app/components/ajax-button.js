define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var options = {
        method: 'GET',
        successMessage: '',
        errorMessage: 'oro.ui.unexpected_error',
        redirect: '/'
    };

    function onClick(e) {
        var method, url;
        e.preventDefault();

        method = $(e.target).data('method');
        url = $(e.target).data('url');

        mediator.execute('showLoading');

        $.ajax({
            url: url,
            type: method,
            success: function(data) {
                mediator.execute('showFlashMessage', 'success', data.message);
                mediator.execute('redirectTo', {url: options.redirect}, {redirect: true});
                mediator.execute('hideLoading');
            },
            error: function() {
                mediator.execute('showFlashMessage', 'error', __(options.errorMessage));
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
