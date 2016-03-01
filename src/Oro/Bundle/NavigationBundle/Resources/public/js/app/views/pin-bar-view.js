define([
    'underscore',
    'oroui/js/app/views/base/collection-view'
], function(_, BaseCollectionView) {
    'use strict';

    var BarCollectionView;

    BarCollectionView = BaseCollectionView.extend({
        /**
         * Goes across subviews and check if item-view related to corresponded model is visible
         *
         * @param {Chaplin.Model} model
         * @returns {boolean}
         */
        isVisibleItem: function(model) {
            var itemView = this.subview('itemView:' + model.cid);
            return this.isVisibleView(itemView);
        },

        /**
         * Check if corresponded item-view is visible
         *
         * @param {Chaplin.View} itemView
         * @returns {boolean}
         */
        isVisibleView: function(itemView) {
            if (!itemView) {
                return false;
            }
            return this.el.offsetLeft + this.el.offsetWidth >= itemView.el.offsetLeft + itemView.el.offsetWidth;
        }
    });

    return BarCollectionView;
});
