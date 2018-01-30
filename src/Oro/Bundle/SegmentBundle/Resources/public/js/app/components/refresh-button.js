define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');
    var mediator = require('oroui/js/mediator');
    var options = {
        successMessage: 'oro.segment.refresh_dialog.success',
        errorMessage: 'oro.segment.refresh_dialog.error',
        title: 'oro.segment.refresh_dialog.title',
        okText: 'oro.segment.refresh_dialog.okText',
        content: 'oro.segment.refresh_dialog.content',
        reloadRequired: false
    };

    function run(url, reloadRequired) {
        mediator.execute('showLoading');
        $.post({
            url: url,
            errorHandlerMessage: __(options.errorMessage)
        }).done(function() {
            if (reloadRequired) {
                mediator.once('page:update', function() {
                    mediator.execute('showFlashMessage', 'success', __(options.successMessage));
                });
                mediator.execute('refreshPage');
            } else {
                mediator.execute('showFlashMessage', 'success', __(options.successMessage));
            }
        }).always(function() {
            mediator.execute('hideLoading');
        });
    }

    function onClick(reloadRequired, e) {
        e.preventDefault();

        var confirm = new DeleteConfirmation({
            title: __(options.title),
            okText: __(options.okText),
            content: __(options.content)
        });

        var url = $(e.target).data('url');

        confirm.on('ok', $.proxy(run, null, url, reloadRequired));
        confirm.open();
    }

    return function(additionalOptions) {
        _.extend(options, additionalOptions || {});
        var reloadRequired = Boolean(options.reloadRequired);
        var button = options._sourceElement;
        button.click($.proxy(onClick, null, reloadRequired));
    };
});
