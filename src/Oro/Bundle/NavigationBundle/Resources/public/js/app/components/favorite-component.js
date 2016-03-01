define([
    'underscore',
    './base/bookmark-component',
    '../views/bookmark-button-view',
    'oroui/js/app/views/base/collection-view',
    '../views/bookmark-item-view'
], function(_, BaseBookmarkComponent, ButtonView, CollectionView, ItemView) {
    'use strict';

    var FavoriteComponent;

    FavoriteComponent = BaseBookmarkComponent.extend({
        typeName: 'favorite',

        _createSubViews: function() {
            this._createButtonView();
            this._createTabView();
        },

        /**
         * Create view for pin button
         *
         * @protected
         */
        _createButtonView: function() {
            var options = this._options.buttonOptions || {};
            var collection = this.collection;

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
        _createTabView: function() {
            var options = this._options.tabOptions || {};
            var collection = this.collection;
            var TabItemView = ItemView.extend({
                template: this._options.tabItemTemplate
            });

            _.extend(options, {
                autoRender: true,
                collection: collection,
                itemView: TabItemView
            });

            this.tabs = new CollectionView(options);
        },

        actualizeAttributes: function(model) {
            model.set('type', this.typeName);
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
