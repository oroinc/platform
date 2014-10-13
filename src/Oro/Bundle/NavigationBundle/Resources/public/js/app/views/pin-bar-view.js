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
            var itemView = this.subview("itemView:" + model.cid);
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
            var itemView, i, models;
            models = this.collection.models;

            // iterate from the end of models list until first visible view
            for (i = models.length - 1; i >= 0; i -= 1) {
                itemView = this.subview("itemView:" + models[i].cid);
                if (this.isVisibleView(itemView)) {
                    break;
                }
                itemView = null;
            }

            return itemView;
        }
    });

    return BarCollectionView;
});
