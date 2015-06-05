define(function (require) {
    'use strict';

    var WysiwygEditorView,
        BaseView = require('oroui/js/app/views/base/view'),
        _ = require('underscore'),
        $ = require('tinymce/jquery.tinymce.min'),
        txtHtmlTransformer = require('./txt-html-transformer'),
        LoadingMask = require('oroui/js/app/views/loading-mask-view');

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

        events: {
            'set-focus': 'setFocus'
        },

        initialize: function (options) {
            options = $.extend(true, {}, this.defaults, options);
            this.enabled = options.enabled;
            this.options = _.omit(options, ['enabled']);
            WysiwygEditorView.__super__.initialize.apply(this, arguments);
        },

        render: function () {
            var loadingMaskContainer,
                self = this;
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
                loadingMaskContainer = this.$el.parents('.ui-dialog');
                if (!loadingMaskContainer.length) {
                    loadingMaskContainer = this.$el.parent();
                }
                this.subview('loadingMask', new LoadingMask({
                    container: loadingMaskContainer
                }));
                this.subview('loadingMask').show();
                if (!this.firstRender) {
                    if (this.htmlValue && this.$el.val() === this.strippedValue) {
                        // if content is not modified, return html representation back
                        this.$el.val(this.htmlValue);
                    } else {
                        this.$el.val(txtHtmlTransformer.text2html(this.$el.val()));
                    }
                }
                this.renderDeferred = $.Deferred();
                var options = this.options;
                if ($(this.$el).prop('disabled')) {
                    options.readonly = true;
                }
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

                        self.removeSubview('loadingMask');
                        self.tinymceInstance = editor;
                        _.defer(function () {
                            /**
                             * fixes jumping dialog on refresh page
                             * (promise should be resolved in a separate process)
                             */
                            self.renderDeferred.resolve();
                        });
                    }
                }, options));
                this.tinymceConnected = true;
                this.$el.attr('data-focusable', true);
            } else {
                this.$el.removeAttr('data-focusable');
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

        setFocus: function (e) {
            if (this.enabled) {
                this.tinymceInstance.focus();
            }
        },

        getHeight: function () {
            return this.$el.parent().height();
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
