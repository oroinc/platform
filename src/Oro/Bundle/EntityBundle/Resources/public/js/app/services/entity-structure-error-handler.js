define(function(require) {
    'use strict';

    const _ = require('underscore');
    const errorHelper = require('oroui/js/error');
    const mediator = require('oroui/js/mediator');
    const __ = require('orotranslation/js/translator');

    const entityStructureErrorHandler = {
        _showErrorMessage: _.throttle(function() {
            errorHelper.showErrorInUI(__('oro.entity.not_exist'));
        }, 100, {trailing: false}),

        /**
         * @param {EntityError} error
         */
        handle: function(error) {
            entityStructureErrorHandler._showErrorMessage();
            errorHelper.showErrorInConsole(error);
        },

        handleOutdatedDataError: _.throttle(function() {
            mediator.execute('showMessage', 'warning', __('oro.entity.structure_outdated.message'));
        }, 100, {trailing: false})
    };

    return entityStructureErrorHandler;
});
