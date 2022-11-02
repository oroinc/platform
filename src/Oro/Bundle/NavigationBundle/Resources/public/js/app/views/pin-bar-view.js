define(function(require) {
    'use strict';

    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    const _ = require('underscore');

    const BarCollectionView = BaseCollectionView.extend({
        animationDuration: 0,

        /**
         * @inheritdoc
         */
        constructor: function BarCollectionView(options) {
            BarCollectionView.__super__.constructor.call(this, options);
        },

        /**
         * Goes across subviews and check if item-view related to corresponded model is visible
         *
         * @param {Chaplin.Model} model
         * @returns {boolean}
         */
        isVisibleItem: function(model) {
            const itemView = this.subview('itemView:' + model.cid);
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
