/*global define*/
define(['underscore', 'orotranslation/js/translator', 'jquery', 'routing', 'oroui/js/app', 'oroui/js/modal', 'oronavigation/js/navigation'
], function (_, __, $, routing, app, Modal, Navigation) {
    'use strict';

    var defaults = {
            headerServerError: __('Server error'),
            headerUserError: __('User input error'),
            message: __('Error! Incorrect server response.')
        },

        ERROR_USER_INPUT = 'user_input_error',
        errorHandler = {
            /**
             * Global error handler
             *
             * @param {Object} event
             * @param {Object} xhr
             */
            handle: function (event, xhr) {
                if (xhr.status === 401 || xhr.status === 403) {
                    this._processRedirect();
                } else if (xhr.readyState === 4 && (app.debug || typeof xhr.error !== 'function')) {
                    // show error in modal window in following cases:
                    // when custom error handling is not added
                    // when in debug mode
                    this._processModal(xhr);
                }
            },

            /**
             * Shows modal window
             * @param {Object} xhr
             * @private
             */
            _processModal: function (xhr) {
                var modal,
                    message = defaults.message;
                if (app.debug) {
                    message += '<br><b>Debug:</b>' + xhr.responseText;
                }

                var responseObject = xhr.responseJSON || {},
                    errorType = responseObject.type;

                modal = new Modal({
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
            _processRedirect: function () {
                var hashUrl = '';
                if (Navigation.isEnabled()) {
                    var navigation = Navigation.getInstance();
                    hashUrl = '#url=' + navigation.getHashUrl();
                }

                window.location.href = routing.generate('oro_user_security_login') + hashUrl;
            }
        };

    $(document).ajaxError(_.bind(errorHandler.handle, errorHandler));

    return errorHandler;
});
