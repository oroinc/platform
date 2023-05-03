define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const tinyMCE = require('tinymce/tinymce');

    /**
     * @export orolocale/js/app/views/fallback-view
     * @extends oroui.app.views.base.View
     * @class orolocale.app.views.FallbackView
     */
    const FallbackView = BaseView.extend({
        autoRender: true,

        initSubviews: true,

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
            statusActiveClass: 'active',
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
                'new': {
                    html: '<span class="fa-language"></span>',
                    event: 'expandChildItems'
                },
                'edited': {
                    html: '<span class="fa-language"></span>',
                    event: 'expandChildItems'
                },
                'save': {
                    html: '<span class="fa-language"></span>',
                    event: 'collapseChildItems'
                }
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function FallbackView(options) {
            FallbackView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            FallbackView.__super__.initialize.call(this, options);
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
                this.bindEvents();
            }.bind(this));

            return this;
        },

        renderSubviews: function() {
            this.initSubviews = false;
            this.$(this.options.selectors.childItem).removeAttr('data-layout');

            const self = this;
            this.getValueEl(this.$el).each(function() {
                self.cloneValueToChildren(self.getItemEl(this));
            });
            this.initLayout();
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            const self = this;

            this.mapItemsByCode();

            this.getUseFallbackEl(this.$el).each(function() {
                self.switchUseFallback(self.getItemEl(this));
            });

            this.mapItemToChildren();

            this.getValueEl(this.$el).each(function() {
                // self.cloneValueToChildren(self.getItemEl(this)); uncomment on merging master
            });

            this.setStatusIcon();
        },

        /**
         * Bind events to controls
         */
        bindEvents: function() {
            const self = this;

            this.getValueEl(this.$el)
                .change(this.cloneValueToChildrenEvent.bind(this))
                .keyup(this.cloneValueToChildrenEvent.bind(this));

            this.$el.find(this.options.selectors.itemValue).find('.tox-tinymce').each(function() {
                tinyMCE.get(self.getValueEl(self.getItemEl(this)).attr('id'))
                    .on('change', function() {
                        $(this.targetElm).change();
                    })
                    .on('keyup', function() {
                        $(this.targetElm).change();
                    });
            });

            this.getUseFallbackEl(this.$el)
                .change(this.switchUseFallbackEvent.bind(this));

            this.getFallbackEl(this.$el)
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
            const $item = this.getItemEl(e.currentTarget);

            this.mapItemToChildren();

            const parentItemCode = this.getParentItemCode($item);
            if (parentItemCode) {
                const $fromValue = this.getValueEl(this.itemsByCode[parentItemCode]);
                const $toValue = this.getValueEl($item);
                this.cloneValue($fromValue, $toValue);
            } else {
                this.cloneValueToChildrenEvent(e);
            }
        },

        /**
         * Show child items
         */
        expandChildItems: function() {
            if (this.initSubviews) {
                this.renderSubviews();
            }

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
            const $fromValue = this.getValueEl($item);
            const itemCode = this.getItemCode($item);

            const self = this;
            $.each(this.itemToChildren[itemCode] || [], function() {
                const $toValue = self.getValueEl(this);
                self.cloneValue($fromValue, $toValue);
            });
        },

        /**
         * Enable/disable controls depending on the "use fallback"
         *
         * @param {jQuery} $item
         */
        switchUseFallback: function($item) {
            const $useFallback = this.getUseFallbackEl($item);
            if ($useFallback.length === 0) {
                return;
            }

            const checked = $useFallback.get(0).checked;

            this.enableDisableValue(this.getValueEl($item), !checked);
            this.enableDisableFallback(this.getFallbackEl($item), checked);
        },

        /**
         * Enable/disable value
         *
         * @param {jQuery} $element
         * @param {Boolean} enable
         */
        enableDisableValue: function($element, enable) {
            const $$elementContainer = $element.closest(this.options.selectors.itemValue);

            let editor;
            if ($$elementContainer.find('.tox-tinymce').length > 0) {
                editor = tinyMCE.get($$elementContainer.find('textarea').attr('id'));
            }

            if (enable) {
                $element.removeAttr('disabled');

                if (editor) {
                    editor.mode.set('design');
                    $(editor.editorContainer).removeClass('disabled');
                    $(editor.editorContainer).children('.disabled-overlay').remove();
                }
            } else {
                $element.attr('disabled', 'disabled');

                if (editor) {
                    editor.mode.set('readonly');
                    $(editor.editorContainer).addClass('disabled');
                    $(editor.editorContainer).children('.disabled-overlay').remove();
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
            const $fallbackContainer = $fallback.inputWidget('getContainer');

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
            let isChanged = false;
            $fromValue.each(function(i) {
                const toValue = $toValue.get(i);
                if ($(this).is(':checkbox') || $(this).is(':radio')) {
                    if (toValue.checked !== this.checked) {
                        isChanged = true;
                        toValue.checked = this.checked;
                    }
                } else {
                    if ($(toValue).val() !== $(this).val()) {
                        isChanged = true;
                        $(toValue).val($(this).val());
                    }
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
            const $select = this.getFallbackEl($item);
            if ($select.length === 0 || $select.attr('disabled')) {
                return;
            }

            const parentItemCode = $select.attr('data-parent-localization');

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
            const $select = this.getFallbackEl($item);
            let itemCode;

            if ($select.length === 0) {
                itemCode = 'system';
            } else {
                itemCode = $select.attr('data-localization');
            }

            return itemCode;
        },

        /**
         * Check is child has custom value
         */
        isChildEdited: function() {
            let isChildEdited = false;
            const $childItems = this.$el.find(this.options.selectors.childItem);

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
         * Change status icon depending on expanded flag and child custom values
         */
        setStatusIcon: function() {
            let icon;

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
                .one('click' + this.eventNamespace(), this[icon.event].bind(this))
                .toggleClass(this.options.statusActiveClass, this.options.expanded);

            const $defaultLabel = this.$el.find(this.options.selectors.defaultItem)
                .find(this.options.selectors.itemLabel);
            const $childItems = this.$el.find(this.options.selectors.childItem);

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
