define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    'oroui/js/error'
], function($, _, mediator, Error) {
    'use strict';

    var layoutSubtreeManager;
    /**
     * @export oroui/js/layout-subtree-manager
     * @name   oro.layoutSubtreeManager
     */
    layoutSubtreeManager = {
        url: window.location.href,
        method: 'get',
        layoutSubtreeCollection: [],
        reloadEvents: {},

        /**
         * Add layout subtree instance to registry.
         *
         * @param {object} layoutSubtreeOptions
         */
        addLayoutSubtreeInstance: function(layoutSubtreeView) {
            this.layoutSubtreeCollection[layoutSubtreeView.options.rootId] = layoutSubtreeView;
            if (layoutSubtreeView.options.reloadEvents instanceof Array) {
                layoutSubtreeView.options.reloadEvents.map((function(eventItem, index) {
                    if (!this.reloadEvents[eventItem]) {
                        this.reloadEvents[eventItem] = [];
                        mediator.on(eventItem, this._reloadLayouts.bind(this, eventItem));
                    }
                    this.reloadEvents[eventItem].push(layoutSubtreeView.options.rootId);
                }).bind(this));
            }
        },

        /**
         * Remove layout subtree instance from registry.
         *
         * @param {string} rootId unique layoutSubtree identifier
         */
        removeLayoutSubtreeInstance: function(layoutSubtreeRootId) {
            delete this.layoutSubtreeCollection[layoutSubtreeRootId];
            Object.keys(this.reloadEvents).map((function(eventItem) {
                var index = eventItem.indexOf(layoutSubtreeRootId);
                if (index > -1) {
                    eventItem.splice(index, 1);
                }
                if (!eventItem.length) {
                    delete this.reloadEvents[eventItem];
                    mediator.off(event, this._reloadLayouts, this);
                }
            }).bind(this));
        },

        /**
         * Call layoutSubtreeView methods from collection.
         *
         * @param {string} event name of fired event
         */
        _callLayoutSubtreeViewMethod: function(idsIterator, methodName, methodParams) {
            idsIterator.map((function(rootId) {
                if (typeof this.layoutSubtreeCollection[rootId] !== 'undefined') {
                    var methodArguments = typeof methodParams === 'function' ? methodParams(rootId) : [];
                    this.layoutSubtreeCollection[rootId][methodName].apply(this.layoutSubtreeCollection[rootId], methodArguments);
                }
            }).bind(this));
        },

        // TODO Remove after finish backend part
        _executeMockAjaxQuery: function(queryData, eventListeners) {
            var dfd = $.Deferred();
            var ajaxes = eventListeners.map(function(rootId){
                var customAjaxDeff = $.Deferred();
                $.ajax({
                    url: queryData.url,
                    method: queryData.method,
                    data: {
                        layout_root_id: rootId
                    }
                }).done(function(content){
                    customAjaxDeff.resolve({
                        rootId: rootId,
                        content: content
                    })
                }).fail(function(jqxhr){
                    customAjaxDeff.fail({
                        rootId: rootId,
                        jqxhr: jqxhr
                    })
                });
                return customAjaxDeff;
            });
            $.when.apply($, ajaxes).then(
                function() {
                    var result = [];
                    Array.prototype.slice.call(arguments).map(function(item){
                        result[item.rootId] = item.content;
                    });
                    dfd.resolve(result);
                },
                function() {
                    var result = [];
                    Array.prototype.slice.call(arguments).map(function(item){
                        result[item.rootId] = item.jqxhr;
                    });
                    dfd.fail(result);
                }
            )
            return dfd;
        },

        /**
         * Send ajax request to server with query(ies) for update elements depends from event.
         *
         * @param {string} event name of fired event
         */
        _reloadLayouts: function(event) {
            var self = this;
            var eventListeners = this.reloadEvents[event] || [];
            if (!(eventListeners instanceof Array) || !eventListeners.length) {
                return;
            }
            var queryData = {
                url: this.url,
                data: {
                    layout_root_id: eventListeners.join(',')
                },
                type: this.method
            };
            this._callLayoutSubtreeViewMethod(eventListeners, '_showLoading');
            // TODO Uncomment after finish backend part
            //$.ajax(queryData)
            // TODO Remove after finish backend part
            this._executeMockAjaxQuery(queryData, eventListeners)
                .done(function(content) {
                    self._callLayoutSubtreeViewMethod(Object.keys(content), '_onContentLoad', function(rootId) {
                        return [content[rootId]];
                    });
                })
                .fail(function(jqxhr) {
                    self._callLayoutSubtreeViewMethod(eventListeners, '_hideLoading');
                    Error.handle({}, jqxhr, {enforce: true});
                });
        }
    };

    return layoutSubtreeManager;
});
