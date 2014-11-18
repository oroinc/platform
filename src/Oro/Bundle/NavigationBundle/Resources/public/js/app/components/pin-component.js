/*jshint browser:true*/
/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'oroui/js/mediator',
    './base/bookmark-component',
    '../views/bookmark-button-view',
    '../views/pin-bar-view',
    '../views/pin-dropdown-view',
    '../views/pin-item-view'
], function (_, mediator, BaseBookmarkComponent, ButtonView, PinBarView, DropdownView, ItemView) {
    'use strict';

    var PinComponent;

    PinComponent = BaseBookmarkComponent.extend({
        listen: {
            'add collection': 'onAdd',
            'remove collection': 'onRemove',
            'pagestate:change mediator': 'onPageStateChange'
        },

        _createSubViews: function () {
            this._createButtonView();
            this._createBarView();
            this._createDropdownView();
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
         * Create view for pin bar
         *
         * @protected
         */
        _createBarView: function () {
            var options, collection, BarItemView;

            options = this._options.barOptions || {};
            collection = this.collection;
            BarItemView = ItemView.extend({
                template: this._options.barItemTemplate
            });

            _.extend(options, {
                autoRender: true,
                collection: collection,
                itemView: BarItemView
            });

            this.pinBar = new PinBarView(options);
        },

        /**
         * Create view for pins in dropdown
         *
         * @protected
         */
        _createDropdownView: function () {
            var options, collection, pinBar, DropdownItemView;

            options = this._options.dropdownOptions || {};
            collection = this.collection;
            pinBar = this.pinBar;
            DropdownItemView = ItemView.extend({
                template: this._options.dropdownItemTemplate
            });

            _.extend(options, {
                autoRender: true,
                collection: collection,
                itemView: DropdownItemView,
                filterer: function (item) {
                    return !pinBar.isVisibleItem(item);
                },
                position: function () {
                    var itemView, pos = {};
                    itemView = pinBar.getLastVisibleView();
                    if (itemView) {
                        pos.left = itemView.el.getBoundingClientRect().right;
                    }
                    return pos;
                }
            });

            this.dropdown = new DropdownView(options);
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
        },

        onPageStateChange: function () {
            var model, url;
            model = this.collection.getCurrentModel();
            if (model) {
                url = mediator.execute('currentUrl');
                if (model.get('url') !== url) {
                    model.set('url', url);
                    model.save();
                }
            }
        }
    });

    return PinComponent;
});
