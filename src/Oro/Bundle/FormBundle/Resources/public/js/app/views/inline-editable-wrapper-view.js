define(function(require) {
    'use strict';
    var __ = require('orotranslation/js/translator');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * Tags view, able to handle either `collection` of tags or plain array of `items`.
     *
     * @class
     */
    var InlineEditorWrapperView = BaseView.extend({
        template: require('tpl!oroform/templates/inline-editable-wrapper-view.html'),

        events: {
            'dblclick': 'onInlineEditingStart',
            'click [data-role="start-editing"]': 'onInlineEditingStart'
        },

        setElement: function($el) {
            $el.addClass('inline-editable-wrapper');
            $el.attr('title', __('oro.form.inlineEditing.helpMessage'));
            return InlineEditorWrapperView.__super__.setElement.apply(this, arguments);
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
