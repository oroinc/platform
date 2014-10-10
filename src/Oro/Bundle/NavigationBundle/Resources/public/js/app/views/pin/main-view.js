/*jshint browser:true*/
/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'oroui/js/mediator',
    '../base/main-view',
    '../base/button-view',
    './pinbar-view',
    './dropdown-view',
    './item-view'
], function (_, mediator, MainView, ButtonView, PinBarView, DropdownView, ItemView) {
    'use strict';

    var PinView;

    PinView = MainView.extend({
        maxItems: 7,

        listen: {
            'add collection': 'onAdd',
            'remove collection': 'onRemove'
        },

        createSubViews: function (options) {
            var collection, button,
                barView, BarItemView, barOptions,
                dropdownView, DropdownItemView, dropdownOptions;

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
                itemView: BarItemView
            });
            barView = new PinBarView(barOptions);
            this.subview('bar', barView);

            // tab view
            DropdownItemView = ItemView.extend({
                template: options.tabItemTemplate
            });
            dropdownOptions = _.extend(options.tabOptions, {
                autoRender: true,
                el: 'pinTab',
                collection: collection,
                itemView: DropdownItemView,
                filterer: function (item) {
                    return !barView.isVisibleItem(item);
                },
                position: function () {
                    var itemView, pos = {};
                    itemView = barView.getLastVisibleView();
                    if (itemView) {
                        pos.left = itemView.el.getBoundingClientRect().right;
                    }
                    return pos;
                }
            });
            dropdownView = new DropdownView(dropdownOptions);
            this.subview('dropdown', dropdownView);
        },

        actualizeAttributes: function (model) {
            model.set('type', 'pinbar');
            model.set('position', 0);
        },

        onAdd: function (model) {
            mediator.execute({name: 'pageCache:add', silent: true});
            if (model.get('url') !== mediator.execute('currentUrl')) {
                // if URL was changed on server, applies this changes for current page
                mediator.execute('changeUrl', model.get('url'), {replace: true});
            }
        },

        onRemove: function (model) {
            var url;
            url = model.get('url');
            mediator.execute({name: 'pageCache:remove', silent: true}, url);
            if (mediator.execute('compareUrl', model.get('url'))) {
                // remove 'restore' param from URL, if pin was removed for current page
                mediator.execute('changeUrlParam', 'restore', null);
            }
        }
    });

    return PinView;
});
