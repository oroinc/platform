define(function(require) {
    'use strict';

    var BarCollectionView;
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var _ = require('underscore');

    BarCollectionView = BaseCollectionView.extend({
        animationDuration: 0,

        /**
         * @inheritDoc
         */
        constructor: function BarCollectionView() {
            BarCollectionView.__super__.constructor.apply(this, arguments);
        },

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
            return _.isRTL()
                ? this.el.offsetLeft <= itemView.el.offsetLeft
                : this.el.offsetLeft + this.el.offsetWidth >= itemView.el.offsetLeft + itemView.el.offsetWidth;
        }
    });

    return BarCollectionView;
});
