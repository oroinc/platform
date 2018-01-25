define([
    'jquery',
    'underscore',
    'oroui/js/mediator'
], function($, _, mediator) {
    'use strict';

    var layoutSubtreeManager;

    /**
     * @export oroui/js/layout-subtree-manager
     * @name   oro.layoutSubtreeManager
     */
    layoutSubtreeManager = {
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
            var blockId = view.options.blockId;

            this.viewsCollection[blockId] = view;

            if (view.options.reloadEvents instanceof Array) {
                view.options.reloadEvents.map((function(eventItem) {
                    if (!this.reloadEvents[eventItem]) {
                        this.reloadEvents[eventItem] = [];
                        mediator.on(eventItem, this._reloadLayouts.bind(this, eventItem), this);
                    }
                    this.reloadEvents[eventItem].push(blockId);
                }).bind(this));
            }
        },

        /**
         * Remove layout subtree instance from registry.
         *
         * @param {Object} view LayoutSubtreeView instance
         */
        removeView: function(view) {
            var blockId = view.options.blockId;

            delete this.viewsCollection[blockId];

            Object.keys(this.reloadEvents).map((function(eventName) {
                var eventBlockIds = this.reloadEvents[eventName];
                var index = eventBlockIds.indexOf(blockId);
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
                var view = this.viewsCollection[blockId];
                if (!view) {
                    return;
                }

                var viewArguments = methodArguments || [];
                if (typeof viewArguments === 'function') {
                    viewArguments = viewArguments(blockId);
                }
                view[methodName].apply(view, viewArguments);
            }).bind(this));
        },

        /**
         * Send ajax request to server with query(ies) for update elements depends from event.
         *
         * @param {string} event name of fired event
         * @param {Object} options
         */
        _reloadLayouts: function(event, options) {
            var self = this;
            var eventBlockIds = this.reloadEvents[event] || [];
            if (!(eventBlockIds instanceof Array) || !eventBlockIds.length) {
                return;
            }

            options = options || {
                layoutSubtreeUrl: null,
                layoutSubtreeCallback: null
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
                        return [content[blockId] || ''];
                    });
                    self._callViewMethod(eventBlockIds, 'afterContentLoading');
                    if (options.layoutSubtreeCallback) {
                        options.layoutSubtreeCallback();
                    }
                })
                .fail(function(jqxhr) {
                    self._callViewMethod(eventBlockIds, 'contentLoadingFail');
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
