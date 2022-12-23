define(function(require) {
    'use strict';

    const $ = require('jquery');
    const tinyMCE = require('tinymce/tinymce');
    const BaseView = require('oroui/js/app/views/base/view');

    const DefaultFieldValueView = BaseView.extend({
        /**
         * @inheritdoc
         * @property
         */
        autoRender: true,

        /**
         * @inheritdoc
         */
        optionNames: BaseView.prototype.optionNames.concat(['prepareTinymce', 'fieldSelector', 'checkboxSelector']),

        /**
         * @property {Boolean}
         */
        prepareTinymce: true,

        /**
         * @property {String}
         */
        checkboxSelector: '[data-role="changeUseDefault"]',

        /**
         * @property {String}
         */
        itemUseFallback: '.fallback-item-use-fallback input',

        /**
         * @inheritdoc
         */
        events: {
            'change [data-role="changeUseDefault"]': 'onDefaultCheckboxChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function DefaultFieldValueView(options) {
            DefaultFieldValueView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         * @returns {*}
         */
        render: function() {
            if (this.$(this.checkboxSelector).is(':checked')) {
                this.$(this.checkboxSelector).filter(':checked').each((i, e) => {
                    this._setFieldsState($(e), true);
                });
            }

            return DefaultFieldValueView.__super__.render.call(this);
        },

        /**
         * On change checkbox handler
         * @param e
         */
        onDefaultCheckboxChange: function(e) {
            const $currentTarget = $(e.currentTarget);
            const value = $currentTarget.is(':checked');

            this._setFieldsState($currentTarget, value);
        },

        /**
         * Change field status
         *
         * @param $element
         * @param value
         * @private
         */
        _setFieldsState: function($element, value) {
            const $controls = $element.parents('.controls');
            const valueEls = $controls.find(':input, a.btn, button')
                .not(this.$(this.checkboxSelector))
                .not('[readonly]');
            const itemUseFallbackEls = $controls.find(this.itemUseFallback);

            valueEls.each(function(i, el) {
                const $el = $(el);

                $el
                    .prop('disabled', value)
                    .data('disabled', value)
                    .attr('disabled', value)
                    .trigger(value ? 'disable' : 'enable')
                    .inputWidget('refresh');
            });

            // Force to refresh fallback fields after re-enable
            if (!value) {
                itemUseFallbackEls.trigger('change');
            }

            if (this.prepareTinymce) {
                this._prepareTinymce($controls.find('textarea'));
            }
        },

        /**
         * Set enable/disable tinymce field
         *
         * @param $textareas
         * @private
         */
        _prepareTinymce: function($textareas) {
            $textareas.each(function(i, el) {
                const editor = tinyMCE.get(el.id);
                if (editor) {
                    editor.mode.set($(el).prop('disabled') ? 'readonly' : 'design');
                }
            });
        }
    });

    return DefaultFieldValueView;
});
