define(function(require) {
    'use strict';

    var TextEditorView;
    var BaseView = require('oroform/js/app/views/editor/text-editor-view');

    // TODO: will be remove after https://magecore.atlassian.net/browse/BAP-11905
    TextEditorView = BaseView.extend({
        template: require('tpl!../../../../templates/text-editor.html'),
        events: {
            'change [name=value]': 'onChange',
            'keyup [name=value]': 'onChange',
            'keydown [name=value]': 'onGenericKeydown',
        },

        /**
         * @inheritdoc
         */
        focus: function(atEnd) {
            this.$('[name=value]').setCursorToEnd().focus();
        },

        /**
         * @inheritdoc
         */
        getValue: function() {
            return this.$('[name=value]').val();
        },

        /**
         * @inheritdoc
         */
        rethrowEvent: function(e) {
        },

        /**
         * @inheritdoc
         */
        onGenericEnterKeydown: function(e) {
            if (e.keyCode === this.ENTER_KEY_CODE && e.ctrlKey) {
                if (this.isChanged()) {
                    if (this.validator.form()) {
                        this.trigger('saveAndEditNextRowAction');
                    } else {
                        this.focus();
                    }
                } else {
                    this.trigger('cancelAndEditNextRowAction');
                }

                e.stopImmediatePropagation();
                e.preventDefault();
            }
        }
    });

    return TextEditorView;
});
