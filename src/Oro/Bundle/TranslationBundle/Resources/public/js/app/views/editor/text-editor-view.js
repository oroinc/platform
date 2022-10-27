define(function(require) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroform/js/app/views/editor/text-editor-view');

    // TODO: will be removed after https://magecore.atlassian.net/browse/BAP-11905
    const TextEditorView = BaseView.extend({
        template: require('tpl-loader!../../../../templates/text-editor.html'),

        events: {
            'change [name=value]': 'onChange',
            'keyup [name=value]': 'onChange',
            'keydown [name=value]': 'onGenericKeydown'
        },

        /**
         * @inheritdoc
         */
        constructor: function TextEditorView(options) {
            TextEditorView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        focus: function(atEnd) {
            this.$('[name=value]').setCursorToEnd().focus();
            this.updateButtonsOffset();
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
        onGenericKeydown: function(e) {
            this.onGenericEnterKeydown(e);
            this.onGenericTabKeydown(e);
            this.onGenericArrowKeydown(e);
            this.onGenericEscapeKeydown(e);
        },

        /**
         * @inheritdoc
         */
        onGenericEnterKeydown: function(e) {
            if (e.keyCode === this.ENTER_KEY_CODE && (e.ctrlKey || e.shiftKey)) {
                const postfix = e.shiftKey ? 'AndEditPrevRow' : 'AndEditNextRow';
                if (this.isChanged()) {
                    if (this.validator.form()) {
                        this.trigger('save' + postfix + 'Action');
                    } else {
                        this.focus();
                    }
                } else {
                    this.trigger('cancel' + postfix + 'Action');
                }

                e.stopImmediatePropagation();
                e.preventDefault();
            }
        },

        onChange: function() {
            this.updateButtonsOffset();

            TextEditorView.__super__.onChange.call(this);
        },

        onKeyUp: function() {
            this.updateButtonsOffset();

            TextEditorView.__super__.onKeyUp.call(this);
        },

        updateButtonsOffset: function() {
            const $field = this.$('[name=value]');

            if ($field.is('textarea')) {
                this.$('[data-role="actions"]').css(
                    `margin${_.isRTL() ? 'Left' : 'Right'}`, $field[0].clientHeight < $field[0].scrollHeight
                        ? `${mediator.execute('layout:scrollbarWidth')}px` : ''
                );
            }
        }
    });

    return TextEditorView;
});
