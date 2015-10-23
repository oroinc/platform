define([
    'jquery',
    'underscore',
    'oroui/js/tools',
    'oroui/js/tools/multi-use-resource-manager',
    'cryptojs/sha256',
    'bootstrap'
], function($, _, tools, MultiUseResourceManager, CryptoJS) {
    'use strict';

    var defaults = {
        container: '',
        delay: false,
        template: $.noop,
        insertMethod: 'appendTo'
    };
    var queue = [];
    var groupedMessages = {};
    var notFlashTypes = ['error', 'danger', 'warning', 'alert'];
    var console = window.console;

    /**
     * Same arguments as for Oro.NotificationMessage
     */
    function showMessage(type, message, options) {
        var opt = _.extend({}, defaults, options || {});
        var $el = $(opt.template({type: type, message: message}))[opt.insertMethod](opt.container);
        var delay = opt.delay || (opt.flash && 5000);
        var actions = {close: _.bind($el.alert, $el, 'close')};
        if (opt.namespace) {
            $el.attr('data-messenger-namespace', opt.namespace);
        }
        if (delay) {
            _.delay(actions.close, delay);
        }
        return actions;
    }

    /**
     * @export oroui/js/messenger
     * @name   oro.messenger
     */
    return {
            /**
             * Shows notification message
             *
             * @param {(string|boolean)} type 'error'|'success'|false
             * @param {string} message text of message
             * @param {Object=} options
             *
             * @param {(string|jQuery)} options.container selector of jQuery with container element
             * @param {(number|boolean)} options.delay time in ms to auto close message
             *      or false - means to not close automatically
             * @param {Function} options.template template function
             * @param {boolean} options.flash flag to turn on default delay close call, it's 5s
             *
             * @return {Object} collection of methods - actions over message element,
             *      at the moment there's only one method 'close', allows to close the message
             */
            notificationMessage:  function(type, message, options) {
                var container = (options || {}).container ||  defaults.container;
                var args = Array.prototype.slice.call(arguments);
                var actions = {close: $.noop};
                if (container && $(container).length) {
                    actions = showMessage.apply(null, args);
                } else {
                    // if container is not ready then save message for later
                    queue.push([args, actions]);
                }
                return actions;
            },

            /**
             * Shows flash notification message
             *
             * @param {(string|boolean)} type 'error'|'success'|false
             * @param {string} message text of message
             * @param {Object=} options
             *
             * @param {(string|jQuery)} options.container selector of jQuery with container element
             * @param {(number|boolean)} options.delay time in ms to auto close message
             *      or false - means to not close automatically
             * @param {Function} options.template template function
             * @param {boolean} options.flash flag to turn on default delay close call, it's 5s
             *
             * @return {Object} collection of methods - actions over message element,
             *      at the moment there's only one method 'close', allows to close the message
             */
            notificationFlashMessage: function(type, message, options) {
                var isFlash = notFlashTypes.indexOf(type) === -1;
                var namespace = (options || {}).namespace;

                if (!namespace) {
                    namespace = CryptoJS.SHA256(message + this.type).toString();

                    if (!options) {
                        options = {
                            namespace: null
                        };
                    }
                    options.namespace = namespace;
                }

                this.clear(namespace, options);

                return this.notificationMessage(type, message, _.extend({flash: isFlash}, options));
            },

            /**
             * Shows error message
             *
             * @param {string} message text of message
             * @param {*=} err an error. Can be a string, exception object or an object represents JSON REST response
             *
             * @return {Object} collection of methods - actions over message element,
             *      at the moment there's only one method 'close', allows to close the message
             */
            showErrorMessage: function(message, err) {
                var msg = message;
                if (!_.isUndefined(err) && !_.isNull(err)) {
                    if (!_.isUndefined(console)) {
                        console.error(_.isUndefined(err.stack) ? err : err.stack);
                    }
                    if (tools.debug) {
                        if (!_.isUndefined(err.message)) {
                            msg += ': ' + err.message;
                        } else if (!_.isUndefined(err.errors) && _.isArray(err.errors)) {
                            msg += ': ' + err.errors.join();
                        } else if (_.isString(err)) {
                            msg += ': ' + err;
                        }
                    }
                }
                return this.notificationFlashMessage('error', msg);
            },

            setup: function(options) {
                _.extend(defaults, options);

                while (queue.length) {
                    var args = queue.shift();
                    _.extend(args[1], showMessage.apply(null, args[0]));
                }
            },

            addMessage: function(type, message, options) {
                var args = [type, message, _.extend({flash: true}, options)];
                var actions = {close: $.noop};

                queue.push([args, actions]);
            },

            showProcessingMessage: function(message, promise, type) {
                if (!type) {
                    type = 'process';
                }
                var _this = this;
                if (!groupedMessages[message]) {
                    groupedMessages[message] = new MultiUseResourceManager({
                        listen: {
                            'construct': function() {
                                this.alert = _this.notificationMessage(type, message, {flash: false});
                            },
                            'dispose': function() {
                                this.alert.close();
                                groupedMessages[message].dispose();
                                delete groupedMessages[message];
                            }
                        }
                    });
                }
                var holderId = groupedMessages[message].hold();
                promise.always(function() {
                    groupedMessages[message].release(holderId);
                });
            },

            /**
             * Clears all messages within namespace
             *
             * @param {string} namespace
             * @param {Object=} options
             */
            clear: function(namespace, options) {
                var opt = _.extend({}, defaults, options || {});
                $(opt.container).find('[data-messenger-namespace=' + namespace + ']').remove();
            }
        };
});
