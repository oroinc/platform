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

        initialize: function (options) {
            _.extend(this, _.pick(options, ['position']));
            CollectionView.__super__.initialize.apply(this, arguments);
            // handle resize event once per frame (1000 ms / 25 frames)
            $(window).on('resize.' + this.cid, _.debounce(_.bind(this.onPageResize, this), 40));
        },

        dispose: function () {
            $(window).off('.' + this.cid);
            CollectionView.__super__.dispose.call(this);
        },

        render: function () {
            CollectionView.__super__.render.call(this);
            this.positionUpdate();
            return this;
        },

        onPageResize: function () {
            this.positionUpdate();
            this.recheck();
        },

        /**
         * Updates position of root element
         */
        positionUpdate: function () {
            var pos = _.result(this, 'position');
            if (pos) {
                this.$el.css('left', pos.left);
            }
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
            this.$el[this.visibleItems.length > 0 ? 'show' : 'hide']();
        }
    });

    return CollectionView;
});
