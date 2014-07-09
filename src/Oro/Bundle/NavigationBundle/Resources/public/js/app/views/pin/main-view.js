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
            'remove collection': 'onRemove',
            'toMaximize collection': 'toMaximize',

            'page:beforeChange mediator': 'onPageChange'
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

        /**
         * Handle item minimize/maximize state change
         *
         * @param model
         */
        toMaximize: function (model) {
            var url;
            url = model.get('url');
            if (!mediator.execute('compareUrl', url)) {
                mediator.execute('redirectTo', {url: url}, {cache: true});
            }
        },

        onAdd: function () {
            mediator.execute({name: 'pageCache:add', silent: true});
            this.reorder();
        },

        onRemove: function (model) {
            var url;
            url = model.get('url');
            mediator.execute({name: 'pageCache:remove', silent: true}, url);
            this.reorder();
        },

        /**
         * Change position property of model based on current order
         */
        reorder: function () {
            this.collection.each(function (module, position) {
                module.set({position: position});
            });
        },

        /**
         * Handles page change
         *  - if there's related model in collection, updates route query
         * @param oldRoute
         * @param newRoute
         * @param options
         */
        onPageChange: function (oldRoute, newRoute, options) {
            var model, _ref;
            if (!newRoute || newRoute.query === '') {
                return;
            }
            model = this.collection.find(function (model) {
                return mediator.execute('compareUrl', model.get('url'), newRoute.path);
            });
            if (model) {
                _ref = model.get('url').split('?');
                newRoute.query = _ref[1] || '';
            }
        }
    });

    return PinView;
});
