/** @lends SelectEditorView */
define(function(require) {
    'use strict';

    /**
     *
     * @TODO update doc
     *
     * @augments [TextEditorView](./text-editor-view.md)
     * @exports StringArrayEditor
     */
    var StringArrayEditor;
    var TextEditorView = require('./text-editor-view');
    var $ = require('jquery');
    var _ = require('underscore');
    require('jquery.select2');

    StringArrayEditor = TextEditorView.extend(/** @exports SelectEditorView.prototype */{
        template: require('tpl!../../../../templates/string-array-editor.html'),
        className: 'string-array-editor',

        render: function() {
            TextEditorView.__super__.render.call(this);
            this.$el.addClass(_.result(this, 'className'));
            this.validator = this.$el.validate({
                submitHandler: _.bind(function(form, e) {
                    if (e && e.preventDefault) {
                        e.preventDefault();
                    }
                    this.trigger('saveAction');
                }, this),
                errorPlacement: function(error, element) {
                    error.appendTo($(element).closest('.inline-editor-wrapper'));
                },
                rules: {
                    value: this.getValidationRules()
                }
            });
            this.onChange();
        },
    });

    return StringArrayEditor;
});
