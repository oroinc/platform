define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    const BaseView = require('oroui/js/app/views/base/view');
    const itemTemplate = require('tpl-loader!oroui/templates/dropdown-menu-collection--item.html');

    /**
     * DropdownMenuCollectionView renders dropdown element based on passed items collection
     *
     * Supports Chaplin's CollectionView blocks "list", "loading" and "fallback"
     *
     * Basic usage:
     * ```javascript
     * // ...
     * const itemsCollection = new Collection([
     *     {value: '3', value_text: 'Three'},
     *     {value: '5', value_text: 'Five'}
     * ]);
     *
     * const dropdownMenu = new DropdownMenuCollectionView({
     *     collection: itemsCollection,
     *     loadingText: __('Searching...'),
     *     fallbackText: __('No items found'),
     *     keysMap: {
     *         id: 'value',
     *         text: 'value_text',
     *     }
     * });
     *
     * this.$el.append(dropdownMenu.$el);
     *
     * // ...
     * ```
     *
     * @class
     * @augment BaseCollectionView
     * @exports DropdownMenuView
     */
    const DropdownMenuCollectionView = BaseCollectionView.extend({
        tagName: 'div',
        className: 'dropdown-menu dropdown-menu-collection',
        animationDuration: 0,
        listSelector: '[data-name="list"]',
        loadingSelector: '[data-name="loading"]',
        fallbackSelector: '[data-name="fallback"]',

        /**
         * @type {string}
         */
        loadingText: __('Loading...'),

        /**
         * @type {string}
         */
        fallbackText: __('No matches found'),

        events: {
            'click li': 'onItemClick'
        },

        listen: {
            'sync collection': 'onCollectionSync',
            'syncStateChange collection': 'onCollectionSyncStateChange'
        },

        template: _.template([
            '<div class="dropdown-item" data-name="fallback"><%- fallbackText %></div>',
            '<div class="dropdown-item" data-name="loading"><%- loadingText %></div>',
            '<ul class="list-unstyled" data-name="list" role="menu"></ul>'
        ].join('')),

        /**
         * @inheritdoc
         */
        constructor: function DropdownMenuCollectionView(options) {
            DropdownMenuCollectionView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            _.extend(this, _.pick(options, ['loadingText', 'fallbackText', 'keysMap']));
            if (options.keysMap) {
                const keysMap = options.keysMap;
                const ItemView = this.itemView = this.itemView.extend({// eslint-disable-line oro/named-constructor
                    getTemplateData: function() {
                        const data = ItemView.__super__.getTemplateData.call(this);
                        data.id = keysMap.id && data[keysMap.id];
                        data.text = keysMap.text && data[keysMap.text];
                        return data;
                    }
                });
            }
            DropdownMenuCollectionView.__super__.initialize.call(this, options);
        },

        getTemplateData: function() {
            const data = DropdownMenuCollectionView.__super__.getTemplateData.call(this);
            _.extend(data, _.pick(this, ['loadingText', 'fallbackText']));
            return data;
        },

        itemView: BaseView.extend({// eslint-disable-line oro/named-constructor
            tagName: 'li',
            template: itemTemplate
        }),

        onItemClick: function(e) {
            e.preventDefault();
            e.stopPropagation();
            const subview = _.find(this.subviews, function(subview) {
                return subview.el === e.currentTarget;
            });
            if (subview) {
                this.trigger('selected', subview.model.toJSON());
            }
        },

        onCollectionSync: function() {
            this.$el.trigger('content:changed');
        },

        onCollectionSyncStateChange: function() {
            _.defer(() => {
                this.$el.trigger('content:changed');
            });
        }
    });

    return DropdownMenuCollectionView;
});
