define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');
    const $ = require('jquery');
    const tinyMCE = require('tinymce/tinymce');

    const LocalizableCollectionTabsView = BaseView.extend({
        autoRender: true,

        /**
         * @property {Object}
         */
        itemsByCode: {},

        /**
         * @property {Object}
         */
        itemToChildren: {},

        /**
         * @property {Object}
         */
        options: {
            selectors: {
                item: '.fallback-item',
                firstChildItem: '.fallback-item:first',
                childItem: '.fallback-item:not(:first)',
                itemValue: '.fallback-item-value',
                itemFallback: '.fallback-item-fallback',
                itemUseFallback: '.fallback-item-use-fallback'
            }
        },

        /**
         * @property {Object}
         */
        events: {
            'shown.bs.tab [data-role="change-localization"]': 'onChangeLocalizationTab',
            'hide.bs.tab [data-role="change-localization"]': 'onHideLocalizationTab'
        },

        /**
         * @inheritdoc
         */
        constructor: function LocalizableCollectionTabsView(options) {
            LocalizableCollectionTabsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            LocalizableCollectionTabsView.__super__.initialize.call(this, options);
        },

        /**
         * @param {jQuery.Event} e
         */
        onChangeLocalizationTab: function(e) {
            const $target = $(e.target || window.event.target);
            const $dataTarget = $($target.attr('data-target'));

            this.switchUseFallback($dataTarget);
        },

        onHideLocalizationTab: function(e) {
            const $target = $(e.target || window.event.target);
            const $dataTarget = $($target.attr('data-target'));

            this.enableDisableValue(this.getValueEl($dataTarget), false);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            this.$(this.options.selectors.childItem).attr('data-layout', 'separate');

            this._deferredRender();
            this.initLayout().done(function() {
                this.handleLayoutInit();
                this._resolveDeferredRender();
            }.bind(this));

            return this;
        },

        renderSubviews: function() {
            this.$(this.options.selectors.childItem).removeAttr('data-layout');

            this.initLayout().done(function() {
                this.bindEvents();

                this.enableDisableValue(
                    this.getValueEl(this.$(this.options.selectors.firstChildItem)), true
                );
            }.bind(this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.mapItemsByCode();
            this.mapItemToChildren();
            this.renderSubviews();
        },

        /**
         * Bind events to controls
         */
        bindEvents: function() {
            const self = this;

            this.$el.find(this.options.selectors.itemValue).find('.tox-tinymce').each(function() {
                tinyMCE.get(self.getValueEl(self.getItemEl(this)).attr('id'))
                    .on('change', function() {
                        $(this.targetElm).change();
                    })
                    .on('keyup', function() {
                        $(this.targetElm).change();
                    });
            });

            this.getFallbackEl(this.$el)
                .on('focus', function(e) {
                    $(e.currentTarget).data('prevValue', e.currentTarget.value);
                })
                .change(this.switchFallbackTypeEvent.bind(this));
        },

        /**
         * Create item code to element mapping
         */
        mapItemsByCode: function() {
            const self = this;

            this.itemsByCode = {};

            this.$el.find(this.options.selectors.item).each(function() {
                const $item = $(this);
                const itemCode = self.getItemCode($item);

                if (!itemCode) {
                    return;
                }

                self.itemsByCode[itemCode] = $item;
            });
        },

        /**
         * Create item to children mapping
         */
        mapItemToChildren: function() {
            const self = this;

            this.itemToChildren = {};

            this.$el.find(this.options.selectors.item).each(function() {
                const $item = $(this);
                const parentItemCode = self.getParentItemCode($item);

                if (!parentItemCode) {
                    return;
                }

                if (self.itemToChildren[parentItemCode] === undefined) {
                    self.itemToChildren[parentItemCode] = [];
                }
                self.itemToChildren[parentItemCode].push($item);
            });
        },

        /**
         * Trigger on fallback change
         *
         * @param {Event} e
         */
        switchFallbackTypeEvent: function(e) {
            const $item = this.getItemEl(e.currentTarget);

            this.mapItemToChildren();

            const parentItemCode = this.getParentItemCode($item);
            if (parentItemCode) {
                const $fromValue = this._getFromValue(parentItemCode);
                const $toValue = this.getValueEl($item);
                this.cloneValue($fromValue, $toValue);
            }

            this.switchUseFallback($item);

            $(e.currentTarget).data('prevValue', e.currentTarget.value);
        },

        _getFromValue: function(parentItemCode) {
            const $item = this.itemsByCode[parentItemCode];
            let $value = this.getValueEl($item);

            if (_.isEmpty($value.val())) {
                const code = this.getParentCode($item);
                if (code) {
                    $value = this._getFromValue(code);
                } else {
                    return $value;
                }
            }

            return $value;
        },

        /**
         * Enable/disable controls depending on the "use fallback"
         *
         * @param {jQuery} $item
         */
        switchUseFallback: function($item) {
            const $fallback = this.getFallbackEl($item);
            if ($fallback.length === 0) {
                this.enableDisableValue(this.getValueEl($item), true);
                return;
            }

            const isFallbackNotUsed = $fallback.val() === '';

            this.enableDisableValue(this.getValueEl($item), isFallbackNotUsed);
            this.getUseFallbackEl($item).prop('disabled', isFallbackNotUsed).val(!isFallbackNotUsed);
        },

        /**
         * Enable/disable value
         *
         * @param {jQuery} $element
         * @param {Boolean} enable
         */
        enableDisableValue: function($element, enable) {
            const $$elementContainer = $element.closest(this.options.selectors.itemValue);

            if (enable) {
                $$elementContainer.show();
                $element.trigger('wysiwyg:enable');
            } else {
                $$elementContainer.hide();
                $element.trigger('wysiwyg:disable');
            }
        },

        /**
         * Clone value to another value
         *
         * @param {jQuery} $fromValue
         * @param {jQuery} $toValue
         */
        cloneValue: function($fromValue, $toValue) {
            let isChanged = false;
            $fromValue.each(function(i) {
                const toValue = $toValue.get(i);
                if ($(toValue).val() !== $(this).val()) {
                    isChanged = true;
                    $(toValue).val($(this).val());
                }
            });
            if (isChanged) {
                $toValue.filter(':first').change();
            }
        },

        /**
         * Get item element by children
         *
         * @param {*|jQuery|HTMLElement} el
         *
         * @returns {jQuery}
         */
        getItemEl: function(el) {
            let $item = $(el);
            if (!$item.is(this.options.selectors.item)) {
                $item = $item.closest(this.options.selectors.item);
            }
            return $item;
        },

        /**
         * Get value element
         *
         * @param {jQuery} $el
         *
         * @returns {jQuery}
         */
        getValueEl: function($el) {
            return $el.find(this.options.selectors.itemValue).find('input, textarea, select');
        },

        /**
         * Get fallback element
         *
         * @param {jQuery} $el
         *
         * @returns {jQuery}
         */
        getFallbackEl: function($el) {
            return $el.find(this.options.selectors.itemFallback).find('select');
        },

        /**
         * Get use_fallback element
         *
         * @param {jQuery} $el
         *
         * @returns {jQuery}
         */
        getUseFallbackEl: function($el) {
            return $el.find(this.options.selectors.itemUseFallback).find('input');
        },

        /**
         * Get parent item code
         *
         * @param {jQuery} $item
         *
         * @returns {undefined|String}
         */
        getParentItemCode: function($item) {
            const $select = this.getFallbackEl($item);
            if ($select.length === 0 || $select.attr('disabled')) {
                return;
            }

            const parentItemCode = $select.attr('data-parent-localization');

            if ($select.val() === '') {
                return parentItemCode && $select.data('prevValue') !== 'system'
                    ? parentItemCode
                    : $select.data('prevValue');
            }
        },

        getParentCode: function($item) {
            const $select = this.getFallbackEl($item);
            if ($select.length === 0 || $select.attr('disabled')) {
                return;
            }

            return $select.attr('data-parent-localization');
        },

        /**
         * Get item code
         *
         * @param {jQuery} $item
         *
         * @returns {String}
         */
        getItemCode: function($item) {
            const $select = this.getFallbackEl($item);
            let itemCode;

            if ($select.length === 0) {
                itemCode = 'system';
            } else {
                itemCode = $select.attr('data-localization');
            }

            return itemCode;
        }
    });

    return LocalizableCollectionTabsView;
});
