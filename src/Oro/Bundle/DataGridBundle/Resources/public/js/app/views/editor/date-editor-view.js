define(function(require) {
    'use strict';

    var DateEditorView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var TextEditorView = require('./text-editor-view');
    var DatepickerView = require('oroui/js/app/views/datepicker/datepicker-view');

    DateEditorView = TextEditorView.extend({
        className: 'date-editor',
        inputType: 'date',

        DEFAULT_OPTIONS: {
            dateInputAttrs: {
                placeholder: __('oro.form.choose_date')
            },
            datePickerOptions: {
                altFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: '-80:+1',
                showButtonPanel: true
            }
        },

        /**
         * @inheritDoc
         */
        render: function() {
            DateEditorView.__super__.render.call(this);
            this.view = new DatepickerView($.extend(true, {}, this.DEFAULT_OPTIONS,
                _.pick(this.options, ['dateInputAttrs', 'datePickerOptions']), {
                    el: this.$('input[name=value]')
                }));
        },

        focus: function() {
            this.$('input.hasDatepicker').focus();
        }
    });

    return DateEditorView;
});
