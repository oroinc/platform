/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'oroui/js/mediator',
    './base/bookmark-component',
    '../views/bookmark-button-view',
    'oroui/js/app/views/base/collection-view',
    '../views/bookmark-item-view'
], function (_, mediator, BaseBookmarkComponent, ButtonView, CollectionView, ItemView) {
    'use strict';

    var FavoriteComponent;

    FavoriteComponent = BaseBookmarkComponent.extend({
        _createSubViews: function () {
            this._createButtonView()
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

        toAdd: function (model) {
            var collection;
            collection = this.collection;
            this.actualizeAttributes(model);
            var self = this;
            model.save(null, {
                success: function () {
                    var item;
                    item = collection.find(function (item) {
                        return item.get('url') === model.get('url');
                    });
                    if (item) {
                        model.destroy();
                    } else {
                        var url = model.get('url');
                        self.removeUrlParams(model, url);
                        collection.unshift(model);
                    }
                }
            });
        },

        onPageStateChange: function () {
            var model, url;
            model = this.collection.getCurrentModel();
            if (model) {
                url = mediator.execute('currentUrl');
                this.removeUrlParams(model, url);
                model.save();
            }
        },

        removeUrlParams: function (model, url) {
            var urlPart = url.split('?');
            model.set('url', urlPart[0]);
        },

        actualizeAttributes: function (model) {
            model.set('type', 'favorite');
            model.set('position', this.collection.length);
        }
    });

    return FavoriteComponent;
});
