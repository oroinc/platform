/*jslint browser:true, nomen:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view',
    'oroui/js/mediator'
], function ($, _, BaseView, mediator) {
    'use strict';

    var ItemView;

    ItemView = BaseView.extend({
        tagName:  'li',

        events: {
            'click .btn-close': 'toRemove',
            'click .close': 'toRemove'
        },

        listen: {
            'change:url model': 'render',
            'page:afterChange mediator': 'onPageUpdated'
        },

        /**
         * Change active item after hash navigation request is completed
         */
        onPageUpdated: function () {
            this.setActiveItem();
        },

        toRemove: function () {
            this.model.collection.trigger('toRemove', this.model);
        },

        /**
         * Compares current url with model's url
         *
         * @returns {boolean}
         */
        checkCurrentUrl: function () {
            var url;
            url = this.model.get('url');
            return mediator.execute('compareUrl', url);
        },

        setActiveItem: function () {
            this.$el.toggleClass('active', this.checkCurrentUrl());
        }
    });

    return ItemView;
});
