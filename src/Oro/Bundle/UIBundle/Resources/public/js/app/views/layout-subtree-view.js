define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const LayoutSubtreeManager = require('oroui/js/layout-subtree-manager');
    const mediator = require('oroui/js/mediator');
    const $ = require('jquery');
    const _ = require('underscore');

    const LayoutSubtreeView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'keepAttrs', 'useHiddenElement'
        ]),

        options: {
            blockId: '',
            reloadEvents: [],
            showLoading: true,
            restoreFormState: false
        },

        formState: null,

        /** @property */
        hiddenElement: null,

        /** @property */
        useHiddenElement: false,

        keepAttrs: [],

        /** @property */
        events: {
            'content:initialized': 'contentInitialized'
        },

        /**
         * @inheritDoc
         */
        constructor: function LayoutSubtreeView(options) {
            LayoutSubtreeView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);
            LayoutSubtreeView.__super__.initialize.call(this, options);
            LayoutSubtreeManager.addView(this);
        },

        dispose: function() {
            LayoutSubtreeManager.removeView(this);
            return LayoutSubtreeView.__super__.dispose.call(this);
        },

        setContent: function(content) {
            const $content = $(content);
            this._hideLoading();
            this.disposePageComponents();

            if (this.useHiddenElement) {
                // Create a hidden element in which initialization will be performed
                this.hiddenElement = this.$el.clone(true).hide();
                this.hiddenElement.insertAfter(this.$el);
                this.hiddenElement.trigger('content:remove')
                    .html($content.children())
                    .trigger('content:changed');
            } else {
                this.$el
                    .trigger('content:remove')
                    .html($content.children())
                    .trigger('content:changed');
            }

            if (this.keepAttrs.length) {
                for (const attr of this.keepAttrs) {
                    this.$el.attr(attr, $content.attr(attr));
                }
            }
        },

        beforeContentLoading: function() {
            this._showLoading();

            if (this.options.restoreFormState) {
                this._saveFormState();
            }
        },

        afterContentLoading: function() {
            this._restoreFormState();
            // Initialize tooltips and other elements
            mediator.execute('layout:init', this.useHiddenElement ? this.hiddenElement : this.$el);
        },

        contentLoadingFail: function() {
            this._hideLoading();
        },

        _showLoading: function() {
            if (!this.options.showLoading) {
                return;
            }
            let $container = this.$el.closest('[data-role="layout-subtree-loading-container"]');
            if (!$container.length) {
                $container = this.$el;
            }
            this.subview('loadingMask', new LoadingMaskView({
                container: $container
            }));
            this.subview('loadingMask').show();
        },

        _hideLoading: function() {
            if (!this.options.showLoading) {
                return;
            }
            this.removeSubview('loadingMask');
        },

        _saveFormState: function() {
            this.formState = {};

            _.each(this._getInputs(), _.bind(function(input) {
                let name = input.name;
                let value = input.value;

                if ($(input).is(':checkbox, :radio')) {
                    name += ':' + value;
                    value = input.checked;
                }
                this.formState[name] = value;
            }, this));
        },

        _restoreFormState: function() {
            if (!this.formState) {
                return;
            }

            _.each(this._getInputs(), _.bind(function(input) {
                let name = input.name;
                const isRadio = $(input).is(':checkbox, :radio');
                if (isRadio) {
                    name += ':' + input.value;
                }

                const savedInput = this.formState[name];
                if (savedInput === undefined || (isRadio && !savedInput)) {
                    return;
                }

                const $input = $(input);
                if (isRadio) {
                    $input.click();
                } else {
                    $input.val(savedInput).change();
                }
            }, this));
        },

        _getInputs: function() {
            return this.$el.find('input, textarea, select').filter('[name!=""]');
        },

        contentInitialized: function() {
            if (this.useHiddenElement) {
                // Replace a target element with an initialized hidden element
                this.$el.html(this.hiddenElement.html());
                // Remove a hidden element
                this.hiddenElement.remove();
                this.hiddenElement = null;
                this.useHiddenElement = false;
            }
        }
    });

    return LayoutSubtreeView;
});
