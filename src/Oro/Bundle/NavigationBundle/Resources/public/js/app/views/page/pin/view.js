/*jshint browser:true*/
/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'oroui/js/mediator',
    'oroui/js/app/views/base/view',
    '../base/button-view',
    './collection-view',
    './item-view',
    'oroui/js/tools',
    'oroui/js/error'
], function (_, mediator, BaseView, ButtonView, CollectionView, ItemView, tools, error) {
    'use strict';

    var PinView;

    PinView = BaseView.extend({
        maxItems: 7,

        /**
         * Keeps separately extended options,
         * to prevent disposing the view each time by Composer
         */
        _options: {},

        listen: {
            'add collection': 'onAdd',
            'remove collection': 'onRemove',

            'toAdd collection': 'toAdd',
            'toRemove collection': 'toRemove',
            'toMaximize collection': 'toMaximize',

            'pagestate:change meditor': 'onPageStateChange'
        },

        initialize: function (options) {
            var data, extraOptions, $dataEl;

            $dataEl = this.$(options.dataSource);
            data = $dataEl.data('data');
            extraOptions = $dataEl.data('options');
            $dataEl.remove();
            this._options = _.defaults({}, options || {}, extraOptions);

            PinView.__super__.initialize.call(this, options);

            this.collection.reset(data);
        },

        render: function () {
            this.createSubViews(this._options);
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

        getCurrentModel: function () {
            return this.collection.find(function (model) {
                return mediator.execute('compareUrl', model.get('url'));
            });
        },

        toRemove: function (model) {
            var _this = this;
            model.destroy({
                wait: true,
                success: function () {

                },
                error: function (model, xhr) {
                    if (xhr.status === 404 && !tools.debug) {
                        // Suppress error if it's 404 response and not debug mode
                        //@TODO remove item view
                    } else {
                        error.handle({}, xhr, {enforce: true});
                    }
                }
            });
        },

        toAdd: function (model) {
            var collection;
            collection = this.collection;
            model.set('position', 0);
            model.save(null, {
                success: function () {
                    var item;
                    item = collection.find(function (item) {
                        return item.get('url') === model.get('url');
                    });
                    if (item) {
                        model.destroy();
                    } else {
                        collection.unshift(model);
                    }
                }
            });
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

        onPageStateChange: function () {
            var model, url;
            model = this.getCurrentModel();
            url = mediator.execute('currentUrl');
            model.set({url: url});
            model.save();
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
