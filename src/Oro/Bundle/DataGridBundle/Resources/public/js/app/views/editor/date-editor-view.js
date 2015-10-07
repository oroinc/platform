/** @lends DateEditorView */
define(function(require) {
    'use strict';

    /**
     * Date cell content editor
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrid:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     columns:
     *       # Sample 1. Mapped by number frontend type
     *       {column-name-1}:
     *         frontend_type: date
     *       # Sample 2. Full configuration
     *       {column-name-2}:
     *         inline_editing:
     *           editor:
     *             view: orodatagrid/js/app/views/editor/date-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               datePickerOptions:
     *                 altFormat: 'yy-mm-dd'
     *                 changeMonth: true
     *                 changeYear: true
     *                 yearRange: '-80:+1'
     *                 showButtonPanel: true
     *           validationRules:
     *             # jQuery.validate configuration
     *             required: true
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder for empty element
     * inline_editing.editor.view_options.dateInputAttrs   | Optional. Attributes for date HTML input element
     * inline_editing.editor.view_options.datePickerOptions| Optional. See [documentation here](http://goo.gl/pddxZU)
     * inline_editing.editor.validationRules               | Optional. Client side validation rules
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Backgrid.Cell} options.cell - Current datagrid cell
     * @param {Backgrid.Column} options.column - Current datagrid column
     * @param {string} options.placeholder - Placeholder for empty element
     * @param {Object} options.validationRules - Validation rules in form applicable to jQuery.validate
     * @param {Object} options.dateInputAttrs - Attributes for date HTML input element
     * @param {Object} options.datePickerOptions - See [documentation here](http://goo.gl/pddxZU)
     *
     * @augments [TextEditorView](./text-editor-view.md)
     * @exports DateEditorView
     */
    var DateEditorView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var moment = require('moment');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var TextEditorView = require('./text-editor-view');
    var DatepickerView = require('oroui/js/app/views/datepicker/datepicker-view');

    DateEditorView = TextEditorView.extend(/** @exports DateEditorView.prototype */{
        className: 'date-editor',
        inputType: 'date',
        view: DatepickerView,

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

        format: datetimeFormatter.backendFormats.date,

        render: function() {
            DateEditorView.__super__.render.call(this);
            var View = this.view;
            this.view = new View(this.getViewOptions());
            // fix enter behaviour
            this.$('.hasDatepicker').on('keydown' + this.eventNamespace(), _.bind(this.onGenericEnterKeydown, this));
            // fix esc behaviour
            this.$('.hasDatepicker').on('keydown' + this.eventNamespace(), _.bind(this.onGenericEscapeKeydown, this));
        },

        onGenericEnterKeydown: function(e) {
            if (e.keyCode === this.ENTER_KEY_CODE) {
                // there is no other way to get if datepicker is visible
                if ($('#ui-datepicker-div').is(':visible')) {
                    this.$('.hasDatepicker').datepicker('hide');
                } else {
                    DateEditorView.__super__.onGenericEnterKeydown.apply(this, arguments);
                }
            }
        },

        onGenericEscapeKeydown: function(e) {
            if (e.keyCode === this.ESCAPE_KEY_CODE) {
                // there is no other way to get if datepicker is visible
                if ($('#ui-datepicker-div').is(':visible')) {
                    this.$('.hasDatepicker').datepicker('hide');
                } else {
                    DateEditorView.__super__.onGenericEscapeKeydown.apply(this, arguments);
                }
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$('.hasDatepicker').off(this.eventNamespace());
            this.view.dispose();
            DateEditorView.__super__.dispose.call(this);
        },

        getViewOptions: function() {
            return $.extend(true, {}, this.DEFAULT_OPTIONS,
                _.pick(this.options, ['dateInputAttrs', 'datePickerOptions']), {
                    el: this.$('input[name=value]')
                });
        },

        focus: function() {
            this.$('input.hasDatepicker').setCursorToEnd().focus();
        },

        getModelValue: function() {
            var raw = this.model.get(this.column.get('name'));
            try {
                return datetimeFormatter.getMomentForBackendDate(raw);
            } catch (e) {
                try {
                    return datetimeFormatter.getMomentForBackendDateTime(raw);
                } catch (e2) {
                    return null;
                }
            }
        },

        getFormattedValue: function() {
            var value = this.getModelValue();
            if (value === null) {
                return '';
            }
            return value.format(this.format);
        },

        getValue: function() {
            var raw = this.$('input[name=value]').val();
            return !raw ? null : moment.utc(raw, this.format);
        },

        isChanged: function() {
            var value = this.getValue();
            var modelValue = this.getModelValue();
            if (value !== null && modelValue !== null) {
                return value.diff(modelValue);
            }
            return value !== modelValue;
        }
    });

    return DateEditorView;
});
