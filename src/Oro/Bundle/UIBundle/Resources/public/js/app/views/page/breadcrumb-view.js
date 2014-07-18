/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    './../base/view'
], function (_, BaseView) {
    'use strict';

    var BreadcrumbView;

    BreadcrumbView = BaseView.extend({
        listen: {
            'mainMenuUpdated mediator': 'onMenuUpdate'
        },

        template: _.template('<ul class="breadcrumb">' +
            '<% for (var i =0; i < breadcrumbs.length; i++) { %>' +
                '<li>' +
                    '<%= breadcrumbs[i] %>' +
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
        onMenuUpdate: function (menuView) {
            this.data = {
                breadcrumbs: menuView.getActiveItems()
            };
            this.render();
            this.data = null;
        },

        /**
         * Prevents rendering a view without page data
         *
         * @override
         */
        render: function () {
            var data;
            data = this.getTemplateData();
            if (!data) {
                return;
            }

            BreadcrumbView.__super__.render.call(this);
        },

        /**
         * Gets cached page data
         *
         * @returns {Object}
         * @override
         */
        getTemplateData: function () {
            return this.data;
        }
    });

    return BreadcrumbView;
});
