define(function(require) {
    'use strict';

    var WysiwygEditorView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var $ = require('tinymce/jquery.tinymce.min');
    var tools = require('oroui/js/tools');
    var txtHtmlTransformer = require('./txt-html-transformer');
    var LoadingMask = require('oroui/js/app/views/loading-mask-view');

    WysiwygEditorView = BaseView.extend({
        TINYMCE_UI_HEIGHT: 3,
        TEXTAREA_UI_HEIGHT: 22,
        TINYMCE_TIMEOUT: 1000, //after this time view promise will be resolved anyway

        autoRender: true,
        firstRender: true,
        firstQuoteLine: void 0,

        tinymceConnected: false,
        height: false,
        tinymceInstance: null,

        defaults: {
            enabled: true,
            plugins: ['textcolor', 'code', 'bdesk_photo'],
            menubar: false,
            toolbar: ['undo redo | bold italic underline | forecolor backcolor | bullist numlist | code | bdesk_photo'],
            statusbar: false,
            browser_spellcheck: true
        },

        events: {
            'set-focus': 'setFocus'
        },

        initialize: function(options) {
            options = $.extend(true, {}, this.defaults, options);
            this.enabled = options.enabled;
            this.options = _.omit(options, ['enabled']);
            if (tools.isIOS()) {
                this.options.plugins = _.without(this.options.plugins, 'fullscreen');
                this.options.toolbar = this.options.toolbar.map(function(toolbar) {
                    return toolbar.replace(/\s*\|\s?fullscreen/, '');
                });
            }
            WysiwygEditorView.__super__.initialize.apply(this, arguments);
        },

        render: function() {
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
                this.connectTinyMCE();
                this.$el.attr('data-focusable', true);
                this.findFirstQuoteLine();
            } else {
                this.$el.removeAttr('data-focusable');
            }
            this.firstRender = false;
            this.trigger('resize');
        },

        connectTinyMCE: function() {
            var self = this;
            var loadingMaskContainer = this.$el.parents('.ui-dialog');
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
            this._deferredRender();
            var options = this.options;
            if ($(this.$el).prop('disabled')) {
                options.readonly = true;
            }
            this.$el.tinymce(_.extend({
                'init_instance_callback': function(editor) {
                    self.removeSubview('loadingMask');
                    self.tinymceInstance = editor;
                    if (!tools.isMobile()) {
                        self.tinymceInstance.on('FullscreenStateChanged', function(e) {
                            if (e.state) {
                                var rect = $('#container').get(0).getBoundingClientRect();
                                var css = {
                                    top: rect.top + 'px',
                                    left: rect.left + 'px',
                                    right: Math.max(window.innerWidth - rect.right, 0) + 'px'
                                };

                                var rules = _.map(_.pairs(css), function(item) {
                                    return item.join(': ');
                                }).join('; ');
                                tools.addCSSRule('div.mce-container.mce-fullscreen', rules);
                                self.$el.after($('<div />', {class: 'mce-fullscreen-overlay'}));
                            } else {
                                self.$el.siblings('.mce-fullscreen-overlay').remove();
                            }
                        });
                    }
                    _.defer(function() {
                        /**
                         * fixes jumping dialog on refresh page
                         * (promise should be resolved in a separate process)
                         */
                        self._resolveDeferredRender();
                    });
                }
            }, options));
            this.tinymceConnected = true;

            /**
             * In case when TinyMCE in some reason wasn't initialized we resolve the view anyway
             */
            _.delay(function() {
                if ('deferredRender' in self === false) {
                    return;
                }
                if (window.console && window.console.warn) {
                    window.console.warn('TinyMCE initialization fault');
                }
                self.removeSubview('loadingMask');
                self.tinymceInstance = null;
                self.tinymceConnected = false;
                self.$el.css('visibility', '');
                self._resolveDeferredRender();
            }, this.TINYMCE_TIMEOUT);
        },

        setEnabled: function(enabled) {
            if (this.enabled === enabled) {
                return;
            }
            this.enabled = enabled;
            this.render();
        },

        setFocus: function(e) {
            if (this.enabled) {
                this.tinymceInstance.focus();
            }
        },

        getHeight: function() {
            return this.$el.parent().innerHeight();
        },

        findFirstQuoteLine: function() {
            var quote = $('<div>').html(this.$el.val()).find('.quote').html();
            if (quote) {
                quote = txtHtmlTransformer.html2text(quote);
                this.firstQuoteLine = _.find(quote.split(/(\n\r?|\r\n?)/g), function(line) {
                    return line.trim().length > 0;
                });
                if (this.firstQuoteLine) {
                    this.firstQuoteLine = this.firstQuoteLine.trim();
                }
            } else {
                this.firstQuoteLine = void 0;
            }
        },

        getFirstQuoteLine: function() {
            return this.firstQuoteLine;
        },

        setHeight: function(newHeight) {
            var currentToolbarHeight;
            if (this.tinymceConnected) {
                currentToolbarHeight = this.$el.parent().find('.mce-toolbar-grp').outerHeight();
                this.$el.parent().find('iframe').height(newHeight - currentToolbarHeight - this.TINYMCE_UI_HEIGHT);
            } else {
                this.$el.height(newHeight - this.TEXTAREA_UI_HEIGHT);
            }
        },

        dispose: function() {
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
