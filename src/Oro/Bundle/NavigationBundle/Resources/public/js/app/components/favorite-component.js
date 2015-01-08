/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    './base/bookmark-component',
    '../views/bookmark-button-view',
    'oroui/js/app/views/base/collection-view',
    '../views/bookmark-item-view'
], function (_, BaseBookmarkComponent, ButtonView, CollectionView, ItemView) {
    'use strict';

    var FavoriteComponent;

    FavoriteComponent = BaseBookmarkComponent.extend({
        _createSubViews: function () {
            this._createButtonView();
            this._createTabView();
        },

        /**
         * Create view for pin button
         *
         * @protected
         */
        _createButtonView: function () {
            var options, collection;

            options = this._options.buttonOptions || {};
            collection = this.collection;

            _.extend(options, {
                autoRender: true,
                collection: collection
            });

            this.button = new ButtonView(options);
        },

        /**
         * Create view for favorite tabs in dot-menu
         *
         * @protected
         */
        _createTabView: function () {
            var options, collection, TabItemView;

            options = this._options.tabOptions || {};
            collection = this.collection;
            TabItemView = ItemView.extend({
                template: this._options.tabItemTemplate
            });

            _.extend(options, {
                autoRender: true,
                collection: collection,
                itemView: TabItemView
            });

            this.tabs = new CollectionView(options);
        },

        actualizeAttributes: function (model) {
            model.set('type', 'favorite');
            model.set('position', this.collection.length);

            var url = model.get('url');
            var urlPart = url.split('?');
            if (model.get('url') !== urlPart[0]) {
                model.set('url', urlPart[0]);
            }
        }
    });

    return FavoriteComponent;
});
