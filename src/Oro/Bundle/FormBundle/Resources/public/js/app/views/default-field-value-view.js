define(function(require) {
    'use strict';

    var DefaultFieldValueView;
    var $ = require('jquery');
    var tinyMCE = require('tinymce/tinymce');
    var BaseView = require('oroui/js/app/views/base/view');

    DefaultFieldValueView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat(['prepareTinymce', 'fieldSelector', 'checkboxSelector']),

        prepareTinymce: true,

        checkboxSelector: '[data-role="changeUseDefault"]',

        events: {
            'change [data-role="changeUseDefault"]': 'onDefaultCheckboxChange'
        },

        /**
         * @inheritDoc
         */
        constructor: function DefaultFieldValueView() {
            DefaultFieldValueView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            if (this.fieldSelector && this.$(this.checkboxSelector).is(':checked')) {
                this.$(this.fieldSelector).prop('disabled', true);
            }

            return DefaultFieldValueView.__super__.render.apply(this, arguments);
        },

        onDefaultCheckboxChange: function(e) {
            var $currentTarget = $(e.currentTarget);
            var $controls = $currentTarget.parents('.controls');

            var value = $currentTarget.is(':checked');
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
