define(function(require) {
    'use strict';
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
        onInlineEditingStart: function() {
            this.trigger('start-editing');
        },
        getContainer: function() {
            return this.$('[data-role="container"]');
        }
    });

    return InlineEditorWrapperView;
});
