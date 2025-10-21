import _ from 'underscore';
import BaseBookmarkComponent from 'oronavigation/js/app/components/base/bookmark-component';
import CollectionView from 'oroui/js/app/views/base/collection-view';
import ButtonView from 'oronavigation/js/app/views/bookmark-button-view';
import ItemView from 'oronavigation/js/app/views/bookmark-item-view';
import favoriteItemTemplate from 'tpl-loader!oronavigation/templates/favorite-item.html';

const FavoriteComponent = BaseBookmarkComponent.extend({
    typeName: 'favorite',

    /**
     * @inheritdoc
     */
    constructor: function FavoriteComponent(options) {
        FavoriteComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
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
        const options = this._options.buttonOptions || {};
        const collection = this.collection;

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
        const options = this._options.tabOptions || {};
        const collection = this.collection;
        const TabItemView = ItemView.extend({// eslint-disable-line oro/named-constructor
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

        const url = model.get('url');
        const urlPart = url.split('?');
        if (model.get('url') !== urlPart[0]) {
            model.set('url', urlPart[0]);
        }
    }
});

export default FavoriteComponent;
