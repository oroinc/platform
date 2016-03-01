define([
    'underscore',
    'oroui/js/mediator',
    './base/bookmark-component',
    '../views/bookmark-button-view',
    '../views/pin-bar-view',
    '../views/pin-dropdown-view',
    '../views/pin-item-view'
], function(_, mediator, BaseBookmarkComponent, ButtonView, PinBarView, DropdownView, ItemView) {
    'use strict';

    var PinComponent;

    PinComponent = BaseBookmarkComponent.extend({
        typeName: 'pinbar',

        listen: {
            'add collection': 'onAdd',
            'remove collection': 'onRemove',
            'pagestate:change mediator': 'onPageStateChange',
            'page:afterChange mediator': 'onPageAfterChange'
        },

        _createSubViews: function() {
            this._createButtonView();
            this._createBarView();
            this._createDropdownView();
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
         * Create view for pin bar
         *
         * @protected
         */
        _createBarView: function() {
            var options = this._options.barOptions || {};
            var collection = this.collection;
            var BarItemView = ItemView.extend({
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
        _createDropdownView: function() {
            var options = this._options.dropdownOptions || {};
            var collection = this.collection;
            var pinBar = this.pinBar;
            var DropdownItemView = ItemView.extend({
                template: this._options.dropdownItemTemplate
            });

            _.extend(options, {
                autoRender: true,
                collection: collection,
                itemView: DropdownItemView,
                filterer: function(item) {
                    return !pinBar.isVisibleItem(item);
                },
                position: function() {
                    if (pinBar.el) {
                        return {
                            left: Math.ceil(pinBar.el.offsetLeft) + Math.ceil(pinBar.el.offsetWidth)
                        };
                    } else {
                        return null;
                    }
                }
            });

            this.dropdown = new DropdownView(options);
        },

        actualizeAttributes: function(model) {
            model.set('type', this.typeName);
            model.set('position', 0);
        },

        onAdd: function(model) {
            mediator.execute({name: 'pageCache:add', silent: true});
            if (model.get('url') !== mediator.execute('currentUrl')) {
                // if URL was changed on server, applies this changes for current page
                mediator.execute('changeUrl', model.get('url'), {replace: true});
            }
        },

        onRemove: function(model) {
            var url;
            url = model.get('url');
            mediator.execute({name: 'pageCache:remove', silent: true}, url);
            if (mediator.execute('compareUrl', model.get('url'))) {
                // remove 'restore' param from URL, if pin was removed for current page
                mediator.execute('changeUrlParam', 'restore', null);
            }
        },

        onPageStateChange: function() {
            var url;
            var model = this.collection.getCurrentModel();
            if (model) {
                url = mediator.execute('currentUrl');
                if (model.get('url') !== url) {
                    model.set('url', url);
                    model.save();
                }
            }
        },

        onPageAfterChange: function() {
            var model = this.collection.getCurrentModel();
            if (model) {
                model.set(this.button.getItemAttrs());
                // if title changed (template and/or it's parameters) -- update it
                if (model.hasChanged('title')) {
                    model.save();
                }
            }
        }
    });

    return PinComponent;
});
