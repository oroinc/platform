define(function(require) {
    'use strict';

    var WysiwygDialogView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    WysiwygDialogView = BaseView.extend({
        autoRender: true,

        minimalWysiwygEditorHeight: 150,

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
            var outerHeight = this.$el.closest('.ui-widget-content').innerHeight();
            var innerHeight = this.$el.closest('.widget-content').height();
            var editorHeight = this.getEditorView().getHeight();
            var availableHeight = editorHeight + outerHeight - innerHeight;
            return Math.max(availableHeight, this.minimalWysiwygEditorHeight);
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
