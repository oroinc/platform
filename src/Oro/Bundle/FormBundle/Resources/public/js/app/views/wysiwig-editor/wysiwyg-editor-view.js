define(function (require) {
    'use strict';

    var WysiwygEditorView,
        BaseView = require('oroui/js/app/views/base/view'),
        $ = require('tinymce/jquery.tinymce.min');
    require('tinymce/plugins/textcolor/plugin.min');
    require('tinymce/plugins/code/plugin.min');

    WysiwygEditorView = BaseView.extend({
        autoRender: true,

        tinymceInstance: null,

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
            if (this.tinymceInstance) {
                this.tinymceInstance.remove();
                this.tinymceInstance = null;
            }
            if (this.enabled) {
                this.$el.tinymce(this.options);
                this.tinymceInstance = this.$el.tinymce();
            }
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }
            if (this.tinymceInstance) {
                this.tinymceInstance.remove();
                this.tinymceInstance = null;
            }
            WysiwygEditorView.__super__.dispose.call(this);
        }
    });

    return WysiwygEditorView;
});
