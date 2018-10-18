define(function(require) {
    'use strict';

    var PageBreadcrumbView;
    var _ = require('underscore');
    var PageRegionView = require('./../base/page-region-view');

    PageBreadcrumbView = PageRegionView.extend({
        listen: {
            'mainMenuUpdated mediator': 'onMenuUpdate'
        },
        pageItems: ['breadcrumb'],

        template: function(data) {
            return data.breadcrumb;
        },

        breadcrumbsTemplate: _.template('<ul class="breadcrumb">' +
        '<% for (var i = 0; i < breadcrumbs.length; i++) { %>' +
        '<li class="breadcrumb-item<%= (i + 1 === breadcrumbs.length) ? " active": "" %>"><%- breadcrumbs[i] %></li>' +
        '<% } %>' +
        '</ul>'),

        data: null,

        /**
         * @inheritDoc
         */
        constructor: function PageBreadcrumbView() {
            PageBreadcrumbView.__super__.constructor.apply(this, arguments);
        },

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
                    breadcrumb: this.breadcrumbsTemplate({breadcrumbs: breadcrumbs})
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

    return PageBreadcrumbView;
});
