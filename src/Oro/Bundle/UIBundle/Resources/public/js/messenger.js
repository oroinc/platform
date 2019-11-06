define([
    'jquery',
    'underscore',
    'tpl-loader!oroui/templates/message-item.html',
    'oroui/js/tools',
    'oroui/js/tools/multi-use-resource-manager',
    'cryptojs/sha256',
    'oroui/js/mediator',
    'oroui/js/error',
    'bootstrap'
], function($, _, template, tools, MultiUseResourceManager, SHA256, mediator, error) {
    'use strict';

    const defaults = {
        container: '',
        temporaryContainer: '[data-role="messenger-temporary-container"]',
        delay: false,
        template: template,
        insertMethod: 'appendTo',
        style: 'default'
    };
    let queue = [];
    const groupedMessages = {};
    const notFlashTypes = ['error', 'danger', 'warning', 'alert'];
    const noFlashTags = ['a'];

    const resolveContainer = function(options) {
        if ($(options.container).is(defaults.container) && $(defaults.temporaryContainer).length) {
            options.container = defaults.temporaryContainer;
        }
    };

    /**
     * Same arguments as for Oro.NotificationMessage
     */
    function showMessage(type, message, options) {
        const opt = _.extend({}, defaults, options || {});
        resolveContainer(opt);
        const $el = $(opt.template({
            type: type,
            message: message,
            style: opt.style
        }))[opt.insertMethod](opt.container);
        if (opt.onClose) {
            $el.find('button.close').click(opt.onClose);
        }

        if (opt.hideCloseButton) {
            $el.find('[data-dismiss="alert"]').remove();
        }

        const delay = opt.delay || (opt.flash && 5000);
        const actions = {
            close: function() {
                const result = $el.alert('close');
                mediator.trigger('layout:adjustHeight');
                return result;
            },
            namespace: opt.namespace
        };
        if (opt.namespace) {
            $el.attr('data-messenger-namespace', opt.namespace);
        }
        if (delay) {
            _.delay(actions.close, delay);
        }
        mediator.trigger('layout:adjustHeight');
        return actions;
    }

    /**
     * @export oroui/js/messenger
     * @name   oro.messenger
     */
    return {
        /**
         * Shows notification message.
         * By default, the message is displayed until an user close it.
         * If you want to close the message you can specify 'flash' or 'delay' option.
         * Also in this case you can use `notificationFlashMessage` method.
         *
         * @param {string} type 'error'|'success'|'warning'
         * @param {string} message text of message
         * @param {Object=} options
         *
         * @param {(string|jQuery)} options.container selector of jQuery with container element
         * @param {(number|boolean)} options.delay time in ms to auto close message
         *      or false - means to not close automatically
         * @param {Function} options.template template function
         * @param {boolean} options.flash flag to turn on default delay close call, it's 5s
         * @param {boolean} options.afterReload whether the message should be shown after a page is reloaded
         *
         * @return {Object} collection of methods - actions over message element,
         *      at the moment there's only one method 'close', allows to close the message
         */
        notificationMessage: function(...args) {
            const container = (args[2] || {}).container || defaults.container;
            const afterReload = (args[2] || {}).afterReload || false;
            let afterReloadQueue = [];
            let actions = {close: $.noop};

            if (afterReload && window.localStorage) {
                afterReloadQueue = JSON.parse(localStorage.getItem('oroAfterReloadMessages') || '[]');
                afterReloadQueue.push(args);
                localStorage.setItem('oroAfterReloadMessages', JSON.stringify(afterReloadQueue));
            } else if (container && $(container).length) {
                actions = showMessage(...args);
            } else {
                // if container is not ready then save message for later
                queue.push(args);
            }
            return actions;
        },

        /**
         * Shows flash notification message.
         * By default, the message is displayed for 5 seconds.
         * To change this you can use `delay` option.
         *
         * @param {string} type 'error'|'success'|'warning'
         * @param {string} message text of message
         * @param {Object=} options
         *
         * @param {(string|jQuery)} options.container selector of jQuery with container element
         * @param {(number|boolean)} options.delay time in ms to auto close message
         *      or false - means to not close automatically
         * @param {Function} options.template template function
         * @param {boolean} options.flash flag to turn on default delay close call, it's 5s
         * @param {boolean} options.afterReload whether the message should be shown after a page is reloaded
         *
         * @return {Object} collection of methods - actions over message element,
         *      at the moment there's only one method 'close', allows to close the message
         */
        notificationFlashMessage: function(type, message, options) {
            const isFlash = notFlashTypes.indexOf(type) === -1 && !this._containsNoFlashTags(message);
            let namespace = (options || {}).namespace;

            if (!namespace) {
                // eslint-disable-next-line new-cap
                namespace = SHA256(message + this.type).toString();

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
            const msg = message;
            if (!_.isUndefined(err) && !_.isNull(err)) {
                error.showErrorInConsole(err);
            }
            return this.notificationFlashMessage('error', msg);
        },

        setup: function(options) {
            _.extend(defaults, options);
            $(document).on('remove', defaults.temporaryContainer, this.removeTemporaryContainer);
        },

        flushStoredMessages: function() {
            if (window.localStorage) {
                queue = queue.concat(JSON.parse(localStorage.getItem('oroAfterReloadMessages') || '[]'));
                localStorage.removeItem('oroAfterReloadMessages');
            }

            while (queue.length) {
                showMessage(...queue.shift());
            }
        },

        addMessage: function(type, message, options) {
            queue.push([type, message, _.extend({flash: true}, options)]);
        },

        showProcessingMessage: function(message, promise, type) {
            if (!type) {
                type = 'process';
            }
            const _this = this;
            if (!groupedMessages[message]) {
                groupedMessages[message] = new MultiUseResourceManager({
                    listen: {
                        constructResource: function() {
                            this.alert = _this.notificationMessage(type, message, {flash: false});
                        },
                        disposeResource: function() {
                            this.alert.close();
                            groupedMessages[message].dispose();
                            delete groupedMessages[message];
                        }
                    }
                });
            }
            const holderId = groupedMessages[message].hold();
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
            const opt = _.extend({}, defaults, options || {});
            $(opt.container).add(opt.temporaryContainer)
                .find('[data-messenger-namespace=' + namespace + ']').remove();
        },

        removeTemporaryContainer: function() {
            $(defaults.container).append($(this).children());
        },

        /**
         * Check if given string contains no flash tags
         *
         * @param {string} string
         * @return {boolean}
         */
        _containsNoFlashTags: function(string) {
            return _.some(noFlashTags, function(tag) {
                return $('<div>').append(string).find(tag).length > 0;
            });
        }
    };
});
