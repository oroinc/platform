define(function(require) {
    'use strict';

    var PinComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var PageStateView = require('oronavigation/js/app/views/page-state-view');
    var ButtonView = require('oronavigation/js/app/views/bookmark-button-view');
    var PinBarView = require('oronavigation/js/app/views/pin-bar-view');
    var DropdownView = require('oronavigation/js/app/views/pin-dropdown-view');
    var ItemView = require('oronavigation/js/app/views/pin-item-view');
    var BaseBookmarkComponent = require('oronavigation/js/app/components/base/bookmark-component');

    PinComponent = BaseBookmarkComponent.extend({
        typeName: 'pinbar',

        listen: {
            'add collection': 'onAdd',
            'remove collection': 'onRemove',
            'pagestate:change mediator': 'onPageStateChange',
            'page:afterChange mediator': 'onPageAfterChange'
        },

        /**
         * @inheritDoc
         */
        constructor: function PinComponent() {
            PinComponent.__super__.constructor.apply(this, arguments);
        },

        _createSubViews: function() {
            this._createButtonView();
            this._createBarView();
            this._createDropdownView();
            this._createPageStateView();
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
         * Create view for pin bar
         *
         * @protected
         */
        _createBarView: function() {
            var options = this._options.barOptions || {};
            var collection = this.collection;

            _.extend(options, {
                autoRender: true,
                collection: collection,
                itemView: ItemView
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

            _.extend(options, {
                autoRender: true,
                collection: collection,
                itemView: ItemView,
                filterer: function(item) {
                    return !pinBar.isVisibleItem(item);
                },
                position: function() {
                    if (pinBar.el) {
                        var left = Math.ceil(pinBar.$el.position().left);

                        return {
                            left: _.isRTL() ? left : left + Math.ceil(pinBar.$el.width())
                        };
                    } else {
                        return null;
                    }
                }
            });

            this.dropdown = new DropdownView(options);
        },

        _createPageStateView: function() {
            var options = this._options.pageStateOptions || {};

            _.extend(options, {
                collection: this.collection
            });

            this.pageState = new PageStateView(options);

            mediator.setHandler('isPageStateChanged', this.pageState.isStateChanged.bind(this.pageState));
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
