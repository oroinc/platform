define(function (require) {
    'use strict';

    var WysiwygEditorView,
        BaseView = require('oroui/js/app/views/base/view');
    require('tinymce');
    require('tinymce.textcolor');
    require('tinymce.code');

    WysiwygEditorView = BaseView.extend({
        autoRender: true,
        defaults: {
            plugins: ['textcolor', 'code'],
            menubar : false,
            toolbar: ['undo redo | bold italic underline | forecolor backcolor | bullist numlist | code'],
            statusbar : false
        },

        render: function () {
            this.$el.tinymce(this.defaults);
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }
            this.$el.tinymce().remove();
            WysiwygEditorView.__super__.dispose.call(this);
        }
    });

    return WysiwygEditorView;
});
