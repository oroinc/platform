define(function(require) {
    'use strict';

    var FallbackView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orolocale/js/app/views/fallback-view
     * @extends oroui.app.views.base.View
     * @class orolocale.app.views.FallbackView
     */
    FallbackView = BaseView.extend({
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
            expanded: false,
            hideDefaultLabel: true,
            fallbackWidth: 180,
            selectors: {
                status: '.fallback-status',
                item: '.fallback-item',
                defaultItem: '.fallback-item:first',
                childItem: '.fallback-item:not(:first)',
                itemLabel: '.fallback-item-label',
                itemValue: '.fallback-item-value',
                itemUseFallback: '.fallback-item-use-fallback',
                itemFallback: '.fallback-item-fallback'
            },
            icons: {
                new: {
                    html: '<i class="icon-folder-close-alt"></i>',
                    event: 'expandChildItems'
                },
                edited: {
                    html: '<i class="icon-folder-close"></i>',
                    event: 'expandChildItems'
                },
                save: {
                    html: '<i class="icon-folder-open"></i>',
                    event: 'collapseChildItems'
                }
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            var self = this;
            this.initLayout().done(function() {
                self.handleLayoutInit();
            });
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            var self = this;

            this.mapItemsByCode();

            this.getUseFallbackEl(this.$el).each(function() {
                self.switchUseFallback(self.getItemEl(this));
            });

            this.mapItemToChildren();

            this.getValueEl(this.$el).each(function() {
                self.cloneValueToChildren(self.getItemEl(this));
            });

            this.fixFallbackWidth();
            this.setStatusIcon();

            this.bindEvents();
        },

        /**
         * Bind events to controls
         */
        bindEvents: function() {
            var self = this;

            this.getValueEl(this.$el)
                .change(_.bind(this.cloneValueToChildrenEvent, this))
                .keyup(_.bind(this.cloneValueToChildrenEvent, this));

            this.$el.find(this.options.selectors.itemValue).find('.mce-tinymce').each(function() {
                self.getValueEl(self.getItemEl(this)).tinymce()
                    .on('change', function() {
                        $(this.targetElm).change();
                    })
                    .on('keyup', function() {
                        $(this.targetElm).change();
                    });
            });

            this.getUseFallbackEl(this.$el)
                .change(_.bind(this.switchUseFallbackEvent, this));

            this.getFallbackEl(this.$el)
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
         * Trigger on value change
         *
         * @param {Event} e
         */
        cloneValueToChildrenEvent: function(e) {
            this.cloneValueToChildren(this.getItemEl(e.currentTarget));
        },

        /**
         * Trigger on "use fallback" change
         *
         * @param {Event} e
         */
        switchUseFallbackEvent: function(e) {
            this.switchUseFallback(this.getItemEl(e.currentTarget));
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
                var $fromValue = this.getValueEl(this.itemsByCode[parentItemCode]);
                var $toValue = this.getValueEl($item);
                this.cloneValue($fromValue, $toValue);
            } else {
                this.cloneValueToChildrenEvent(e);
            }
        },

        /**
         * Show child items
         */
        expandChildItems: function() {
            this.options.expanded = true;
            this.setStatusIcon();
        },

        /**
         * Hide child items
         */
        collapseChildItems: function() {
            this.options.expanded = false;
            this.setStatusIcon();
        },

        /**
         * Clone item value to children
         *
         * @param {jQuery} $item
         */
        cloneValueToChildren: function($item) {
            var $fromValue = this.getValueEl($item);
            var itemCode = this.getItemCode($item);

            var self = this;
            $.each(this.itemToChildren[itemCode] || [], function() {
                var $toValue = self.getValueEl(this);
                self.cloneValue($fromValue, $toValue);
            });
        },

        /**
         * Enable/disable controls depending on the "use fallback"
         *
         * @param {jQuery} $item
         */
        switchUseFallback: function($item) {
            var $useFallback = this.getUseFallbackEl($item);
            if ($useFallback.length === 0) {
                return ;
            }

            var checked = $useFallback.get(0).checked;

            this.enableDisableValue(this.getValueEl($item), !checked);
            this.enableDisableFallback(this.getFallbackEl($item), checked);
        },

        /**
         * Enable/disable value
         *
         * @param {jQuery} $value
         * @param {Boolean} enable
         */
        enableDisableValue: function($value, enable) {
            var $valueContainer = $value.closest(this.options.selectors.itemValue);

            var editor;
            if ($valueContainer.find('.mce-tinymce').length > 0) {
                editor = $valueContainer.find('textarea').tinymce();
            }

            if (enable) {
                $value.removeAttr('disabled');

                if (editor) {
                    editor.getBody().setAttribute('contenteditable', true);
                    $(editor.editorContainer).removeClass('disabled');
                    $(editor.editorContainer).children('.disabled-overlay').remove();
                }
            } else {
                $value.attr('disabled', 'disabled');

                if (editor) {
                    editor.getBody().setAttribute('contenteditable', false);
                    $(editor.editorContainer).addClass('disabled');
                    $(editor.editorContainer).append('<div class="disabled-overlay"></div>');
                }
            }
        },

        /**
         * Enable/disable fallback
         *
         * @param {jQuery} $fallback
         * @param {Boolean} enable
         */
        enableDisableFallback: function($fallback, enable) {
            var $fallbackContainer = $fallback.inputWidget('getContainer');

            if (enable) {
                $fallback.removeAttr('disabled');

                if ($fallbackContainer) {
                    $fallbackContainer.removeClass('disabled');
                }
            } else {
                $fallback.attr('disabled', 'disabled');

                if ($fallbackContainer) {
                    $fallbackContainer.addClass('disabled');
                }
            }

            $fallback.change();
        },

        /**
         * Clone value to another value
         *
         * @param {jQuery} $fromValue
         * @param {jQuery} $toValue
         */
        cloneValue: function($fromValue, $toValue) {
            $fromValue.each(function(i) {
                var toValue = $toValue.get(i);

                if ($(this).is(':checkbox') || $(this).is(':radio')) {
                    toValue.checked = this.checked;
                } else {
                    $(toValue).val($(this).val());
                }
            });

            $toValue.filter(':first').change();
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
         * Get "use fallback" element
         *
         * @param {jQuery} $el
         *
         * @returns {jQuery}
         */
        getUseFallbackEl: function($el) {
            return $el.find(this.options.selectors.itemUseFallback).find('input');
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

            var parentItemCode = $select.attr('data-parent-locale');
            return parentItemCode && $select.val() !== 'system' ? parentItemCode : $select.val();
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
                itemCode = $select.attr('data-locale');
            }

            return itemCode;
        },

        /**
         * Check is child has custom value
         */
        isChildEdited: function() {
            var isChildEdited = false;
            var $childItems = this.$el.find(this.options.selectors.childItem);

            this.getValueEl($childItems).each(function() {
                if (this.disabled) {
                    return;
                }

                if ($(this).is(':checkbox') || $(this).is(':radio')) {
                    isChildEdited = true;
                } else {
                    isChildEdited = $(this).val().length > 0;
                }

                if (isChildEdited) {
                    return false;
                }
            });

            return isChildEdited;
        },

        /**
         * Set fallback selector width depending of their content
         */
        fixFallbackWidth: function() {
            var $fallback = this.$el.find(this.options.selectors.itemFallback).find('select');
            $fallback.inputWidget('setWidth', this.options.fallbackWidth);
        },

        /**
         * Change status icon depending on expanded flag and child custom values
         */
        setStatusIcon: function() {
            var icon;

            if (this.options.expanded) {
                icon = 'save';
            } else if (this.isChildEdited()) {
                icon = 'edited';
            } else {
                icon = 'new';
            }

            icon = this.options.icons[icon];

            this.$el.find(this.options.selectors.status)
                .html(icon.html)
                .find('i').click(_.bind(this[icon.event], this));

            var $defaultLabel = this.$el.find(this.options.selectors.defaultItem)
                .find(this.options.selectors.itemLabel);
            var $childItems = this.$el.find(this.options.selectors.childItem);

            if (this.options.expanded) {
                if (this.options.hideDefaultLabel) {
                    $defaultLabel.show();
                }
                $childItems.show();
            } else {
                if (this.options.hideDefaultLabel) {
                    $defaultLabel.hide();
                }
                $childItems.hide();
            }
        }
    });

    return FallbackView;
});
