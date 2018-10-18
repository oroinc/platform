define(function(require) {
    'use strict';

    var FavoriteComponent;
    var _ = require('underscore');
    var BaseBookmarkComponent = require('oronavigation/js/app/components/base/bookmark-component');
    var CollectionView = require('oroui/js/app/views/base/collection-view');
    var ButtonView = require('oronavigation/js/app/views/bookmark-button-view');
    var ItemView = require('oronavigation/js/app/views/bookmark-item-view');
    var favoriteItemTemplate = require('tpl!oronavigation/templates/favorite-item.html');

    FavoriteComponent = BaseBookmarkComponent.extend({
        typeName: 'favorite',

        /**
         * @inheritDoc
         */
        constructor: function FavoriteComponent(options) {
            FavoriteComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
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
                el: this._options._sourceElement,
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
            var TabItemView = ItemView.extend({// eslint-disable-line oro/named-constructor
                template: favoriteItemTemplate
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
