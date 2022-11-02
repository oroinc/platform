define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const DeleteConfirmation = require('oroui/js/delete-confirmation');
    const mediator = require('oroui/js/mediator');
    const options = {
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

        const confirm = new DeleteConfirmation({
            title: __(options.title),
            okText: __(options.okText),
            content: __(options.content)
        });

        const url = $(e.target).data('url');

        confirm.on('ok', _.partial(run, url, reloadRequired));
        confirm.open();
    }

    return function(additionalOptions) {
        _.extend(options, additionalOptions || {});
        const reloadRequired = Boolean(options.reloadRequired);
        const button = options._sourceElement;
        button.click(_.partial(onClick, reloadRequired));
    };
});
