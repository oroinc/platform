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
        reloadEvents: {},

        /**
         * Add layout subtree instance to registry.
         *
         * @param {object} layoutSubtreeOptions
         */
        addLayoutSubtreeInstance: function(layoutSubtreeOptions) {
            if (layoutSubtreeOptions.reloadEvents instanceof Array) {
                layoutSubtreeOptions.reloadEvents.map((function(item, index) {
                    if (!this.reloadEvents[item]) {
                        this.reloadEvents[item] = [];
                        mediator.on(item, this._reloadLayouts.bind(this, item));
                    }
                    this.reloadEvents[item].push(layoutSubtreeOptions.rootId);
                }).bind(this));
            }
        },

        /**
         * Remove layout subtree instance from registry.
         *
         * @param {string} rootId unique layoutSubtree identifier
         */
        removeLayoutSubtreeInstance: function(layoutSubtreeOptions) {
            Object.keys(this.reloadEvents).map((function(item) {
                var index = item.indexOf(layoutSubtreeOptions.rootId);
                if (index > -1) {
                    item.splice(index, 1);
                }
                if (!item.length) {
                    delete this.reloadEvents[item];
                    mediator.off(event, this._reloadLayouts, this);
                }
            }).bind(this));
        },

        /**
         * Send ajax request to server with query(ies) for update elements depends from event.
         *
         * @param {string} event name of fired event
         */
        _reloadLayouts: function(event) {
            if (!this.reloadEvents[event] || !(this.reloadEvents[event] instanceof Array) || !this.reloadEvents[event].length) {
                return;
            }
            var queryData = {
                url: this.url,
                data: {
                    layout_root_id: this.reloadEvents[event]
                },
                type: this.method
            };
            mediator.trigger('layout_subtree_reload_start');
            $.ajax(queryData)
                .done(function(content) {
                    mediator.trigger('layout_subtree_reload_done', content);
                })
                .fail(function(jqxhr) {
                    mediator.trigger('layout_subtree_reload_fail');
                    Error.handle({}, jqxhr, {enforce: true});
                });
        }
    };

    return layoutSubtreeManager;
});