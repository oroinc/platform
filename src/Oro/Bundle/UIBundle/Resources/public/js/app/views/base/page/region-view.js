/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'chaplin',
    './../view'
], function (_, Chaplin, BaseView) {
    'use strict';

    var PageRegionView;

    PageRegionView = BaseView.extend({
        listen: {
            'page:update mediator': 'onPageUpdate'
        },

        data: null,
        pageItems: [],

        /**
         * Handles page load event
         *  - stores from page data corresponded page items
         *  - renders view
         *  - dispose cached data
         *
         * @param {Object} data
         * @param {Object} actionArgs arguments of controller's action point
         */
        onPageUpdate: function (data, actionArgs) {
            this.data = _.pick(data, this.pageItems);
            this.actionArgs = actionArgs;
            this.render();
            this.data = null;
            this.actionArgs = null;
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

            PageRegionView.__super__.render.call(this);
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

    return PageRegionView;
});
