/*jslint nomen: true*/
/*global define*/
define(function (require) {
    'use strict';

    var $ = require('jquery'),
        __ = require('orotranslation/js/translator'),
        routing = require('routing'),
        DeleteConfirmation = require('oroui/js/delete-confirmation'),
        mediator = require('oroui/js/mediator'),
        options = {
            successMessage: 'oro.segment.refresh_dialog.success',
            errorMessage: 'oro.segment.refresh_dialog.error',
            title: 'oro.segment.refresh_dialog.title',
            okText: 'oro.segment.refresh_dialog.okText',
            content: 'oro.segment.refresh_dialog.content',
            reloadRequired: false
        };

    function run(url, reloadRequired) {
        mediator.execute('showLoading');
        $.post(url, function () {
            if (reloadRequired) {
                mediator.once("page:update", function () {
                    mediator.execute('showFlashMessage', 'success', __(options.successMessage));
                });
                mediator.execute('refreshPage');
            } else {
                mediator.execute('showFlashMessage', 'success', __(options.successMessage));
            }
        }).error(function () {
            mediator.execute('showFlashMessage', 'error', __(options.errorMessage));
        }).always(function () {
            mediator.execute('hideLoading');
        });
    }

    function onClick(reloadRequired, e) {
        var confirm, url;
        e.preventDefault();

        confirm = new DeleteConfirmation({
            title:   __(options.title),
            okText:  __(options.okText),
            content: __(options.content)
        });

        url = $(e.target).data('url');

        confirm.on('ok', $.proxy(run, null, url, reloadRequired));
        confirm.open();
    }

    return function (additionalOptions) {
        _.extend(options, additionalOptions || {});
        var reloadRequired, button;
        reloadRequired = Boolean(options.reloadRequired);
        button = options._sourceElement;
        button.click($.proxy(onClick, null, reloadRequired));
    };
});
