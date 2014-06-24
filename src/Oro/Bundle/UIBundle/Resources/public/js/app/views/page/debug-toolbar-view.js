/*jslint browser:true, nomen:true*/
/*global define, window*/
define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    './../base/page-region-view'
], function ($, _, mediator, PageRegionView) {
    'use strict';

    var DebugToolbarView;

    DebugToolbarView = PageRegionView.extend({
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
        onPageUpdate: function (data, actionArgs, xhr) {
            var baseUrl, url, token;

            if (!xhr) {
                this.$el.empty();
                mediator.trigger('layout:adjustHeight');
                return;
            }

            baseUrl = mediator.execute('retrieveOption', 'root');
            token = xhr.getResponseHeader('x-debug-token');
            url = baseUrl + '_wdt/' + token;
            $.get(url, _.bind(this.render, this, token, url));
        },

        render: function (token, url, data) {
            var id;

            if (!data) {
                return;
            }

            id = 'sfwdt' + token;
            this.$el
                .attr('id', id)
                .attr('data-sfurl', url);
            this.$el.html(data);

            mediator.trigger('layout:adjustHeight');
        }
    });

    return DebugToolbarView;
});
