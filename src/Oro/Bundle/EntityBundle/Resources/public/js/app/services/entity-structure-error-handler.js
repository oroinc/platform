define(function(require) {
    'use strict';

    var entityStructureErrorHandler;
    var _ = require('underscore');
    var errorHelper = require('oroui/js/error');
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
        }
    };

    return entityStructureErrorHandler;
});
