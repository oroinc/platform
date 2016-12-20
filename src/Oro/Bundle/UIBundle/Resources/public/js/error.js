define([
    'module',
    'routing',
    'oroui/js/mediator',
    'oroui/js/tools',
    'oroui/js/modal',
    'underscore'
], function(module, routing, mediator, tools, Modal, _) {
    'use strict';

    var defaults = _.defaults(module.config(), {
        headerServerError: _.__('Server error'),
        headerUserError: _.__('User input error'),
        message: _.__('oro.ui.error')
    });

    var ERROR_USER_INPUT = 'user_input_error';
    var console = window.console;

    var errorHandler = {
        /**
         * Global Ajax error handler
         *
         * @param {Object} event
         * @param {Object} xhr
         * @param {Object} settings
         */
        handle: function(event, xhr, settings) {
            if (xhr.status === 401) {
                this._processRedirect();
            } else if (xhr.readyState === 4) {
                if (this.isShowError(event, xhr, settings)) {
                    this.showError(xhr);
                } else {
                    this.showErrorInConsole(xhr);
                }
            }
        },

        /**
         * @param {Object} event
         * @param {Object} xhr
         * @param {Object} settings
         * @return {Boolean}
         */
        isShowError: function(event, xhr, settings) {
            var errorOutput = true;

            if (settings.errorOutput !== undefined) {
                errorOutput = settings.errorOutput;
                if (_.isFunction(errorOutput)) {
                    errorOutput = errorOutput(event, xhr, settings);
                }
            }

            return Boolean(errorOutput);
        },

        /**
         * @param {Object|Error} context
         */
        showError: function(context) {
            this.showErrorInUI(context);
            this.showErrorInConsole(context);
        },

        /**
         * @param {Object|Error} context
         */
        showErrorInUI: function(context) {
            if (tools.debug && context instanceof Error) {
                this.showFlashError(context.toString());
            } else {
                this.showFlashError(defaults.message);
            }
        },

        /**
         * @param {Object|Error} context
         */
        showErrorInConsole: function(context) {
            var errorStyle = 'font-weight: bold;';
            console.error('%cDebug:', errorStyle, context);
        },

        /**
         * @param {String} message
         */
        showFlashError: function(message) {
            mediator.execute('showFlashMessage', 'error', message);
        },

        /**
         * @param {Error} xhr
         * @deprecated
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
         *
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

    return errorHandler;
});
