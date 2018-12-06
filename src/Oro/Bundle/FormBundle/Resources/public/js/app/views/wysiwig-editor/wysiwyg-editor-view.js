define(function(require) {
    'use strict';

    var WysiwygEditorView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var $ = require('jquery');
    var tools = require('oroui/js/tools');
    var txtHtmlTransformer = require('./txt-html-transformer');
    var LoadingMask = require('oroui/js/app/views/loading-mask-view');
    var tinyMCE = require('tinymce/tinymce');

    WysiwygEditorView = BaseView.extend({
        TINYMCE_UI_HEIGHT: 3,
        TEXTAREA_UI_HEIGHT: 22,

        autoRender: true,
        firstRender: true,
        firstQuoteLine: void 0,

        tinymceConnected: false,
        height: false,
        tinymceInstance: null,

        defaults: {
            enabled: true,
            plugins: ['textcolor', 'code', 'bdesk_photo', 'paste', 'lists', 'advlist'],
            pluginsMap: {
                bdesk_photo: '/bundles/oroform/lib/bdeskphoto/plugin.min.js'
            },
            menubar: false,
            toolbar: ['undo redo | bold italic underline | forecolor backcolor | bullist numlist | code | bdesk_photo'],
            statusbar: false,
            browser_spellcheck: true,
            images_dataimg_filter: function() {
                return false;
            },
            paste_data_images: false
        },

        events: {
            'set-focus': 'setFocus'
        },

        /**
         * @inheritDoc
         */
        constructor: function WysiwygEditorView() {
            WysiwygEditorView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            options = $.extend(true, {}, this.defaults, options);
            this.enabled = options.enabled;
            this.options = _.omit(options, 'enabled', 'el');
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
            if ($(this.$el).prop('disabled') || $(this.$el).prop('readonly')) {
                options.readonly = true;
            }

            _.each(this.options.pluginsMap, function(url, name) {
                if (this.options.plugins.indexOf(name) !== -1) {
                    tinyMCE.PluginManager.load(name, url);
                }
            }, this);

            tinyMCE.init(_.extend({
                target: this.el,
                setup: function(editor) {
                    editor.on('keydown', function(e) {
                        if (e.keyCode === 27) {
                            _.defer(function() {
                                // action is deferred to give time for tinymce to process the event by itself first
                                self.$el.trigger(e);
                            });
                        }
                    });
                    editor.on('change', function() {
                        editor.save({no_events: true});
                    });
                    editor.on('SetContent', function() {
                        editor.save({no_events: true});
                    });
                },
                init_instance_callback: function(editor) {
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
                                self.$el.after($('<div />', {'class': 'mce-fullscreen-overlay'}));
                                var DOM = editor.target.DOM;
                                var iframe = editor.iframeElement;
                                var iframeTop = iframe.getBoundingClientRect().top;
                                DOM.setStyle(iframe, 'height', window.innerHeight - iframeTop);
                            } else {
                                self.$el.siblings('.mce-fullscreen-overlay').remove();
                            }
                        });
                    }
                    self.trigger('TinyMCE:initialized');
                    _.delay(function() {
                        /**
                         * fixes jumping dialog on refresh page
                         * (promise should be resolved in a separate process)
                         */
                        self._resolveDeferredRender();
                    }, 20);
                }
            }, options));
            this.tinymceConnected = true;
            this.deferredRender.fail(function() {
                self.removeSubview('loadingMask');
                self.tinymceInstance = null;
                self.tinymceConnected = false;
                self.$el.css('visibility', '');
            });
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
            var quote = $('<div>').html(this.$el[0].value).find('.quote').html();
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
