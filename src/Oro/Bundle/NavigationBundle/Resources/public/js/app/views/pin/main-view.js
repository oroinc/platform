/*jshint browser:true*/
/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'oroui/js/mediator',
    '../base/main-view',
    '../base/button-view',
    './collection-view',
    './item-view'
], function (_, mediator, MainView, ButtonView, CollectionView, ItemView) {
    'use strict';

    var PinView;

    PinView = MainView.extend({
        maxItems: 7,

        listen: {
            'add collection': 'onAdd',
            'remove collection': 'onRemove'
        },

        createSubViews: function (options) {
            var _this, collection, button,
                barView, BarItemView, barOptions,
                tabView, TabItemView, tabOptions;

            _this = this;
            collection = this.collection;

            // button view
            button = new ButtonView({
                autoRender: true,
                el: 'pinButton',
                collection: collection
            });
            this.subview('button', button);

            // bar view
            BarItemView = ItemView.extend({
                template: options.barItemTemplate
            });
            barOptions = _.extend(options.barOptions, {
                autoRender: true,
                el: 'pinBar',
                collection: collection,
                itemView: BarItemView,
                filterer: function (item, index) {
                    return index < _this.maxItems;
                }
            });
            barView = new CollectionView(barOptions);
            this.subview('bar', barView);

            // tab view
            TabItemView = ItemView.extend({
                template: options.tabItemTemplate
            });
            tabOptions = _.extend(options.tabOptions, {
                autoRender: true,
                el: 'pinTab',
                collection: collection,
                itemView: TabItemView,
                filterer: function (item, index) {
                    return index >= _this.maxItems;
                }
            });
            tabView = new CollectionView(tabOptions);
            this.subview('tab', tabView);
        },

        actualizeAttributes: function (model) {
            model.set('type', 'pinbar');
            model.set('position', 0);
        },

        onAdd: function (model) {
            mediator.execute({name: 'pageCache:add', silent: true});
            this.reorder();
            if (model.get('url') !== mediator.execute('currentUrl')) {
                // if URL was changed on server, applies this changes for current page
                mediator.execute('changeUrl', model.get('url'), {replace: true});
            }
        },

        onRemove: function (model) {
            var url;
            url = model.get('url');
            mediator.execute({name: 'pageCache:remove', silent: true}, url);
            this.reorder();
            if (mediator.execute('compareUrl', model.get('url'))) {
                // remove 'restore' param from URL, if pin was removed for current page
                mediator.execute('changeUrlParam', 'restore', null);
            }
        },

        /**
         * Change position property of model based on current order
         */
        reorder: function () {
            this.collection.each(function (module, position) {
                module.set({position: position});
            });
        }
    });

    return PinView;
});
