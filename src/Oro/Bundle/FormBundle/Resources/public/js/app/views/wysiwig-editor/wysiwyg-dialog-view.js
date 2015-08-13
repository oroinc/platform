define(function(require) {
    'use strict';

    var WysiwygDialogView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    WysiwygDialogView = BaseView.extend({
        autoRender: true,

        // PLEASE don't make this value less than 180px - IE will display editor with bugs
        // (to adjust need to also change tinymce iframe stylesheet body{min-height:100px} style,
        // see Oro\Bundle\FormBundle\Resources\public\css\wysiwyg-editor.css)
        minimalWysiwygEditorHeight: 180,

        listen: {
            'component:parentResize': 'resizeEditor'
        },

        initialize: function(options) {
            this.editorComponentName = options.editorComponentName;
            _.extend(this, _.pick(options, ['minimalWysiwygEditorHeight']));
            WysiwygDialogView.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            this._deferredRender();
            this.initLayout().done(_.bind(function() {
                this.listenTo(this.getEditorView(), 'resize', this.resizeEditor());
                this._resolveDeferredRender();
            }, this));
        },

        resizeEditor: function() {
            this.getEditorView().setHeight(this.calcWysiwygHeight());
        },

        calcWysiwygHeight: function() {
            var content = this.$el.closest('.ui-widget-content');
            var editorHeight = this.getEditorView().getHeight();
            var style = getComputedStyle(content[0]);
            var availableHeight = editorHeight +
                content[0].offsetHeight - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom) +
                -this.$el.closest('.widget-content')[0].offsetHeight;
            return Math.floor(Math.max(availableHeight, this.minimalWysiwygEditorHeight));
        },

        getEditorView: function() {
            var editor = this.pageComponent(this.editorComponentName);
            if (!editor) {
                throw new Error('Could not find message editor');
            }
            return editor.view;
        }
    });

    return WysiwygDialogView;
});
