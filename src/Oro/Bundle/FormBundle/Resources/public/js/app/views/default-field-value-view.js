define(function(require) {
    'use strict';

    var DefaultFieldValueView;
    var $ = require('jquery');
    var _ = require('underscore');
    var tinyMCE = require('tinymce/tinymce');
    var BaseView = require('oroui/js/app/views/base/view');

    DefaultFieldValueView = BaseView.extend({
        /**
         * @inheritDoc
         * @property
         */
        autoRender: true,

        /**
         * @inheritDoc
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
         * @inheritDoc
         */
        events: {
            'change [data-role="changeUseDefault"]': 'onDefaultCheckboxChange'
        },

        /**
         * @inheritDoc
         */
        constructor: function DefaultFieldValueView() {
            DefaultFieldValueView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         * @returns {*}
         */
        render: function() {
            if (this.$(this.checkboxSelector).is(':checked')) {
                this.$(this.checkboxSelector).filter(':checked').each(_.bind(function(i, e) {
                    this._setFieldsState($(e), true);
                }, this));
            }

            return DefaultFieldValueView.__super__.render.apply(this, arguments);
        },

        /**
         * On change checkbox handler
         * @param e
         */
        onDefaultCheckboxChange: function(e) {
            var $currentTarget = $(e.currentTarget);
            var value = $currentTarget.is(':checked');

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
            var $controls = $element.parents('.controls');
            var valueEls = $controls.find(':input, a.btn, button')
                .not(this.$(this.checkboxSelector))
                .not('[readonly]');

            valueEls.each(function(i, el) {
                var $el = $(el);

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
                var editor = tinyMCE.get(el.id);
                if (editor) {
                    editor.setMode($(el).prop('disabled') ? 'readonly' : 'code');
                }
            });
        }
    });

    return DefaultFieldValueView;
});
