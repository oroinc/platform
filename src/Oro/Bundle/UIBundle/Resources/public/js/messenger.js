define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const template = require('tpl-loader!oroui/templates/message-item.html');
    const MultiUseResourceManager = require('oroui/js/tools/multi-use-resource-manager');
    const cyrb53 = require('oroui/js/tools/cyrb53').default;
    const mediator = require('oroui/js/mediator');
    const error = require('oroui/js/error');
    require('bootstrap');
    const config = require('module-config').default(module.id);

    const defaults = Object.assign({
        container: '',
        temporaryContainer: '[data-role="messenger-temporary-container"]',
        delay: false,
        dismissible: true,
        showIcon: true,
        animation: true,
        template: template,
        insertMethod: 'appendTo',
        style: 'default',
        storageKey: 'oroAfterReloadMessages'
    }, config);
    let queue = [];
    const groupedMessages = {};
    const notFlashTypes = ['error', 'danger', 'warning', 'alert'];
    const noFlashTags = ['a'];

    const resolveContainer = function(options) {
        if ($(options.container).is(defaults.container) && $(defaults.temporaryContainer).length) {
            return defaults.temporaryContainer;
        }
        return options.container;
    };

    /**
     * Same arguments as for Oro.NotificationMessage
     */
    function showMessage(type, message, options = {}) {
        const opt = Object.assign({}, defaults, options);
        const container = resolveContainer(opt);

        messenger.clear(opt.namespace, opt);

        const $el = $(opt.template({
            type,
            message,
            style: opt.style,
            dismissible: opt.dismissible,
            showIcon: opt.showIcon,
            animation: opt.animation
        }))[opt.insertMethod](container);

        $el.data('_message', {type, message, options});

        if (opt.onClose) {
            $el.on('close.bs.alert', opt.onClose);
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
    const messenger = {
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
         * @param {(string|jQuery)=} options.container selector of jQuery with container element
         * @param {(number|boolean)?} options.delay time in ms to auto close message
         *      or false - means to not close automatically
         * @param {Function=} options.template template function
         * @param {boolean=} options.flash flag to turn on default delay close call, it's 5s
         * @param {boolean=} options.dismissible alert can be closed, by default true
         * @param {boolean=} options.showIcon alert has icon related to its type, by default true
         * @param {boolean=} options.animation alert should be shown and hidden with animation, by default true
         * @param {boolean?} options.afterReload whether the message should be shown after a page is reloaded
         * @param {string=} options.namespace slot for a massage,
         *     other existing message with the same namespace will be removed
         * @param {Function?} options.onClose handler that is executed once user close the alert
         *
         * @return {Object} collection of methods - actions over message element,
         *      at the moment there's only one method 'close', allows to close the message
         */
        notificationMessage: function(type, message, options = {}) {
            const container = options.container || defaults.container;
            const afterReload = options.afterReload || false;
            let afterReloadQueue = [];
            let actions = {close: $.noop};

            if (!options.namespace) {
                options.namespace = cyrb53(message + this.type).toString();
            }

            if (afterReload && window.localStorage) {
                afterReloadQueue = JSON.parse(localStorage.getItem(defaults.storageKey) || '[]');
                afterReloadQueue.push([type, message, options]);
                localStorage.setItem(defaults.storageKey, JSON.stringify(afterReloadQueue));
            } else if (container && $(container).length) {
                actions = showMessage(type, message, options);
            } else {
                // if container is not ready then save message for later
                queue.push([type, message, options]);
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
         * @param {(string|jQuery)=} options.container selector of jQuery with container element
         * @param {(number|boolean)?} options.delay time in ms to auto close message
         *      or false - means to not close automatically
         * @param {Function=} options.template template function
         * @param {boolean=} options.flash flag to turn on default delay close call, it's 5s
         * @param {boolean=} options.dismissible alert can be closed, by default true
         * @param {boolean=} options.showIcon alert has icon related to its type, by default true
         * @param {boolean=} options.animation alert should be shown and hidden with animation, by default true
         * @param {boolean?} options.afterReload whether the message should be shown after a page is reloaded
         * @param {string=} options.namespace slot for a massage,
         *     other existing message with the same namespace will be removed
         * @param {Function?} options.onClose handler that is executed once user close the alert
         *
         * @return {Object} collection of methods - actions over message element,
         *      at the moment there's only one method 'close', allows to close the message
         */
        notificationFlashMessage: function(type, message, options = {}) {
            if (!('flash' in options)) {
                options.flash = notFlashTypes.indexOf(type) === -1 && !this._containsNoFlashTags(message);
            }

            return this.notificationMessage(type, message, options);
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
                queue = queue.concat(JSON.parse(localStorage.getItem(defaults.storageKey) || '[]'));
                localStorage.removeItem(defaults.storageKey);
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
            const messenger = this;
            if (!groupedMessages[message]) {
                groupedMessages[message] = new MultiUseResourceManager({
                    listen: {
                        constructResource: function() {
                            this.alert = messenger.notificationMessage(type, message, {flash: false});
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
         * Clears all messages within namespace or by selector
         *
         * @param {string?} namespace
         * @param {Object=} options
         * @param {string} options.clearSelector selector for messages to remove
         */
        clear: function(namespace, options = {}) {
            const opt = _.extend({}, defaults, options);
            const selectors = [];
            if (namespace) {
                selectors.push(`[data-messenger-namespace="${namespace}"]`);
            }
            if (options.clearSelector) {
                selectors.push(options.clearSelector);
            }
            $(opt.container).add(opt.temporaryContainer).find(selectors.join(',')).remove();
        },

        removeTemporaryContainer: function() {
            $(this).children().each((i, el) => {
                const {type, message, options} = $(el).data('_message');
                // re-publish messages with original options into default messages container
                _.delay(() => messenger.notificationMessage(type, message, options));
            });
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

    return messenger;
});
