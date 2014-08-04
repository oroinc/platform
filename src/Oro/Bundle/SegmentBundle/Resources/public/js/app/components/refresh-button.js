/*jslint nomen: true*/
/*global define*/
define(function (require) {
    'use strict';

    var $ = require('jquery'),
        __ = require('orotranslation/js/translator'),
        routing = require('routing'),
        DeleteConfirmation = require('oroui/js/delete-confirmation'),
        mediator = require('oroui/js/mediator');

    function run(url, reloadRequired) {
        mediator.execute('showLoading');
        $.post(url, function () {
            var successMessage = __('Segment successfully processed.');
            if (reloadRequired) {
                mediator.once("page:update", function () {
                    mediator.execute('showFlashMessage', 'success', successMessage);
                });
                mediator.execute('refreshPage');
            } else {
                mediator.execute('showFlashMessage', 'success', successMessage);
            }
        }).error(function () {
            mediator.execute('showFlashMessage',
                'error', __('An unidentified error has occurred. Please contact your Administrator.'));
        }).always(function () {
            mediator.execute('hideLoading');
        });
    }

    function onClick(reloadRequired, e) {
        var confirm, url;
        e.preventDefault();

        confirm = new DeleteConfirmation({
            title:    __('Confirm action'),
            okText:   __('Yes, I Agree'),
            content:  __('Please confirm that you want to refresh this segment.')
        });

        url = $(e.target).data('url');

        confirm.on('ok', $.proxy(run, null, url, reloadRequired));
        confirm.open();
    }

    return function (options) {
        var reloadRequired, button;
        reloadRequired = Boolean(options.reloadRequired);
        button = options._sourceElement;
        button.click($.proxy(onClick, null, reloadRequired));
    };
});
