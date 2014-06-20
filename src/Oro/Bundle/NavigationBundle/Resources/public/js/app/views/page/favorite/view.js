/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'oroui/js/mediator',
    'oroui/js/app/views/base/view',
    '../base/button-view',
    'oroui/js/app/views/base/collection-view',
    './item-view',
    'oroui/js/tools',
    'oroui/js/error'
], function (_, mediator, BaseView, ButtonView, CollectionView, ItemView, tools, error) {
    'use strict';

    var FavoriteView;

    FavoriteView = BaseView.extend({
        /**
         * Keeps separately extended options,
         * to prevent disposing the view each time by Composer
         */
        _options: {},

        listen: {
            'toAdd collection': 'toAdd',
            'toRemove collection': 'toRemove',

            'pagestate:change meditor': 'onPageStateChange'
        },

        initialize: function (options) {
            var data, extraOptions, $dataEl;

            $dataEl = this.$(options.dataSource);
            data = $dataEl.data('data');
            extraOptions = $dataEl.data('options');
            $dataEl.remove();
            this._options = _.defaults({}, options || {}, extraOptions);

            FavoriteView.__super__.initialize.call(this, options);

            this.collection.reset(data);
        },

        render: function () {
            this.createSubViews(this._options);
        },

        createSubViews: function (options) {
            var collection, button,
                tabView, TabItemView, tabOptions;

            collection = this.collection;

            // button view
            button = new ButtonView({
                autoRender: true,
                el: 'pinButton',
                collection: collection
            });
            this.subview('button', button);

            // tab view
            TabItemView = ItemView.extend({
                template: options.tabItemTemplate
            });
            tabOptions = _.extend(options.tabOptions, {
                autoRender: true,
                el: 'pinTab',
                collection: collection,
                itemView: TabItemView
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
            model.destroy({
                wait: true,
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
            model.set('type', 'favorite');
            model.set('position', this.collection.length);
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

        onPageStateChange: function () {
            var model, url;
            model = this.getCurrentModel();
            url = mediator.execute('currentUrl');
            model.set({url: url});
            model.save();
        }
    });

    return FavoriteView;
});
