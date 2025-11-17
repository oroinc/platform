import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!oroform/templates/inline-editable-wrapper-view.html';

/**
 * Tags view, able to handle either `collection` of tags or plain array of `items`.
 *
 * @class
 */
const InlineEditorWrapperView = BaseView.extend({
    template,

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

export default InlineEditorWrapperView;
