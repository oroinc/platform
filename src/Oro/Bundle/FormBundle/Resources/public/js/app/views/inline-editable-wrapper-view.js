define(function(require) {
    'use strict';
    const __ = require('orotranslation/js/translator');
    const BaseView = require('oroui/js/app/views/base/view');

    /**
     * Tags view, able to handle either `collection` of tags or plain array of `items`.
     *
     * @class
     */
    const InlineEditorWrapperView = BaseView.extend({
        template: require('tpl-loader!oroform/templates/inline-editable-wrapper-view.html'),

        events: {
            'dblclick': 'onInlineEditingStart',
            'click [data-role="start-editing"]': 'onInlineEditingStart'
        },

        /**
         * @inheritdoc
         */
        constructor: function InlineEditorWrapperView(options) {
            InlineEditorWrapperView.__super__.constructor.call(this, options);
        },

        setElement: function(element) {
            element.addClass('inline-editable-wrapper');
            element.attr('title', __('oro.form.inlineEditing.helpMessage'));
            return InlineEditorWrapperView.__super__.setElement.call(this, element);
        },

        onInlineEditingStart: function() {
            this.trigger('start-editing');
        },

        getContainer: function() {
            return this.$('[data-role="container"]');
        }
    });

    return InlineEditorWrapperView;
});
