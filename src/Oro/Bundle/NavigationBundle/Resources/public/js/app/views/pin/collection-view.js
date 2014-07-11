/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'chaplin',
    'oroui/js/app/views/base/collection-view'
], function (_, Chaplin, BaseCollectionView) {
    'use strict';

    var CollectionView, utils;

    utils = Chaplin.utils;
    CollectionView = BaseCollectionView.extend({

        listen: {
            'visibilityChange': 'updateVisibilityList',
            'add collection': 'recheck',
            'remove collection': 'recheck'
        },

        recheck: function () {
            var visibilityChanged;

            this.collection.each(function (model, index) {
                var view, included, visibleItemsIndex;
                view = _.find(this.subviews, function (view) {
                    return view.model === model;
                });
                included = this.filterer(model, index);
                this.filterCallback(view, included);

                visibleItemsIndex = utils.indexOf(this.visibleItems, model);
                if (included && visibleItemsIndex === -1) {
                    this.visibleItems.push(model);
                    visibilityChanged = true;
                } else if (!included && visibleItemsIndex !== -1) {
                    this.visibleItems.splice(visibleItemsIndex, 1);
                    visibilityChanged = true;
                }
            }, this);

            if (visibilityChanged) {
                this.trigger('visibilityChange', this.visibleItems);
            }
        },

        filterCallback: function (view, included) {
            if (included) {
                view.$el.css('display', '');
            } else {
                view.$el.css('display', 'none');
            }
        },

        renderAllItems: function () {
            CollectionView.__super__.renderAllItems.apply(this, arguments);
            this.updateVisibilityList();
        },

        updateVisibilityList: function () {
            this.$list[this.visibleItems.length > 0 ? 'show' : 'hide']();
        }
    });

    return CollectionView;
});
