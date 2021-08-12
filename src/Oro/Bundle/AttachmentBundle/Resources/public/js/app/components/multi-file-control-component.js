define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const $ = require('jquery');
    const _ = require('underscore');

    const MultiFileControlComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            lineItemSortOrderSelector: 'tr td.sort-order input',
            addItemButton: '.add-list-item',
            maxNumber: 0
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {Object}
         */
        $lineItemSortOrder: null,

        /**
         * @property {Object}
         */
        $addItemButton: null,

        /**
         * @inheritdoc
         */
        constructor: function MultiFileControlComponent(options) {
            MultiFileControlComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$el = this.options._sourceElement;

            this.$addItemButton = this.$el.find(this.options.addItemButton);
            this.$lineItemSortOrder = this.$el.find(this.options.lineItemSortOrderSelector);

            if (this.$lineItemSortOrder.length === 1 && !this.$lineItemSortOrder.val()) {
                this.$lineItemSortOrder.val(1);
            }

            this.$el.on('content:changed', this._onChangeContent.bind(this));
            this.$el.on('content:remove', this._onRemoveContent.bind(this));

            this._hideAddItemButton();
        },

        _onChangeContent: function() {
            let max = 0;
            this.$el.find(this.options.lineItemSortOrderSelector).each(function() {
                max = Math.max(max, $(this).val() || 0);
            });

            this.$el.find(this.options.lineItemSortOrderSelector).last().val(max + 1);

            this._hideAddItemButton();
        },

        _onRemoveContent: function() {
            this._showAddItemButton();
        },

        _hideAddItemButton: function() {
            if (this.options.maxNumber > 0) {
                const count = this.$el.find(this.options.lineItemSortOrderSelector).length;
                if (this.options.maxNumber <= count) {
                    this.$addItemButton.hide();
                }
            }
        },

        _showAddItemButton: function() {
            if (this.options.maxNumber > 0) {
                const count = this.$el.find(this.options.lineItemSortOrderSelector).length - 1;
                if (this.options.maxNumber > count) {
                    this.$addItemButton.show();
                }
            }
        }
    });

    return MultiFileControlComponent;
});
