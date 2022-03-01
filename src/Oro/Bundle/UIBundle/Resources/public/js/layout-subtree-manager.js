define([
    'jquery',
    'underscore',
    'oroui/js/mediator'
], function($, _, mediator) {
    'use strict';

    /**
     * @export oroui/js/layout-subtree-manager
     * @name   oro.layoutSubtreeManager
     */
    const layoutSubtreeManager = {
        url: window.location.href,

        method: 'get',

        viewsCollection: {},

        reloadEvents: {},

        /**
         * Add layout subtree instance to registry.
         *
         * @param {Object} view LayoutSubtreeView instance
         */
        addView: function(view) {
            const blockId = view.options.blockId;

            this.viewsCollection[blockId] = view;

            if (view.options.reloadEvents instanceof Array) {
                view.options.reloadEvents.map((function(eventItem) {
                    if (!this.reloadEvents[eventItem]) {
                        this.reloadEvents[eventItem] = [];
                        mediator.on(eventItem, this._reloadLayouts.bind(this, eventItem), this);
                    }

                    if (!this.reloadEvents[eventItem].includes(blockId)) {
                        this.reloadEvents[eventItem].push(blockId);
                    }
                }).bind(this));
            }
        },

        /**
         * Remove layout subtree instance from registry.
         *
         * @param {Object} view LayoutSubtreeView instance
         */
        removeView: function(view) {
            const blockId = view.options.blockId;

            delete this.viewsCollection[blockId];

            Object.keys(this.reloadEvents).map((function(eventName) {
                const eventBlockIds = this.reloadEvents[eventName];
                const index = eventBlockIds.indexOf(blockId);
                if (index > -1) {
                    eventBlockIds.splice(index, 1);
                }
                if (!eventBlockIds.length) {
                    delete this.reloadEvents[eventName];
                    mediator.off(eventName, null, this);
                }
            }).bind(this));
        },

        /**
         * Call view methods from collection.
         *
         * @param {Array} blockIds
         * @param {String} methodName
         * @param {Function|Array} [methodArguments]
         */
        _callViewMethod: function(blockIds, methodName, methodArguments) {
            blockIds.map((function(blockId) {
                const view = this.viewsCollection[blockId];
                if (!view) {
                    return;
                }

                let viewArguments = methodArguments || [];
                if (typeof viewArguments === 'function') {
                    viewArguments = viewArguments(blockId);
                }
                view[methodName](...viewArguments);
            }).bind(this));
        },

        /**
         * Send ajax request to server with query(ies) for update elements depends from event.
         *
         * @param {string} event name of fired event
         * @param {Object} options
         */
        _reloadLayouts: function(event, options) {
            const self = this;
            const eventBlockIds = this.reloadEvents[event] || [];
            if (!(eventBlockIds instanceof Array) || !eventBlockIds.length) {
                return;
            }

            options = options || {
                layoutSubtreeUrl: null,
                layoutSubtreeCallback: null,
                layoutSubtreeFailCallback: null
            };

            this._callViewMethod(eventBlockIds, 'beforeContentLoading');
            $.ajax({
                url: options.layoutSubtreeUrl || this.url,
                type: this.method,
                data: {
                    layout_block_ids: eventBlockIds
                }
            })
                .done(function(content) {
                    self._callViewMethod(eventBlockIds, 'setContent', function(blockId) {
                        return [content[blockId]];
                    });
                    self._callViewMethod(eventBlockIds, 'afterContentLoading');
                    if (options.layoutSubtreeCallback) {
                        options.layoutSubtreeCallback();
                    }
                })
                .fail(function(jqxhr) {
                    self._callViewMethod(eventBlockIds, 'contentLoadingFail');
                    if (options.layoutSubtreeFailCallback) {
                        options.layoutSubtreeFailCallback(jqxhr);
                    }
                });
        },

        /**
         * Send ajax request to server and update block for layout by ID.
         *
         * @param {String} blockId
         * @param {object} data
         * @param {Function} callback
         */
        get: function(blockId, data, callback) {
            data = data || {};
            data.layout_block_ids = [blockId];
            $.ajax({
                url: document.location.pathname,
                type: this.method,
                data: data || {}
            }).done(function(content) {
                if (_.isFunction(callback)) {
                    callback(content[blockId] || '');
                }
            }).fail(function(jqxhr) {
                if (_.isFunction(callback)) {
                    callback('');
                }
            });
        }
    };

    return layoutSubtreeManager;
});
