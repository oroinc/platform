/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'oroui/js/app/views/base/collection-view'
], function (_, BaseCollectionView) {
    'use strict';

    var BarCollectionView;

    BarCollectionView = BaseCollectionView.extend({
        /**
         * Goes across subviews and check if item-view related to corresponded model is visible
         *
         * @param {Chaplin.Model} model
         * @returns {boolean}
         */
        isVisibleItem: function (model) {
            var itemView = _.find(this.subviews, function (itemView) {
                return itemView.model === model;
            });
            return this.isVisibleView(itemView);
        },

        /**
         * Check if corresponded item-view is visible
         *
         * @param {Chaplin.View} itemView
         * @returns {boolean}
         */
        isVisibleView: function (itemView) {
            return itemView && itemView.el.offsetTop === 0;
        },

        /**
         * Looks for last visible item-view
         *
         * @returns {Chaplin.View|undefined}
         */
        getLastVisibleView: function () {
            var itemView;
            itemView = _.find(this.subviews.slice().reverse(), this.isVisibleView, this);
            return itemView;
        }
    });

    return BarCollectionView;
});
