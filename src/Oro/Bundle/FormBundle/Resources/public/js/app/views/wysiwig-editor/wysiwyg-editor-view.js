define(function (require) {
    'use strict';

    var WysiwygEditorView,
        BaseView = require('oroui/js/app/views/base/view'),
        $ = require('tinymce/jquery.tinymce.min'),
        LoadingMask = require('oroui/js/loading-mask');
    require('tinymce/plugins/textcolor/plugin.min');
    require('tinymce/plugins/code/plugin.min');

    WysiwygEditorView = BaseView.extend({
        autoRender: true,

        tinymceConnected: false,
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
            var self = this,
                loadingMask,
                loadingMaskContainer;
            if (this.tinymceConnected) {
                if (this.tinymceInstance) {
                    this.tinymceInstance.remove();
                    this.tinymceInstance = null;
                }
                this.$el.show();
                this.tinymceConnected = false;
            }
            if (this.enabled) {
                loadingMask = new LoadingMask();
                loadingMask.render();
                loadingMaskContainer = this.$el.parents('.ui-dialog');
                if (!loadingMaskContainer.length) {
                    loadingMaskContainer = this.$el.parent();
                }
                loadingMask.$el.prependTo(loadingMaskContainer);
                loadingMask.show();
                this.$el.tinymce(_.extend({
                    init_instance_callback: function (editor) {
                        self.tinymceInstance = editor;
                        loadingMask.dispose();
                    }
                }, this.options));
                this.tinymceConnected = true;
            }
        },

        setEnabled: function (enabled) {
            if (this.enabled === enabled) {
                return;
            }
            this.enabled = enabled;
            this.render();
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
