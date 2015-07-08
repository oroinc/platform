define([
    'underscore',
    'orotranslation/js/translator',
    'jquery',
    'routing',
    'oroui/js/tools',
    'oroui/js/modal'
], function(_, __, $, routing, tools, Modal) {
    'use strict';

    var defaults = {
        headerServerError: __('Server error'),
        headerUserError: __('User input error'),
        message: __('Error! Incorrect server response.')
    };

    var ERROR_USER_INPUT = 'user_input_error';
    var errorHandler = {
        /**
         * Global error handler
         *
         * @param {Object} event
         * @param {Object} xhr
         * @param {Object} settings
         */
        handle: function(event, xhr, settings) {
            // enforce handling in case when called manually from user handler
            var force = settings.enforce || false;

            if (xhr.status === 401) {
                this._processRedirect();
            } else if (xhr.readyState === 4 && tools.debug && (typeof xhr.error !== 'function' || force)) {
                // show error in modal window in following cases:
                // when custom error handling is not added
                this.modalHandler(xhr);
            }
        },

        /**
         * Shows modal window
         *
         * @param {Object} xhr
         */
        modalHandler: function(xhr) {
            var message = defaults.message;
            if (tools.debug) {
                message += '<br><b>Debug:</b>' + xhr.responseText;
            }

            var responseObject = xhr.responseJSON || {};
            var errorType = responseObject.type;

            var modal = new Modal({
                title: errorType === ERROR_USER_INPUT ? defaults.headerUserError : defaults.headerServerError,
                content: responseObject.message || message,
                cancelText: false
            });
            modal.open();
        },

        /**
         * Redirects to login
         * @private
         */
        _processRedirect: function() {
            var hashUrl = '';
            // @TODO add extra parameter for redirect after login
            /*if (Navigation.isEnabled()) {
                var navigation = Navigation.getInstance();
                hashUrl = '#url=' + navigation.getHashUrl();
            }*/

            window.location.href = routing.generate('oro_user_security_login') + hashUrl;
        }
    };

    $(document).ajaxError(_.bind(errorHandler.handle, errorHandler));

    return errorHandler;
});
