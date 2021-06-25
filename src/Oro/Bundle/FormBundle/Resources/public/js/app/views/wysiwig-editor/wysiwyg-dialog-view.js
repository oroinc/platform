define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');

    const WysiwygDialogView = BaseView.extend({
        autoRender: true,

        // PLEASE don't make this value less than 180px - IE will display editor with bugs
        // (to adjust need to also change tinymce iframe stylesheet body{min-height:100px} style,
        // see Oro\Bundle\FormBundle\Resources\public\css\scss\tinymce\wysiwyg-editor.scss)
        minimalWysiwygEditorHeight: 180,

        /**
         * Name of related WYSIWYG editor component
         *
         * @type {string}
         */
        editorComponentName: null,

        listen: {
            'component:parentResize': 'resizeEditor'
        },

        /**
         * @inheritdoc
         */
        constructor: function WysiwygDialogView(options) {
            WysiwygDialogView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['minimalWysiwygEditorHeight', 'editorComponentName']));
            WysiwygDialogView.__super__.initialize.call(this, options);
        },

        render: function() {
            this._deferredRender();
            this.initLayout().done(() => {
                if (this.getDialogContainer().length) {
                    // there's dialog widget -- subscribe to resize event
                    this.listenTo(this.getEditorView(), 'resize', this.resizeEditor());
                }
                this._resolveDeferredRender();
            });
        },

        resizeEditor: function() {
            if (this.$el.closest('[data-spy="scroll"]').length) {
                // switch off resizer in case an editor is inside of scroll spy
                return;
            }
            this.getEditorView().setHeight(this.calcWysiwygHeight());
        },

        calcWysiwygHeight: function() {
            const content = this.getDialogContainer();
            const widgetContent = this.$el.closest('.widget-content')[0] || content.children().first().get(0);
            const editorHeight = this.getEditorView().getHeight();
            const style = getComputedStyle(widgetContent);
            const availableHeight = editorHeight +
                content[0].offsetHeight - parseFloat(style.marginTop) - parseFloat(style.marginBottom) +
                -widgetContent.offsetHeight;
            return Math.floor(Math.max(availableHeight, this.minimalWysiwygEditorHeight));
        },

        getEditorView: function() {
            const editor = this.pageComponent(this.editorComponentName);
            if (!editor) {
                throw new Error('Could not find message editor');
            }
            return editor.view;
        },

        getDialogContainer: function() {
            return this.$el.closest('.ui-widget-content');
        }
    });

    return WysiwygDialogView;
});
