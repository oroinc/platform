define([
    'underscore',
    './../base/page-region-view'
], function(_, PageRegionView) {
    'use strict';

    var BreadcrumbView;

    BreadcrumbView = PageRegionView.extend({
        listen: {
            'mainMenuUpdated mediator': 'onMenuUpdate'
        },
        pageItems: ['breadcrumb'],

        template: function(data) {
            return data.breadcrumb;
        },

        breadcrumbsTemplate: _.template('<ul class="breadcrumb">' +
            '<% for (var i =0; i < breadcrumbs.length; i++) { %>' +
                '<li>' +
                    '<%- breadcrumbs[i] %>' +
                    '<%if (i+1 != breadcrumbs.length) { %><span class="divider">/&nbsp;</span><% } %>' +
                '</li>' +
            '<% } %>' +
            '</ul>'),

        data: null,

        /**
         * Handles menu update event
         *  - prepares data for breadcrumbs rendering
         *  - renders view
         *  - dispose cached data
         *
         * @param {Object} menuView
         */
        onMenuUpdate: function(menuView) {
            var breadcrumbs = menuView.getActiveItems();
            if (breadcrumbs.length) {
                this.data = {
                    'breadcrumb': this.breadcrumbsTemplate({'breadcrumbs': breadcrumbs})
                };
                this.render();
                this.data = null;
            }
        },

        /**
         * Gets cached page data
         *
         * @returns {Object}
         * @override
         */
        getTemplateData: function() {
            return this.data;
        }
    });

    return BreadcrumbView;
});
