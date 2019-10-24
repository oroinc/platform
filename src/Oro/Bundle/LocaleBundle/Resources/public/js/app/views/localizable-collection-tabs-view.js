define(function(require) {
    'use strict';

    var LocalizableCollectionTabsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var tinyMCE = require('tinymce/tinymce');

    LocalizableCollectionTabsView = BaseView.extend({
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
                itemFallback: '.fallback-item-fallback'
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
         * @inheritDoc
         */
        constructor: function LocalizableCollectionTabsView() {
            LocalizableCollectionTabsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            LocalizableCollectionTabsView.__super__.initialize.call(this, options);
        },

        /**
         * @param {jQuery.Event} e
         */
        onChangeLocalizationTab: function(e) {
            var $target = $(e.target || window.event.target);
            var $dataTarget = $($target.attr('data-target'));

            this.switchUseFallback($dataTarget);
        },

        onHideLocalizationTab: function(e) {
            var $target = $(e.target || window.event.target);
            var $dataTarget = $($target.attr('data-target'));

            this.enableDisableValue(this.getValueEl($dataTarget), false);
        },

        /**
         * @inheritDoc
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
            var self = this;

            this.$el.find(this.options.selectors.itemValue).find('.mce-tinymce').each(function() {
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
                .change(_.bind(this.switchFallbackTypeEvent, this));
        },

        /**
         * Create item code to element mapping
         */
        mapItemsByCode: function() {
            var self = this;

            this.itemsByCode = {};

            this.$el.find(this.options.selectors.item).each(function() {
                var $item = $(this);
                var itemCode = self.getItemCode($item);

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
            var self = this;

            this.itemToChildren = {};

            this.$el.find(this.options.selectors.item).each(function() {
                var $item = $(this);
                var parentItemCode = self.getParentItemCode($item);

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
            var $item = this.getItemEl(e.currentTarget);

            this.mapItemToChildren();

            var parentItemCode = this.getParentItemCode($item);
            if (parentItemCode) {
                var $fromValue = this._getFromValue(parentItemCode);
                var $toValue = this.getValueEl($item);
                this.cloneValue($fromValue, $toValue);
            }

            this.switchUseFallback($item);

            $(e.currentTarget).data('prevValue', e.currentTarget.value);
        },

        _getFromValue: function(parentItemCode) {
            var $item = this.itemsByCode[parentItemCode];
            var $value = this.getValueEl($item);

            if (_.isEmpty($value.val())) {
                var code = this.getParentCode($item);
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
            var $useFallback = this.getFallbackEl($item);
            if ($useFallback.length === 0) {
                this.enableDisableValue(this.getValueEl($item), true);
                return;
            }

            this.enableDisableValue(this.getValueEl($item), $useFallback.val() === '');
        },

        /**
         * Enable/disable value
         *
         * @param {jQuery} $element
         * @param {Boolean} enable
         */
        enableDisableValue: function($element, enable) {
            var $$elementContainer = $element.closest(this.options.selectors.itemValue);

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
            var isChanged = false;
            $fromValue.each(function(i) {
                var toValue = $toValue.get(i);
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
            var $item = $(el);
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
         * Get parent item code
         *
         * @param {jQuery} $item
         *
         * @returns {undefined|String}
         */
        getParentItemCode: function($item) {
            var $select = this.getFallbackEl($item);
            if ($select.length === 0 || $select.attr('disabled')) {
                return;
            }

            var parentItemCode = $select.attr('data-parent-localization');

            if ($select.val() === '') {
                return parentItemCode && $select.data('prevValue') !== 'system'
                    ? parentItemCode
                    : $select.data('prevValue');
            }
        },

        getParentCode: function($item) {
            var $select = this.getFallbackEl($item);
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
            var $select = this.getFallbackEl($item);
            var itemCode;

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
