define(function (require) {
    'use strict';

    var WysiwygEditorView,
        BaseView = require('oroui/js/app/views/base/view'),
        $ = require('tinymce');
    require('tinymce.textcolor');
    require('tinymce.code');

    WysiwygEditorView = BaseView.extend({
        autoRender: true,
        defaults: {
            enabled: true,
            plugins: ['textcolor', 'code'],
            menubar : false,
            toolbar: ['undo redo | bold italic underline | forecolor backcolor | bullist numlist | code'],
            statusbar : false
        },

        initialize: function (options) {
            options = $.extend(true, {}, this.defaults, options);
            this.enabled = options.enabled;
            this.options = _.omit(options, ['enabled']);
            WysiwygEditorView.__super__.initialize.apply(this, arguments);
        },

        render: function () {
            if (this.enabled) {
                this.$el.tinymce(this.options);
            }
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
