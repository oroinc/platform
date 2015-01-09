define(function (require) {
    'use strict';

    var WysiwygEditorView,
        BaseView = require('oroui/js/app/views/base/view'),
        $ = require('tinymce/jquery.tinymce.min'),
        txtHtmlTransformer = require('./txt-html-transformer'),
        LoadingMask = require('oroui/js/loading-mask');

    WysiwygEditorView = BaseView.extend({
        autoRender: true,
        firstRender: true,

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
                if (!this.tinymceInstance) {
                    throw new Error('Cannot disable tinyMCE before its instance is created');
                }
                this.tinymceInstance.remove();
                this.tinymceInstance = null;

                // strip tags when disable HTML editing mode
                this.htmlValue = this.$el.val();
                this.strippedValue = txtHtmlTransformer.html2text(this.htmlValue);
                this.$el.val(this.strippedValue);

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
                if (!this.firstRender) {
                    if (this.htmlValue && this.$el.val() === this.strippedValue) {
                        // if content is not modified, return html representation back
                        this.$el.val(this.htmlValue);
                    } else {
                        this.$el.val(txtHtmlTransformer.text2html(this.$el.val()));
                    }
                }
                this.renderDeffered = $.Deferred();
                this.$el.tinymce(_.extend({
                    init_instance_callback: function (editor) {
                        /**
                         * fix of https://magecore.atlassian.net/browse/BAP-7130
                         * "WYSWING editor does not work with IE"
                         * Please check if it's still required after tinyMCE update
                         */
                        setTimeout(function () {
                            var focusedElement = $(':focus');
                            editor.focus();
                            focusedElement.focus();
                        }, 0);

                        loadingMask.dispose();
                        self.tinymceInstance = editor;
                        self.renderDeffered.resolve();
                        delete self.renderDeffered;
                    }
                }, this.options));
                this.tinymceConnected = true;
            }
            this.firstRender = false;
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
