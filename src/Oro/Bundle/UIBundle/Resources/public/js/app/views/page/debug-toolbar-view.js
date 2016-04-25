define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    'routing',
    './../base/page-region-view'
], function($, _, mediator, routing, PageRegionView) {
    'use strict';

    var DebugToolbarView;

    DebugToolbarView = PageRegionView.extend({
        listen: {
            'page:error mediator': 'onPageUpdate'
        },

        events: {
            'click .hide-button': 'sendUpdates',
            'click .sf-minitoolbar': 'sendUpdates'
        },

        /**
         * Handles page load event
         *  - loads debug data
         *  - updates a debugger bar
         *
         * @param {Object} data
         * @param {Object} actionArgs arguments of controller's action point
         * @param {Object} xhr
         * @override
         */
        onPageUpdate: function(data, actionArgs, xhr) {
            if (!actionArgs.route.previous) {
                this.sendUpdates();
                // nothing to do, the page just loaded
                return;
            } else if (!xhr) {
                this.$el.empty();
                mediator.trigger('layout:adjustHeight');
                return;
            }
            this.updateToolbar(xhr);
        },

        /**
         * Makes request for toolbar's content and call render method
         *
         * @param {Object} xhr
         */
        updateToolbar: function(xhr) {
            var token = xhr.getResponseHeader('x-debug-token');
            var url = routing.generate('_wdt', {token: token});
            $.get(url, _.bind(this.render, this, token, url));
        },

        /**
         * Updates a debugger bar
         *
         * @param {string} token
         * @param {string} url
         * @param {string} data html content
         */
        render: function(token, url, data) {
            var id;

            if (!data) {
                return;
            }

            id = 'sfwdt' + token;
            this.$el
                .appendTo('body')
                .attr('id', id)
                .attr('data-sfurl', url);
            this.$el.html(data);

            this.sendUpdates();
        },

        /**
         * Notifies application about updates
         */
        sendUpdates: function() {
            mediator.trigger('debugToolbar:afterUpdateView');
            mediator.trigger('layout:adjustHeight');
        }
    });

    return DebugToolbarView;
});
