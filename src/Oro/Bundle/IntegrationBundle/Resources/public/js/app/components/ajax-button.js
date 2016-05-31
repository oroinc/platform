define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var options = {
        successMessage: '',
        errorMessage: 'oro.ui.unexpected_error',
        redirect: '/'
    };

    function onClick(e) {
        var url;
        e.preventDefault();

        url = $(e.target).data('url');
        mediator.execute('showLoading');
        $.get(url, function(data) {
            mediator.execute('showFlashMessage', 'success', data.message);
            mediator.execute('redirectTo', {url: options.redirect}, {redirect: true});
        }).error(function() {
            mediator.execute('showFlashMessage', 'error', __(options.errorMessage));
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
