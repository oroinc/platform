define(function(require) {
    'use strict';

    var entityStructureErrorHandler;
    var _ = require('underscore');
    var errorHelper = require('oroui/js/error');
    var mediator = require('oroui/js/mediator');
    var __ = require('orotranslation/js/translator');

    entityStructureErrorHandler = {
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
