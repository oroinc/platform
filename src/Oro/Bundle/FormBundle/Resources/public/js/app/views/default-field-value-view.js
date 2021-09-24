define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
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
                this.$(this.checkboxSelector).filter(':checked').each(_.bind(function(i, e) {
                    this._setFieldsState($(e), true);
                }, this));
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

            valueEls.each(function(i, el) {
                const $el = $(el);

                $el
                    .prop('disabled', value)
                    .data('disabled', value)
                    .attr('disabled', value)
                    .trigger(value ? 'disable' : 'enable')
                    .inputWidget('refresh');
            });

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
                    editor.setMode($(el).prop('disabled') ? 'readonly' : 'design');
                }
            });
        }
    });

    return DefaultFieldValueView;
});
