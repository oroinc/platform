define(function(require, exports, module) {
    'use strict';

    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const tools = require('oroui/js/tools');
    const _ = require('underscore');
    const config = require('module-config').default(module.id);

    const defaults = _.defaults(config, {
        message: _.__('oro.ui.error.performing'),
        loginRoute: 'oro_user_security_login'
    });

    const console = window.console;

    const errorHandler = {
        /**
         * Global Ajax error handler
         *
         * @param {Object} event
         * @param {Object} xhr
         * @param {Object} settings
         */
        handle: function(event, xhr, settings) {
            if (this.isXHRStatus(xhr, 401)) {
                this._processRedirect(xhr.responseJSON || {});
            } else if (xhr.readyState === 4) {
                const errorMessage = this.getErrorMessage(event, xhr, settings);
                const isShowError = Boolean(errorMessage);

                if (!isShowError) {
                    this.showErrorInConsole(xhr);
                } else {
                    this.showError(xhr, errorMessage);
                }
            }
        },

        isXHRStatus: function(xhr, testStatus) {
            let status = 0;
            if (_.isObject(xhr)) {
                status = xhr.status;
                const responseCode = _.result(xhr.responseJSON, 'code');
                if (!_.isUndefined(responseCode)) {
                    status = responseCode;
                }
            }
            return status === testStatus;
        },

        /**
         * @param {Object} event
         * @param {Object} xhr
         * @param {Object} settings
         * @return {Boolean}
         */
        getErrorMessage: function(event, xhr, settings) {
            let errorMessage = true;

            if (settings.errorHandlerMessage !== undefined && !this.isXHRStatus(xhr, 403)) {
                errorMessage = settings.errorHandlerMessage;
                if (_.isFunction(errorMessage)) {
                    errorMessage = errorMessage(event, xhr, settings);
                }
            }

            return errorMessage;
        },

        /**
         * @param {Object|Error} context
         * @param {String|null} errorMessage
         */
        showError: function(context, errorMessage) {
            this.showErrorInUI(_.isString(errorMessage) ? errorMessage : context);
            this.showErrorInConsole(context);
        },

        /**
         * @param {Object|Error|String} context
         */
        showErrorInUI: function(context) {
            if (tools.debug && context instanceof Error) {
                this.showFlashError(context.toString());
            } else if (_.isString(context)) {
                this.showFlashError(context);
            } else if (_.isObject(context) && context.responseJSON && context.responseJSON.message) {
                const message = this.prepareErrorMessage(context.responseJSON);
                this.showFlashError(message);
            } else if (this.isXHRStatus(context, 403)) {
                this.showFlashError(_.__('oro.ui.forbidden_error'));
            } else if (this.isXHRStatus(context, 413)) {
                this.showFlashError(_.__('oro.ui.request_too_large_error'));
            } else {
                this.showFlashError(defaults.message);
            }
        },

        prepareErrorMessage: function(response) {
            let message = response.message + ': ';
            if (_.has(response, 'errors') && !_.isNull(response.errors)) {
                _.each(response.errors.children, function(child) {
                    if (_.has(child, 'errors') && !_.isNull(child.errors)) {
                        _.each(child.errors, function(error) {
                            message += error + ', ';
                        });
                    }
                });
            }
            message = message.substring(0, message.length - 2);
            return message;
        },

        /**
         * @param {Object|Error} context
         */
        showErrorInConsole: function(context) {
            const errorStyle = 'font-weight: bold;';
            console.error('%cDebug:', errorStyle, context);
        },

        /**
         * @param {String} message
         */
        showFlashError: function(message) {
            mediator.execute('showFlashMessage', 'error', message);
        },

        /**
         * Redirects to login
         *
         * @param {Object} response
         * @private
         */
        _processRedirect: function(response) {
            const hashUrl = '';
            // @TODO add extra parameter for redirect after login
            /* if (Navigation.isEnabled()) {
                var navigation = Navigation.getInstance();
                hashUrl = '#url=' + navigation.getHashUrl();
            } */
            window.location.href = response.redirectUrl || (routing.generate(defaults.loginRoute) + hashUrl);
        }
    };

    return errorHandler;
});
