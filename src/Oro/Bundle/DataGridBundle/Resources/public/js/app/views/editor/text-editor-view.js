define(function(require) {
    'use strict';

    var TextEditorView;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    TextEditorView = BaseView.extend({
        autoRender: true,
        template: require('tpl!../../../../templates/text-editor.html'),
        events: {
            'click [data-action]': 'rethrowAction'
        },

        /**
         * @inheritDoc
         */
        render: function() {
            TextEditorView.__super__.render.call(this);
            this.validator = this.$el.validate({
                submitHandler: _.bind(this.onSave, this),
                rules: {
                    value: this.model.get('validationRules') || {}
                }
            });
        },

        updateModel: function() {
            this.model.set({
                value: this.$('input[type=text]').val(),
                valid: this.$('input[type=text]').valid()
            });
        },

        onSave: function() {
            this.updateModel();
            this.trigger('saveAction');
        },

        rethrowAction: function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            this.updateModel();
            this.trigger($(e.currentTarget).attr('data-action') + 'Action');
        }
    });

    return TextEditorView;
});
