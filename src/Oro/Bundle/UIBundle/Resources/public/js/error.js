/*global define*/
define(['underscore', 'orotranslation/js/translator', 'backbone', 'routing', 'oroui/js/app', 'oroui/js/modal',
    'oronavigation/js/navigation'
], function (_, __, Backbone, routing, app, Modal, Navigation) {
    'use strict';

    var defaults = {
            headerServerError: __('Server error'),
            headerUserError:   __('User input error'),
            message:           __('Error! Incorrect server response.')
        },

        ERROR_USER_INPUT = 'user_input_error',

        /**
         * @export oroui/js/error
         * @name oroui.error
         */
            error = {
                dispatch: function (model, xhr, options) {
                    var self = error.dispatch;
                    self.init(model, xhr, _.extend({}, defaults, options));
                }
        },
        sync = Backbone.sync;

    // Override default Backbone.sync
    Backbone.sync = function (method, model, options) {
        options = options || {};
        if (!_.has(options, 'error')) {
            options.error = error.dispatch;
        }

        sync.call(Backbone, method, model, options);
    };

    _.extend(error.dispatch, {
        /**
         * Error dispatch
         *
         * @param {Object} model
         * @param {Object} xhr
         * @param {Object} options
         */
        init: function (model, xhr, options) {
            if (xhr.status === 401) {
                this._processRedirect();
            } else if (xhr.readyState === 4) {
                this._processModal(xhr, options);
            }
        },

        /**
         * Shows modal window
         * @param {Object} xhr
         * @param {Object} options
         * @private
         */
        _processModal: function (xhr, options) {
            var modal,
                message = options.message;
            if (app.debug) {
                message += '<br><b>Debug:</b>' + xhr.responseText;
            }

            var responseObject = xhr.responseJSON || {},
                errorType = responseObject.type;

            modal = new Modal({
                title:      errorType === ERROR_USER_INPUT  ? options.headerUserError : options.headerServerError,
                content:    responseObject.message || message,
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
    });

    return error;
});
